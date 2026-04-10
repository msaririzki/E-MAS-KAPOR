<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PersonnelExport;
use App\Exports\PersonnelKeteranganExport;
use App\Exports\PersonnelTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Middleware\SystemLock;
use App\Imports\PersonnelImport;
use App\Imports\PersonnelKeteranganImport;
use App\Imports\PersonnelSdmImport;
use App\Imports\PersonnelUpdateImport;
use App\Jobs\ProcessSdmImportPreview;
use App\Models\BagianOption;
use App\Models\KaporItem;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\SdmImportRun;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ExportSignatorySettingService;
use App\Services\KaporRequirementService;
use App\Services\SdmImportRunService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PersonnelController extends Controller
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService,
        private readonly SdmImportRunService $sdmImportRunService,
    ) {}

    public function index(Request $request)
    {
        $sort = $request->get('sort', 'latest');
        $direction = $request->get('direction', 'desc');
        $query = Personnel::with(['rank', 'satker'])->forCurrentSatker();

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('nrp', 'LIKE', "%{$search}%")
                    ->orWhereHas('rank', function ($rq) use ($search) {
                        $rq->where('name', 'LIKE', "%{$search}%");
                    }
                    )
                    ->orWhere('jabatan', 'LIKE', "%{$search}%")
                    ->orWhere('bagian', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('rank_id')) {
            $query->where('rank_id', $request->rank_id);
        }

        if ($request->filled('satker_id') && ! $request->user()?->hasRole('admin_satker')) {
            $query->where('satker_id', $request->satker_id);
        }

        if ($request->filled('keterangan')) {
            $query->where('keterangan', $request->keterangan);
        }

        if ($request->filled('bagian')) {
            $query->whereRaw('UPPER(bagian) = ?', [Str::upper(trim((string) $request->bagian))]);
        }

        if ($request->get('status') === 'pending_verification') {
            $query->where('verification_status', 'pending_verification');
        }

        // Filter: hanya tampilkan personel dengan data belum lengkap
        // (kapor_sizes NULL, atau ada ukuran yang hilang, ATAU rank_id NULL ATAU NRP NULL)
        $isIncompleteFilter = $request->get('status') === 'incomplete';
        $missingSizeFilter = $request->get('missing_size', ''); // e.g. 'kemeja', 'topi', etc.
        $incompleteScope = $request->get('incomplete_scope', 'all');
        $kaporItemId = $request->integer('kapor_item_id');

        if ($isIncompleteFilter) {
            // Jika ada filter ukuran spesifik, hanya cari personel yang field itu NULL
            if (! empty($missingSizeFilter)) {
                $allowedSizeKeys = ['topi', 'kemeja', 'celana', 'olahraga', 'sepatu_dinas', 'sepatu_olahraga', 'jaket', 'sabuk', 'jilbab'];
                if (in_array($missingSizeFilter, $allowedSizeKeys)) {
                    if ($kaporItemId <= 0) {
                        $query->where(function ($q) use ($missingSizeFilter) {
                            if ($missingSizeFilter === 'jilbab') {
                                $q->where('gender', 'P')
                                    ->where(function ($hijabQuery) {
                                        $this->kaporRequirementService->applyHijabStatusConstraint($hijabQuery);
                                    })
                                    ->where(function ($q2) {
                                        $q2->whereNull('personnels.kapor_sizes')
                                            ->orWhereNull('kapor_sizes->jilbab');
                                    });

                            } else {
                                $q->whereNull('personnels.kapor_sizes')
                                    ->orWhereNull("kapor_sizes->{$missingSizeFilter}");
                            }
                        });
                    }
                }

            } else {
                // Tidak ada filter spesifik — filter dinamis menggunakan service untuk menjamin sinkronisasi dengan stats
                $incompleteIds = Personnel::forCurrentSatker()
                    ->get([
                        'id',
                        'rank_id',
                        'nrp',
                        'gender',
                        'kapor_sizes',
                        'keterangan',
                        'keterangan_2',
                        'keterangan_3',
                        'keterangan_4',
                    ])
                    ->filter(function (Personnel $personnel) use ($incompleteScope) {
                        $isSizeIncomplete = ! $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel);

                        if ($incompleteScope === 'size_only') {
                            return $isSizeIncomplete;
                        }

                        return $isSizeIncomplete || $personnel->rank_id === null || $personnel->nrp === null;
                    })
                    ->pluck('id');

                $query->whereIn('personnels.id', $incompleteIds);
            }

            // Kelompokkan berdasarkan satker agar tidak tercampur
            if ($sort !== 'satker') {
                $query->leftJoin('satkers', 'personnels.satker_id', '=', 'satkers.id')
                    ->select('personnels.*');
            }
            $query->orderBy('satkers.name', 'asc')->orderBy('personnels.full_name', 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', $isIncompleteFilter ? 100 : 10);
        $filteredStatsCollection = collect();
        if ($isIncompleteFilter && ! empty($missingSizeFilter) && $kaporItemId > 0) {
            $kaporItem = KaporItem::with('sizes')->find($kaporItemId);

            if ($kaporItem !== null) {
                $filtered = $query->get()->filter(function (Personnel $personnel) use ($kaporItem, $missingSizeFilter) {
                    return $this->kaporRequirementService->resolveSizeKey($kaporItem->item_name) === $missingSizeFilter
                        && $this->kaporRequirementService->personnelMissingSizeForItem($personnel, $kaporItem, $missingSizeFilter);
                })->values();
                $filteredStatsCollection = $filtered;

                $currentPage = LengthAwarePaginator::resolveCurrentPage();
                $items = $filtered->slice(($currentPage - 1) * $perPage, $perPage)->values();

                $personnels = new LengthAwarePaginator(
                    $items,
                    $filtered->count(),
                    (int) $perPage,
                    $currentPage,
                    [
                        'path' => $request->url(),
                        'query' => $request->query(),
                    ]
                );
            } else {
                $filteredStatsCollection = (clone $query)->get();
                $personnels = $query->paginate($perPage)->withQueryString();
            }
        } else {
            $filteredStatsCollection = (clone $query)->get();
            $personnels = $query->paginate($perPage)->withQueryString();
        }

        $totalReal = $filteredStatsCollection->count();
        $submittedCount = $filteredStatsCollection
            ->filter(fn (Personnel $personnel) => $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel))
            ->count();
        $stats = [
            'total_real' => $totalReal,
            'polri' => $filteredStatsCollection->where('personnel_type', 'Polri')->count(),
            'pns' => $filteredStatsCollection->where('personnel_type', 'PNS')->count(),
            'submitted' => $submittedCount,
            'pending' => $totalReal - $submittedCount,
            'active' => $filteredStatsCollection->where('is_active', true)->count(),
            'pending_verification' => $filteredStatsCollection->where('verification_status', 'pending_verification')->count(),
            'is_filtered' => $this->hasActivePersonnelFilters($request),
            'scope_label' => $this->buildPersonnelStatsScopeLabel($request),
        ];

        $ranks = Rank::orderBy('sort_order')->get();
        $satkers = Satker::orderBy('sort_order')->orderBy('name')->get();
        $bagians = $this->resolveAvailableBagians($request);
        $printSignatoryDefaults = app(ExportSignatorySettingService::class)->resolveForUser($request->user());
        // Note: kaporItems query removed as we now use decoupled JSON sizes in kapor_sizes column

        return view('admin.personnel.index', compact('personnels', 'stats', 'ranks', 'satkers', 'bagians', 'perPage', 'isIncompleteFilter', 'missingSizeFilter', 'incompleteScope', 'kaporItemId', 'printSignatoryDefaults'));

    }

    private function hasActivePersonnelFilters(Request $request): bool
    {
        $satkerFilterActive = $request->filled('satker_id') && ! $request->user()?->hasRole('admin_satker');

        return $request->filled('search')
            || $request->filled('rank_id')
            || $satkerFilterActive
            || $request->filled('keterangan')
            || $request->filled('bagian')
            || $request->get('status') === 'pending_verification'
            || $request->get('status') === 'incomplete'
            || $request->filled('missing_size')
            || $request->filled('kapor_item_id');
    }

    private function buildPersonnelStatsScopeLabel(Request $request): string
    {
        if (! $this->hasActivePersonnelFilters($request)) {
            return 'Semua data personel';
        }

        $labels = [];

        if ($request->filled('satker_id') && ! $request->user()?->hasRole('admin_satker')) {
            $satker = Satker::find($request->input('satker_id'));
            if ($satker) {
                $labels[] = 'Satker: '.$satker->name;
            }
        }

        if ($request->filled('rank_id')) {
            $rank = Rank::find($request->input('rank_id'));
            if ($rank) {
                $labels[] = 'Pangkat: '.$rank->name;
            }
        }

        if ($request->filled('bagian')) {
            $labels[] = 'Bag/Fungsi: '.$request->input('bagian');
        }

        if ($request->filled('search')) {
            $labels[] = 'Pencarian aktif';
        }

        if ($request->get('status') === 'pending_verification') {
            $labels[] = 'Menunggu verifikasi';
        }

        if ($request->get('status') === 'incomplete') {
            $labels[] = 'Filter ukuran belum lengkap';
        }

        return $labels !== [] ? implode(' • ', $labels) : 'Berdasarkan filter aktif';
    }

    private function resolveAvailableBagians(Request $request)
    {
        $masterBagians = BagianOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');

        $personnelBagiansQuery = Personnel::query()
            ->forCurrentSatker()
            ->whereNotNull('bagian')
            ->where('bagian', '!=', '');

        if ($request->filled('satker_id') && ! $request->user()?->hasRole('admin_satker')) {
            $personnelBagiansQuery->where('satker_id', $request->integer('satker_id'));
        }

        $personnelBagians = $personnelBagiansQuery
            ->distinct()
            ->orderBy('bagian')
            ->pluck('bagian');

        return $masterBagians
            ->merge($personnelBagians)
            ->map(fn ($bagian) => strtoupper(trim((string) $bagian)))
            ->filter()
            ->unique()
            ->values();
    }

    private function applyPersonnelLookupFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->where(function (Builder $builder) use ($search) {
                $builder->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('nrp', 'LIKE', "%{$search}%")
                    ->orWhere('jabatan', 'LIKE', "%{$search}%")
                    ->orWhere('bagian', 'LIKE', "%{$search}%")
                    ->orWhereHas('rank', fn (Builder $rankQuery) => $rankQuery->where('name', 'LIKE', "%{$search}%"));
            });
        }

        if ($request->filled('bagian')) {
            $query->whereRaw('UPPER(bagian) = ?', [Str::upper(trim((string) $request->input('bagian')))]);
        }
    }

    private function filterPersonnelsByCompletion(Collection $personnels, ?string $status): Collection
    {
        return match ($status) {
            'pending' => $personnels
                ->filter(fn (Personnel $personnel) => ! $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel))
                ->values(),
            'submitted' => $personnels
                ->filter(fn (Personnel $personnel) => $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel))
                ->values(),
            default => $personnels->values(),
        };
    }

    private function resolvePersonnelExportIds(Request $request, ?array $satkerIds): ?array
    {
        $status = $request->input('status');
        $hasExportFilters = $request->filled('search')
            || $request->filled('bagian')
            || in_array($status, ['pending', 'submitted'], true);

        if (! $hasExportFilters) {
            return null;
        }

        $query = Personnel::query()->with('rank');

        if ($satkerIds !== null) {
            $query->whereIn('satker_id', $satkerIds);
        }

        $this->applyPersonnelLookupFilters($query, $request);

        return $this->filterPersonnelsByCompletion($query->get(), $status)
            ->pluck('id')
            ->all();
    }

    public function store(Request $request)
    {
        $isAdminSatker = $request->user()?->hasRole('admin_satker');

        $this->abortIfAdminSatkerWriteLocked($request);

        $validated = $request->validate([
            'nrp' => 'required|string',
            'full_name' => 'required|string|max:255',
            'rank_id' => 'required|exists:ranks,id',
            'satker_id' => $isAdminSatker ? 'nullable' : 'required|exists:satkers,id',
            'jabatan' => 'nullable|string|max:255',
            'bagian' => 'nullable|string|max:255',
            'personnel_type' => 'required|in:Polri,PNS',
            'gender' => 'required|in:L,P',
            'phone' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:50',
            'golongan' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:255',
            'keterangan_2' => 'nullable|string|max:255',
            'keterangan_3' => 'nullable|string|max:255',
            'keterangan_4' => 'nullable|string|max:255',
            'kapor_sizes' => 'nullable|array',
        ]);

        if (!auth()->user()?->hasRole('superadmin')) {
            unset($validated['keterangan_2'], $validated['keterangan_3'], $validated['keterangan_4']);
        }

        if ($isAdminSatker) {
            $validated['satker_id'] = (int) $request->user()->satker_id;
        }

        $nrp = trim((string) $validated['nrp']);
        $duplicateIdentity = $this->findDuplicatePersonnelIdentity($nrp);

        if ($duplicateIdentity !== null) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'nrp' => $this->buildDuplicatePersonnelIdentityMessage($nrp, $duplicateIdentity, (int) $validated['satker_id']),
                ]);
        }

        $requestMode = Setting::getValue('personnel_request_mode', 'auto');
        $requiresVerification = $isAdminSatker && $requestMode === 'pending_verification';

        DB::beginTransaction();
        try {
            if (array_key_exists('kapor_sizes', $validated)) {
                $validated['kapor_sizes'] = $this->kaporRequirementService->sanitizeSubmittedSizes(
                    $validated['kapor_sizes'] ?? [],
                    $validated['gender'] ?? null,
                );
            }

            // 1. Create User Account
            $user = User::createOrUpdatePersonnelAccount(
                null,
                $validated['nrp'],
                $validated['full_name'],
                $validated['satker_id'],
                ! $requiresVerification,
            );

            // 2. Create Personnel Record
            $personnelData = $validated;
            $personnelData['user_id'] = $user->id;
            $personnelData['is_active'] = ! $requiresVerification;
            $personnelData['verification_status'] = $requiresVerification ? 'pending_verification' : 'approved';
            $personnel = Personnel::create($personnelData);

            DB::commit();

            // Sinkronkan jumlah Polri/PNS di satker setelah tambah personil
            PersonnelImport::recalculateSatkerCount($validated['satker_id']);

            if ($requiresVerification) {
                return redirect()->route('admin.personnel.index')->with('warning', 'Usulan personel baru berhasil dibuat dan menunggu verifikasi superadmin.');
            }

            return redirect()->route('admin.personnel.index')->with('success', 'Data personil dan ukuran berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menambahkan personil: '.$e->getMessage());
        }
    }

    public function storeMeasurements(Request $request, Personnel $personnel)
    {
        $this->abortIfAdminSatkerWriteLocked($request);

        $validated = $request->validate([
            'kapor_sizes' => 'required|array',
        ]);

        try {
            $personnel->update([
                'kapor_sizes' => $this->kaporRequirementService->sanitizeSubmittedSizes(
                    $validated['kapor_sizes'],
                    $personnel->gender,
                ),
            ]);

            return redirect()->back()->with('success', 'Data ukuran berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan ukuran: '.$e->getMessage());
        }
    }

    public function approveVerification(Personnel $personnel)
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Hanya Superadmin yang dapat memverifikasi usulan personel.');

        if ($personnel->verification_status !== 'pending_verification') {
            return redirect()->back()->with('info', 'Personel ini tidak sedang menunggu verifikasi.');
        }

        DB::transaction(function () use ($personnel) {
            $personnel->update([
                'verification_status' => 'approved',
                'is_active' => true,
            ]);

            if ($personnel->user) {
                $personnel->user->update([
                    'is_active' => true,
                    'satker_id' => $personnel->satker_id,
                ]);
            }
        });

        AuditLogger::log('Setujui Usulan Personel', 'Manajemen Personil', $personnel->fresh(), null, ['verification_status' => 'approved'], 'success', 'Menyetujui usulan personel baru.');

        return redirect()->back()->with('success', 'Usulan personel berhasil disetujui dan diaktifkan.');
    }

    public function rejectVerification(Personnel $personnel)
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Hanya Superadmin yang dapat menolak usulan personel.');

        if ($personnel->verification_status !== 'pending_verification') {
            return redirect()->back()->with('info', 'Personel ini tidak sedang menunggu verifikasi.');
        }

        DB::transaction(function () use ($personnel) {
            $personnel->update([
                'verification_status' => 'rejected',
                'is_active' => false,
            ]);

            if ($personnel->user) {
                $personnel->user->update([
                    'is_active' => false,
                ]);
            }
        });

        AuditLogger::log('Tolak Usulan Personel', 'Manajemen Personil', $personnel->fresh(), null, ['verification_status' => 'rejected'], 'warning', 'Menolak usulan personel baru.');

        return redirect()->back()->with('warning', 'Usulan personel ditolak dan tetap nonaktif.');
    }

    public function update(Request $request, Personnel $personnel)
    {
        if ($request->user()?->hasRole('admin_satker')) {
            return $this->updateAsAdminSatker($request, $personnel);
        }

        $validated = $request->validate([
            'nrp' => 'required|string',
            'full_name' => 'required|string|max:255',
            'rank_id' => 'required|exists:ranks,id',
            'satker_id' => 'required|exists:satkers,id',
            'jabatan' => 'nullable|string|max:255',
            'bagian' => 'nullable|string|max:255',
            'personnel_type' => 'nullable|in:Polri,PNS',
            'gender' => 'nullable|in:L,P',
            'phone' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:50',
            'golongan' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:255',
            'keterangan_2' => 'nullable|string|max:255',
            'keterangan_3' => 'nullable|string|max:255',
            'keterangan_4' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'kapor_sizes' => 'nullable|array',
        ]);

        $oldSatkerId = $personnel->satker_id;
        $duplicateIdentity = $this->findDuplicatePersonnelIdentity($validated['nrp'], $personnel, $personnel->user);

        if ($duplicateIdentity !== null) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'nrp' => $this->buildDuplicatePersonnelIdentityMessage($validated['nrp'], $duplicateIdentity, (int) $validated['satker_id']),
                ]);
        }

        DB::beginTransaction();
        try {
            if (array_key_exists('kapor_sizes', $validated)) {
                $validated['kapor_sizes'] = $this->kaporRequirementService->sanitizeSubmittedSizes(
                    $validated['kapor_sizes'] ?? [],
                    $validated['gender'] ?? $personnel->gender,
                );
            }

            $personnel->fill($validated);

            $user = $personnel->user;
            $targetUserState = [
                'nrp_nip' => $validated['nrp'],
                'name' => $validated['full_name'],
                'satker_id' => $validated['satker_id'],
                'is_active' => (bool) ($validated['is_active'] ?? $personnel->is_active),
            ];

            $userNeedsSync = ! $user
                || $user->nrp_nip !== $targetUserState['nrp_nip']
                || $user->name !== $targetUserState['name']
                || (int) $user->satker_id !== (int) $targetUserState['satker_id']
                || (bool) $user->is_active !== $targetUserState['is_active'];

            if (! $personnel->isDirty() && ! $userNeedsSync) {
                DB::rollBack();

                return redirect()->back()->with('info', 'Tidak ada perubahan pada data personel.');
            }

            $user = User::createOrUpdatePersonnelAccount(
                $user,
                $validated['nrp'],
                $validated['full_name'],
                $validated['satker_id'],
                $targetUserState['is_active'],
            );

            // Update Personnel
            $personnel->forceFill([
                'user_id' => $user->id,
            ])->save();

            DB::commit();

            // Sinkronkan jumlah Polri/PNS di satker setelah update personil
            PersonnelImport::recalculateSatkerCount($validated['satker_id']);
            // Jika satker berubah, update juga satker lama
            if ($oldSatkerId != $validated['satker_id']) {
                PersonnelImport::recalculateSatkerCount($oldSatkerId);
            }

            return redirect()->back()->with('success', 'Data personil dan akun berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage());
        }
    }

    private function updateAsAdminSatker(Request $request, Personnel $personnel)
    {
        abort_unless((int) $personnel->satker_id === (int) $request->user()?->satker_id, 403, 'Anda hanya dapat mengedit personel pada satker Anda sendiri.');

        $this->abortIfAdminSatkerWriteLocked($request);

        $validated = $request->validate([
            'nrp' => 'required|string',
            'full_name' => 'required|string|max:255',
            'rank_id' => 'required|exists:ranks,id',
            'satker_id' => 'nullable',
            'jabatan' => 'nullable|string|max:255',
            'bagian' => 'nullable|string|max:255',
            'personnel_type' => 'nullable|in:Polri,PNS',
            'gender' => 'nullable|in:L,P',
            'phone' => 'nullable|string|max:20',
            'religion' => 'nullable|string|max:50',
            'golongan' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:255',
            'keterangan_2' => 'nullable|string|max:255',
            'keterangan_3' => 'nullable|string|max:255',
            'keterangan_4' => 'nullable|string|max:255',
            'kapor_sizes' => 'nullable|array',
        ]);

        if (!auth()->user()?->hasRole('superadmin')) {
            unset($validated['keterangan_2'], $validated['keterangan_3'], $validated['keterangan_4']);
        }

        $validated['satker_id'] = (int) $request->user()->satker_id;
        $duplicateIdentity = $this->findDuplicatePersonnelIdentity($validated['nrp'], $personnel, $personnel->user);

        if ($duplicateIdentity !== null) {
            return redirect()->back()
                ->withInput()
                ->withErrors([
                    'nrp' => $this->buildDuplicatePersonnelIdentityMessage($validated['nrp'], $duplicateIdentity, (int) $validated['satker_id']),
                ]);
        }

        DB::beginTransaction();
        try {
            if (array_key_exists('kapor_sizes', $validated)) {
                $validated['kapor_sizes'] = $this->kaporRequirementService->sanitizeSubmittedSizes(
                    $validated['kapor_sizes'] ?? [],
                    $validated['gender'] ?? $personnel->gender,
                );
            }

            $personnel->fill($validated);

            $user = $personnel->user;
            $targetUserState = [
                'nrp_nip' => $validated['nrp'],
                'name' => $validated['full_name'],
                'satker_id' => $validated['satker_id'],
                'is_active' => (bool) $personnel->is_active,
            ];

            $userNeedsSync = ! $user
                || $user->nrp_nip !== $targetUserState['nrp_nip']
                || $user->name !== $targetUserState['name']
                || (int) $user->satker_id !== (int) $targetUserState['satker_id']
                || (bool) $user->is_active !== $targetUserState['is_active'];

            if (! $personnel->isDirty() && ! $userNeedsSync) {
                DB::rollBack();

                return redirect()->back()->with('info', 'Tidak ada perubahan pada data personel.');
            }

            $user = User::createOrUpdatePersonnelAccount(
                $user,
                $validated['nrp'],
                $validated['full_name'],
                $validated['satker_id'],
                $targetUserState['is_active'],
            );

            $personnel->forceFill([
                'user_id' => $user->id,
            ])->save();

            DB::commit();

            PersonnelImport::recalculateSatkerCount($validated['satker_id']);

            return redirect()->back()->with('success', 'Data personil dan akun berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage());
        }
    }

    public function destroy(Personnel $personnel)
    {
        $this->abortIfAdminSatkerWriteLocked(request());

        $satkerId = $personnel->satker_id;

        DB::beginTransaction();
        try {
            if ($personnel->user) {
                $personnel->user->delete();
            }
            $personnel->delete();

            DB::commit();

            // Sinkronkan jumlah Polri/PNS di satker setelah hapus personil
            PersonnelImport::recalculateSatkerCount($satkerId);

            return redirect()->back()->with('success', 'Personil dan akun terkait berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menghapus: '.$e->getMessage());
        }
    }

    public function nrpIssues(Request $request)
    {
        return redirect()->route('admin.personnel.index')->with('warning', 'Fitur NRP Issues sedang dinonaktifkan sementara karena penyesuaian database.');
    }

    /**
     * Tandai masalah NRP selesai direview.
     */
    public function resolveNrpIssue(Request $request, Personnel $personnel)
    {
        return redirect()->back()->with('error', 'Fitur Resolusi NRP dinonaktifkan sementara.');
    }

    /**
     * Download Excel template for personnel import.
     */
    public function downloadTemplate()
    {
        return Excel::download(new PersonnelTemplateExport, 'template_import_personil.xlsx');
    }

    public function exportKeterangan()
    {
        $this->ensureSuperadmin();

        $fileName = 'template_import_keterangan_personel_'.date('Ymd').'.xlsx';

        AuditLogger::log(
            'Export Template Keterangan Personel',
            'Manajemen Personil',
            null,
            null,
            null,
            'info',
            'Mengunduh file referensi untuk import keterangan personel.'
        );

        return Excel::download(new PersonnelKeteranganExport, $fileName);
    }

    /**
     * Import personnel — hanya baca file, simpan preview ke session, redirect ke halaman preview.
     */
    public function import(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        if (auth()->user()?->hasRole('admin_satker')) {
            abort(403, 'Admin satker harus menggunakan Impor Update Data untuk menambah atau menyesuaikan data personel.');
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200', // max 50MB
            'satker_id' => 'required|exists:satkers,id',
        ]);

        $user = auth()->user();

        // Admin satker hanya boleh import ke satkernya sendiri
        if ($user->hasRole('admin_satker') && (int) $request->satker_id !== (int) $user->satker_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk satker tersebut.');
        }

        try {
            $import = new PersonnelImport($request->satker_id);
            $collection = Excel::toCollection($import, $request->file('file'));

            // Proses setiap sheet terpisah agar deteksi kolom (double-NO) per-sheet
            // Sheet POLRI dan PNS bisa punya layout berbeda
            $preview = [];
            foreach ($collection as $sheetIndex => $sheetRows) {
                // generatePreview menerima Collection hasil toCollection langsung
                // sheetIndex menentukan personnel_type: 0=Polri, >=1=PNS
                $sheetPreview = $import->generatePreview($sheetRows, $sheetIndex);
                $preview = array_merge($preview, $sheetPreview);
            }

            // Hitung total status
            $totalOk = collect($preview)->where('status', 'ok')->count();
            $totalCorrected = collect($preview)->where('status', 'corrected')->count();
            $totalError = collect($preview)->where('status', 'error')->count();

            // Simpan ke session
            session([
                'import_preview' => $preview,
                'import_satker_id' => $request->satker_id,
                'import_stats' => [
                    'ok' => $totalOk,
                    'corrected' => $totalCorrected,
                    'error' => $totalError,
                    'total' => count($preview),
                ],
            ]);

            AuditLogger::log('Preview Import Personil', 'Manajemen Personil', null, null, null, 'info', "Preview: {$totalOk} siap, {$totalCorrected} dikoreksi, {$totalError} error");

            return redirect()->route('admin.personnel.import-preview');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file: '.$e->getMessage());
        }
    }

    /**
     * Tampilkan halaman preview hasil parsing file Excel.
     */
    public function importPreview()
    {
        $preview = session('import_preview');
        $satkerId = session('import_satker_id');
        $stats = session('import_stats');

        if (! $preview || ! $satkerId) {
            return redirect()->route('admin.personnel.index')->with('error', 'Sesi preview sudah kadaluwarsa. Silakan upload ulang file.');
        }

        $satker = Satker::find($satkerId);
        $ranks = Rank::orderBy('sort_order')->get();

        return view('admin.personnel.import_preview', compact('preview', 'satker', 'stats', 'ranks'));
    }

    /**
     * Konfirmasi import: baca data dari SESSION, merge rank_overrides dari form.
     * Tidak mengandalkan form POST untuk semua data (menghindari batas max_input_vars).
     */
    public function importConfirm(Request $request)
    {
        set_time_limit(0);

        $satkerId = session('import_satker_id');
        $preview = session('import_preview');

        if (! $satkerId || ! $preview) {
            return redirect()->route('admin.personnel.index')
                ->with('error', 'Sesi preview sudah kadaluwarsa. Silakan upload ulang file.');
        }

        // Ambil koreksi rank_id manual dari form (hanya baris yang diedit)
        // Format: rank_overrides[index] = rank_id
        $rankOverrides = $request->input('rank_overrides', []);

        // Terapkan override ke data preview dari session
        foreach ($rankOverrides as $index => $rankId) {
            if (isset($preview[$index]) && $rankId !== '' && $rankId !== null) {
                $preview[$index]['rank_id'] = $rankId;
            }
        }

        // Ambil aksi untuk baris duplikat (skip vs import)
        $actionOverrides = $request->input('action_overrides', []);
        foreach ($actionOverrides as $index => $action) {
            if ($action === 'skip' && isset($preview[$index])) {
                // Hapus data dari preview agar tidak diimport
                unset($preview[$index]);
            }
        }

        // Info: baris error tanpa pangkat akan tetap diimport dengan rank_id NULL
        // Tidak memblokir proses import agar data personel yang belum lengkap tetap masuk
        $missingRank = [];
        foreach ($preview as $i => $row) {
            if ($row['status'] === 'error' && empty($row['rank_id'])) {
                $missingRank[] = ($row['full_name'] ?? "Baris #{$i}");
            }
        }

        try {
            $importer = new PersonnelImport($satkerId);
            $results = $importer->saveFromPreviewData($preview, $satkerId);

            $successCount = $results['success_count'];
            $errorCount = $results['error_count'];
            $errors = $results['errors'];

            // Hapus session setelah selesai
            session()->forget(['import_preview', 'import_satker_id', 'import_stats']);

            AuditLogger::log('Konfirmasi Import Personil', 'Manajemen Personil', null, null, null, 'success', "Berhasil: {$successCount}. Gagal: {$errorCount}");

            if ($errorCount > 0) {
                return redirect()->route('admin.personnel.index')
                    ->with('warning', "Berhasil mengimpor {$successCount} data. Gagal: {$errorCount}.");
            }

            return redirect()->route('admin.personnel.index')
                ->with('success', "Berhasil mengimpor {$successCount} data personil.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan data: '.$e->getMessage());
        }
    }

    /**
     * Batalkan proses import: bersihkan session dan redirect ke halaman personel.
     */
    public function importCancel()
    {
        session()->forget(['import_preview', 'import_satker_id', 'import_stats']);

        return redirect()->route('admin.personnel.index')->with('info', 'Proses import dibatalkan.');
    }

    /**
     * Export data personel ke Excel (untuk diedit dan diupload kembali sebagai update).
     *
     * Admin        : bisa memilih satker tertentu atau semua satker.
     * Admin Satker : otomatis hanya export satker miliknya sendiri.
     */
    public function exportPersonnel(Request $request)
    {
        // Increase memory and time limits for heavy export operations
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $user = auth()->user();

        if ($user->hasRole('admin_satker')) {
            // Admin satker: paksa ke satkernya sendiri
            $satker = Satker::find($user->satker_id);
            $satkerIds = [$user->satker_id];
            $satkerName = $satker?->name ?? 'SATKER';
        } else {
            // Admin: bisa pilih satker atau semua
            $satkerIdParam = $request->get('satker_id');
            if ($satkerIdParam && $satkerIdParam !== 'all') {
                $satker = Satker::findOrFail($satkerIdParam);
                $satkerIds = [$satker->id];
                $satkerName = $satker->name;
            } else {
                $satkerIds = null; // semua
                $satkerName = 'SEMUA SATKER';
            }
        }

        $safeName = preg_replace('/[\\/:*?"<>|]/', '_', $satkerName);
        $fileName = 'Data_Personel_'.$safeName.'_'.date('Ymd').'.xlsx';

        AuditLogger::log(
            'Export Data Personel',
            'Manajemen Personil',
            null, null, null,
            'info',
            "Export: {$satkerName}"
        );

        $personnelIds = $this->resolvePersonnelExportIds($request, $satkerIds);

        return Excel::download(new PersonnelExport($satkerIds, $satkerName, $personnelIds), $fileName);
    }

    /**
     * Import UPDATE: baca file Excel, cocokkan via NRP, tampilkan preview.
     */
    public function importUpdate(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        $this->abortIfAdminSatkerWriteLocked($request);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200',
            'satker_id' => 'required|exists:satkers,id',
        ]);

        $user = auth()->user();

        // Admin satker hanya boleh update satkernya sendiri
        if ($user->hasRole('admin_satker') && (int) $request->satker_id !== (int) $user->satker_id) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk satker tersebut.');
        }

        try {
            $import = new PersonnelUpdateImport((int) $request->satker_id);
            $collection = Excel::toCollection($import, $request->file('file'));

            $dataRows = collect();
            foreach ($collection as $sheetRows) {
                $dataRows = $dataRows->concat($sheetRows);
            }

            $preview = $import->generatePreview($dataRows);

            $coll = collect($preview);
            // Hanya hitung baris yang benar-benar BERUBAH (bukan no_change) untuk angka "update"
            $totalUpdate = $coll->where('action', 'update')->whereIn('status', ['update', 'corrected'])->count();
            $totalNew = $coll->where('action', 'new')->whereNotIn('status', ['error'])->count();
            $totalError = $coll->where('status', 'error')->count();
            $totalNoChange = $coll->where('status', 'no_change')->count();
            $totalCorrected = $coll->where('status', 'corrected')->count();

            session([
                'update_import_preview' => $preview,
                'update_import_satker_id' => $request->satker_id,
                'update_import_stats' => [
                    'update' => $totalUpdate,
                    'new' => $totalNew,
                    'error' => $totalError,
                    'no_change' => $totalNoChange,
                    'corrected' => $totalCorrected,
                    'total' => count($preview),
                ],
            ]);

            AuditLogger::log(
                'Preview Import Update/Tambah Personil',
                'Manajemen Personil',
                null, null, null,
                'info',
                "Preview: {$totalUpdate} diupdate, {$totalNew} baru, {$totalError} error, {$totalNoChange} tidak berubah"
            );

            return redirect()->route('admin.personnel.import-update-preview');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file: '.$e->getMessage());
        }
    }

    /**
     * Tampilkan halaman preview hasil parsing file Excel update.
     */
    public function importUpdatePreview()
    {
        $preview = session('update_import_preview');
        $satkerId = session('update_import_satker_id');
        $stats = session('update_import_stats');

        if (! $preview || ! $satkerId) {
            return redirect()->route('admin.personnel.index')
                ->with('error', 'Sesi preview update sudah kadaluwarsa. Silakan upload ulang file.');
        }

        $satker = Satker::find($satkerId);
        $ranks = Rank::orderBy('sort_order')->get();

        return view('admin.personnel.import_update_preview', compact('preview', 'satker', 'stats', 'ranks'));
    }

    /**
     * Konfirmasi import update: simpan data yang statusnya bukan 'skip'.
     */
    public function importUpdateConfirm(Request $request)
    {
        set_time_limit(0);

        $this->abortIfAdminSatkerWriteLocked($request);

        $satkerId = session('update_import_satker_id');
        $preview = session('update_import_preview');

        if (! $satkerId || ! $preview) {
            return redirect()->route('admin.personnel.index')
                ->with('error', 'Sesi preview update sudah kadaluwarsa. Silakan upload ulang file.');
        }

        // Terapkan override rank_id manual dari form
        $rankOverrides = $request->input('rank_overrides', []);
        foreach ($rankOverrides as $index => $rankId) {
            if (isset($preview[$index]) && $rankId !== '' && $rankId !== null) {
                $preview[$index]['rank_id'] = $rankId;
            }
        }

        // Ambil aksi untuk baris duplikat (skip vs import)
        $actionOverrides = $request->input('action_overrides', []);
        foreach ($actionOverrides as $index => $action) {
            if ($action === 'skip' && isset($preview[$index])) {
                // Hapus data dari preview agar tidak diimport/diupdate
                unset($preview[$index]);
            }
        }

        try {
            $importer = new PersonnelUpdateImport((int) $satkerId);
            $results = $importer->saveUpdateFromPreview($preview);

            session()->forget(['update_import_preview', 'update_import_satker_id', 'update_import_stats']);

            AuditLogger::log(
                'Konfirmasi Import Update/Tambah Personil',
                'Manajemen Personil',
                null, null, null,
                'success',
                "Update: {$results['success_count']}. Baru: {$results['new_count']}. Tidak berubah: {$results['no_change_count']}. Gagal: {$results['error_count']}"
            );

            $message = "Berhasil memperbarui {$results['success_count']} data.";
            if (($results['new_count'] ?? 0) > 0) {
                $message .= " {$results['new_count']} personel baru ditambahkan.";
            }
            if (($results['no_change_count'] ?? 0) > 0) {
                $message .= " {$results['no_change_count']} data tidak ada perubahan.";
            }

            if ($results['error_count'] > 0) {
                return redirect()->route('admin.personnel.index')
                    ->with('warning', $message." Gagal: {$results['error_count']}.");
            }

            return redirect()->route('admin.personnel.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan data: '.$e->getMessage());
        }
    }

    /**
     * Batalkan proses import update.
     */
    public function importUpdateCancel()
    {
        $this->abortIfAdminSatkerWriteLocked(request());

        session()->forget(['update_import_preview', 'update_import_satker_id', 'update_import_stats']);

        return redirect()->route('admin.personnel.index')->with('info', 'Proses import update dibatalkan.');
    }

    public function importKeterangan(Request $request)
    {
        $this->ensureSuperadmin();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:51200',
        ]);

        try {
            $this->clearKeteranganPreviewPayload();

            $import = new PersonnelKeteranganImport;
            $collection = Excel::toCollection($import, $request->file('file'));
            $rows = collect();

            foreach ($collection as $sheetRows) {
                $rows = $rows->concat($sheetRows);
            }

            $preview = $import->generatePreview($rows);
            $previewCollection = collect($preview);
            $stats = [
                'update' => $previewCollection->where('status', 'update')->count(),
                'no_change' => $previewCollection->where('status', 'no_change')->count(),
                'error' => $previewCollection->where('status', 'error')->count(),
                'total' => $previewCollection->count(),
            ];

            $previewPath = $this->storeKeteranganPreviewPayload($preview, $stats);

            session([
                'keterangan_import_preview_key' => $previewPath,
                'keterangan_import_stats' => $stats,
            ]);

            AuditLogger::log(
                'Preview Import Keterangan Personel',
                'Manajemen Personil',
                null,
                null,
                null,
                'info',
                'Menyiapkan preview import keterangan personel.'
            );

            return redirect()->route('admin.personnel.import-keterangan-preview');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses file keterangan: '.$e->getMessage());
        }
    }

    public function importKeteranganPreview()
    {
        $this->ensureSuperadmin();

        $payload = $this->getKeteranganPreviewPayload();
        $preview = $payload['preview'] ?? null;
        $stats = $payload['stats'] ?? session('keterangan_import_stats');

        if (! $preview) {
            return redirect()->route('admin.personnel.index')->with('error', 'Sesi preview import keterangan sudah kadaluwarsa. Silakan upload ulang file.');
        }

        return view('admin.personnel.import_keterangan_preview', compact('preview', 'stats'));
    }

    public function importKeteranganConfirm(Request $request)
    {
        $this->ensureSuperadmin();

        $payload = $this->getKeteranganPreviewPayload();
        $preview = $payload['preview'] ?? null;

        if (! $preview) {
            return redirect()->route('admin.personnel.index')->with('error', 'Sesi preview import keterangan sudah kadaluwarsa. Silakan upload ulang file.');
        }

        try {
            $import = new PersonnelKeteranganImport;
            $results = $import->saveFromPreviewData($preview);

            $this->clearKeteranganPreviewPayload();

            AuditLogger::log(
                'Konfirmasi Import Keterangan Personel',
                'Manajemen Personil',
                null,
                null,
                null,
                'success',
                "Update: {$results['updated_count']}. Tidak berubah: {$results['no_change_count']}. Gagal: {$results['error_count']}"
            );

            $message = "Berhasil memperbarui {$results['updated_count']} data keterangan.";

            if ($results['no_change_count'] > 0) {
                $message .= " {$results['no_change_count']} baris tidak ada perubahan.";
            }

            if ($results['error_count'] > 0) {
                return redirect()->route('admin.personnel.index')->with('warning', $message." {$results['error_count']} baris dilewati karena error.");
            }

            return redirect()->route('admin.personnel.index')->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan import keterangan: '.$e->getMessage());
        }
    }

    public function importKeteranganCancel()
    {
        $this->ensureSuperadmin();

        $this->clearKeteranganPreviewPayload();

        return redirect()->route('admin.personnel.index')->with('info', 'Proses import keterangan dibatalkan.');
    }

    /**
     * Import SDM: khusus Super Admin untuk masukkin data pokok awal (termasuk agama).
     */
    public function importSdm(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:xlsx,xls,csv|max:51200',
        ]);

        $user = auth()->user();

        if (! $user->hasRole('superadmin')) {
            $message = 'Hanya Super Admin yang bisa melakukan Impor Data SDM.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 403);
            }

            return redirect()->back()->with('error', $message);
        }

        try {
            $uploadedFiles = $request->file('files', []);
            $importRun = SdmImportRun::create([
                'initiated_by' => $user->id,
                'status' => 'queued',
                'processing_mode' => config('queue.default') === 'sync' ? 'sync' : 'queued',
                'source_files' => $this->stageSdmImportFiles($uploadedFiles),
                'started_at' => now(),
            ]);
            session([
                'sdm_import_run_id' => $importRun->id,
            ]);

            if (config('queue.default') === 'sync') {
                $this->sdmImportRunService->processPreviewRun($importRun);
                $importRun->refresh();

                session([
                    'sdm_import_preview_key' => $importRun->preview_payload_path,
                    'sdm_import_stats' => $importRun->summary ?? [],
                ]);

                $stats = $importRun->summary ?? [];

                AuditLogger::log('Preview Import Data SDM', 'Manajemen Personil', null, null, null, 'info', "Preview SDM: {$stats['ok']} siap, {$stats['corrected']} dikoreksi, {$stats['error']} error");

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Preview import SDM berhasil disiapkan.',
                        'redirect_url' => route('admin.personnel.import-sdm-preview'),
                        'stats' => $stats,
                    ]);
                }

                return redirect()->route('admin.personnel.import-sdm-preview');
            }

            ProcessSdmImportPreview::dispatch($importRun->id);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'File SDM berhasil diunggah dan sedang diproses di background.',
                    'status_url' => route('admin.personnel.import-sdm-runs.status', $importRun),
                    'run_id' => $importRun->id,
                ]);
            }

            return redirect()->route('admin.personnel.index')->with('info', 'Upload SDM sedang diproses di background. Silakan cek Riwayat Import SDM.');
        } catch (\Exception $e) {
            $message = 'Gagal memproses file SDM: '.$e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Tampilkan halaman preview hasil parsing file Excel SDM.
     */
    public function importSdmPreview()
    {
        $payload = $this->getSdmPreviewPayload();
        $preview = $payload['preview'] ?? null;
        $stats = $payload['stats'] ?? session('sdm_import_stats');
        $importRun = $payload['run'] ?? $this->getCurrentSdmImportRun();

        if ($importRun !== null && in_array($importRun->status, ['queued', 'processing'], true)) {
            return redirect()->route('admin.personnel.index')->with('info', 'Preview SDM masih diproses di background. Silakan tunggu beberapa saat.');
        }

        if (! $preview) {
            return redirect()->route('admin.personnel.index')->with('error', 'Sesi preview SDM sudah kadaluwarsa. Silakan upload ulang file.');
        }

        $ranks = Rank::orderBy('sort_order')->get();
        $satkers = Satker::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.personnel.import_sdm_preview', compact('preview', 'stats', 'ranks', 'satkers', 'importRun'));
    }

    /**
     * Konfirmasi import SDM.
     */
    public function importSdmConfirm(Request $request)
    {
        set_time_limit(0);

        $payload = $this->getSdmPreviewPayload();
        $preview = $payload['preview'] ?? null;
        $importRun = $payload['run'] ?? $this->getCurrentSdmImportRun();

        if (! $preview) {
            $message = 'Sesi preview SDM sudah kadaluwarsa. Silakan upload ulang file.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect_url' => route('admin.personnel.index'),
                ], 422);
            }

            return redirect()->route('admin.personnel.index')->with('error', $message);
        }

        $rankOverrides = $request->input('rank_overrides', []);
        foreach ($rankOverrides as $index => $rankId) {
            if (isset($preview[$index]) && $rankId !== '' && $rankId !== null) {
                $preview[$index]['rank_id'] = $rankId;
            }
        }

        $satkerOverrides = $request->input('satker_overrides', []);
        foreach ($satkerOverrides as $index => $satkerId) {
            if (isset($preview[$index]) && $satkerId !== '' && $satkerId !== null) {
                $satker = Satker::find($satkerId);
                if ($satker) {
                    $preview[$index]['satker_id'] = $satker->id;
                    $preview[$index]['satker_name'] = $satker->name;
                }
            }
        }

        $preview = $this->sdmImportRunService->refreshPreviewRows($preview);
        $fileCount = count($importRun?->source_files ?? []);
        $stats = $this->sdmImportRunService->buildStats($preview, $fileCount);

        if ($importRun !== null) {
            $previewPath = $this->sdmImportRunService->storePreviewPayload($importRun, $preview, $stats);
            $errorPath = $this->sdmImportRunService->storeErrorReport($importRun, $preview);

            $importRun->update([
                'status' => 'preview_ready',
                'summary' => $stats,
                'preview_payload_path' => $previewPath,
                'error_report_path' => $errorPath,
            ]);

            session([
                'sdm_import_preview_key' => $previewPath,
                'sdm_import_stats' => $stats,
            ]);
        }

        if (($stats['error'] ?? 0) > 0) {
            $message = "Masih ada {$stats['error']} baris error pada preview SDM. Perbaiki dulu sebelum konfirmasi import.";

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect_url' => route('admin.personnel.import-sdm-preview'),
                    'stats' => $stats,
                ], 422);
            }

            return redirect()->route('admin.personnel.import-sdm-preview')->with('error', $message);
        }

        try {
            $importer = new PersonnelSdmImport;
            $results = $importer->saveFromPreviewData($preview);

            $successCount = $results['success_count'];
            $errorCount = $results['error_count'];
            $notificationType = $errorCount > 0 ? 'warning' : 'success';
            $notificationMessage = $errorCount > 0
                ? "Berhasil mengimpor {$successCount} data SDM. Gagal: {$errorCount}."
                : "Berhasil mengimpor {$successCount} data personil (SDM).";

            if ($importRun !== null) {
                $summary = array_merge($stats, [
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'processing_errors' => count($results['errors'] ?? []),
                ]);

                $errorPath = $this->sdmImportRunService->storeErrorReport($importRun, $preview, $results['errors'] ?? []);

                $importRun->update([
                    'status' => $errorCount > 0 ? 'completed_with_errors' : 'completed',
                    'summary' => $summary,
                    'error_report_path' => $errorPath,
                    'finished_at' => now(),
                ]);
            }

            $this->clearSdmPreviewPayload();

            AuditLogger::log('Konfirmasi Import Data SDM', 'Manajemen Personil', null, null, null, 'success', "Berhasil: {$successCount}. Gagal: {$errorCount}");

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $notificationMessage,
                    'notification' => [
                        'type' => $notificationType,
                        'message' => $notificationMessage,
                    ],
                    'redirect_url' => route('admin.personnel.index'),
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                ]);
            }

            if ($errorCount > 0) {
                return redirect()->route('admin.personnel.index')->with('warning', $notificationMessage);
            }

            return redirect()->route('admin.personnel.index')->with('success', $notificationMessage);
        } catch (\Exception $e) {
            $message = 'Gagal menyimpan data SDM: '.$e->getMessage();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 500);
            }

            return redirect()->back()->with('error', $message);
        }
    }

    /**
     * Batalkan proses import SDM.
     */
    public function importSdmCancel()
    {
        $importRun = $this->getCurrentSdmImportRun();
        if ($importRun !== null && in_array($importRun->status, ['preview_ready', 'processing'], true)) {
            $importRun->update([
                'status' => 'cancelled',
                'finished_at' => now(),
            ]);
        }

        $this->clearSdmPreviewPayload();

        return redirect()->route('admin.personnel.index')->with('info', 'Proses import Data SDM dibatalkan.');
    }

    public function downloadSdmImportErrorReport(SdmImportRun $sdmImportRun)
    {
        $this->ensureSuperadmin();

        if (! $sdmImportRun->error_report_path || ! Storage::disk('local')->exists($sdmImportRun->error_report_path)) {
            abort(404, 'File error report tidak ditemukan.');
        }

        return Storage::disk('local')->download(
            $sdmImportRun->error_report_path,
            'laporan_error_import_sdm_'.$sdmImportRun->id.'.csv'
        );
    }

    public function getSdmImportRunStatus(SdmImportRun $sdmImportRun)
    {
        $this->ensureSuperadmin();

        $elapsedSeconds = $sdmImportRun->started_at?->diffInSeconds(now()) ?? 0;
        $isStaleQueue = $sdmImportRun->status === 'queued' && $elapsedSeconds >= 15;

        return response()->json([
            'status' => $sdmImportRun->status,
            'message' => match ($sdmImportRun->status) {
                'queued' => $isStaleQueue
                    ? "Job preview SDM masih antre lebih dari {$elapsedSeconds} detik. Worker queue kemungkinan belum mengambil job ini."
                    : 'Import SDM masuk antrean dan menunggu diproses.',
                'processing' => "Import SDM sedang diproses di background ({$elapsedSeconds} detik).",
                'preview_ready' => 'Preview SDM siap dibuka.',
                'failed' => $sdmImportRun->summary['error_message'] ?? 'Import SDM gagal diproses.',
                default => 'Status import SDM diperbarui.',
            },
            'redirect_url' => $sdmImportRun->status === 'preview_ready' ? route('admin.personnel.import-sdm-preview') : null,
            'summary' => $sdmImportRun->summary,
            'elapsed_seconds' => $elapsedSeconds,
            'stale_queue' => $isStaleQueue,
        ]);
    }

    /**
     * @return array{preview: array<int, array<string, mixed>>, stats: array<string, mixed>}|null
     */
    private function getSdmPreviewPayload(): ?array
    {
        $run = $this->getCurrentSdmImportRun();
        $path = session('sdm_import_preview_key');
        if ((! is_string($path) || $path === '') && $run?->preview_payload_path) {
            $path = $run->preview_payload_path;
        }
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($path);
        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! isset($payload['preview']) || ! is_array($payload['preview'])) {
            return null;
        }

        return [
            'preview' => $payload['preview'],
            'stats' => is_array($payload['stats'] ?? null) ? $payload['stats'] : (session('sdm_import_stats', [])),
            'run' => $run,
        ];
    }

    private function clearSdmPreviewPayload(): void
    {
        $path = session('sdm_import_preview_key');
        if (is_string($path) && $path !== '') {
            Storage::disk('local')->delete($path);
        }

        session()->forget([
            'sdm_import_run_id',
            'sdm_import_preview_key',
            'sdm_import_preview',
            'sdm_import_stats',
        ]);
    }

    private function getCurrentSdmImportRun(): ?SdmImportRun
    {
        $runId = session('sdm_import_run_id');

        return is_numeric($runId) ? SdmImportRun::find((int) $runId) : null;
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $uploadedFiles
     * @return array<int, array<string, string>>
     */
    private function stageSdmImportFiles(array $uploadedFiles): array
    {
        $staged = [];

        foreach ($uploadedFiles as $index => $uploadedFile) {
            $originalName = $uploadedFile->getClientOriginalName();
            $storedName = $index.'-'.Str::uuid().'-'.$originalName;
            $path = $uploadedFile->storeAs('import-previews/sdm/uploads', $storedName, 'local');

            $staged[] = [
                'original_name' => $originalName,
                'path' => $path,
            ];
        }

        return $staged;
    }

    /**
     * @param  array<int, array<string, mixed>>  $preview
     * @param  array<string, mixed>  $stats
     */
    private function storeKeteranganPreviewPayload(array $preview, array $stats): string
    {
        $path = 'import-previews/keterangan/'.auth()->id().'-'.Str::uuid().'.json';
        $payload = json_encode([
            'preview' => $preview,
            'stats' => $stats,
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new \RuntimeException('Gagal menyusun payload preview import keterangan.');
        }

        Storage::disk('local')->put($path, $payload);

        return $path;
    }

    /**
     * @return array{preview: array<int, array<string, mixed>>, stats: array<string, mixed>}|null
     */
    private function getKeteranganPreviewPayload(): ?array
    {
        $path = session('keterangan_import_preview_key');
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($path);
        $payload = json_decode($raw, true);

        if (! is_array($payload) || ! isset($payload['preview']) || ! is_array($payload['preview'])) {
            return null;
        }

        return [
            'preview' => $payload['preview'],
            'stats' => is_array($payload['stats'] ?? null) ? $payload['stats'] : (session('keterangan_import_stats', [])),
        ];
    }

    private function clearKeteranganPreviewPayload(): void
    {
        $path = session('keterangan_import_preview_key');
        if (is_string($path) && $path !== '') {
            Storage::disk('local')->delete($path);
        }

        session()->forget([
            'keterangan_import_preview_key',
            'keterangan_import_preview',
            'keterangan_import_stats',
        ]);
    }

    /**
     * Export Kapor Recap (Generic and Optimized)
     */
    public function exportRekap(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'item' => 'nullable|string',
        ]);

        $category = $request->query('category');
        $item = $request->query('item');

        $fileName = 'Rekap_'.$category.'_'.($item ? $item.'_' : '').'Polda_NTB_'.date('Y').'.xlsx';

        return Excel::download(new \App\Exports\KaporRekapExport($category, $item), $fileName);
    }

    /**
     * Print Satker PDF Report.
     */

    /**
     * Print Satker PDF Report.
     */
    public function printSatker(Request $request)
    {
        // Pastikan cukup waktu dan memori untuk data besar
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        $request->validate([
            'satker_id' => 'required|exists:satkers,id',
            'fiscal_year' => 'nullable|string',
            'search' => 'nullable|string',
            'bagian' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,submitted',
        ]);

        $satker = Satker::findOrFail($request->satker_id);
        $fiscalYear = $request->get('fiscal_year', Setting::getValue('fiscal_year', date('Y')));

        $query = Personnel::with(['rank', 'submissions' => function ($q) use ($fiscalYear) {
            $q->where('fiscal_year', $fiscalYear)->with('kaporSize', 'kaporItem');
        }])
            ->where('satker_id', $satker->id)
            ->where('is_active', true);

        $this->applyPersonnelLookupFilters($query, $request);

        $personnels = $this->filterPersonnelsByCompletion($query->get(), $request->input('status'));

        // Sort: berdasarkan rank sort_order lalu nama
        $personnels = $personnels->sort(function ($a, $b) {
            $rankA = $a->rank->sort_order ?? 999;
            $rankB = $b->rank->sort_order ?? 999;

            return $rankA !== $rankB ? ($rankA <=> $rankB) : strcasecmp($a->full_name, $b->full_name);
        })->values();

        $kaporItems = KaporItem::where('is_active', true)->orderBy('id')->get();

        $options = [
            'dpi' => 72,        // Turunkan DPI untuk performa
            'defaultFont' => 'Arial',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'isFontSubsettingEnabled' => false,     // Nonaktifkan subsetting untuk performa
            'chroot' => realpath(base_path()),
        ];

        $pdf = Pdf::setOptions($options)->loadView('admin.reports.personnel_satker_pdf', [
            'satker' => $satker,
            'fiscalYear' => $fiscalYear,
            'personnels' => $personnels,
            'kaporItems' => $kaporItems,
            'date' => date('d F Y'),
            'location' => $request->get('location', 'Mataram'),
            'signatory_role' => $request->get('signatory_role', 'KASUBBAG RENMIN KABAG LOG'),
            'signatory_name' => $request->get('signatory_name', '__________________________'),
            'signatory_nrp' => $request->get('signatory_nrp', ''),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = "Data_Personel_{$satker->name}_{$fiscalYear}.pdf";

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Bulk delete personnel by Satker.
     */
    public function bulkDeleteBySatker(Request $request)
    {
        $request->validate([
            'satker_id' => 'required|exists:satkers,id',
            'confirm_text' => 'required|string',
        ]);

        if (strtoupper($request->confirm_text) !== 'HAPUS') {
            return redirect()->back()->with('error', 'Konfirmasi kata kunci salah. Silakan ketik HAPUS untuk melanjutkan.');
        }

        $satker = Satker::findOrFail($request->satker_id);

        try {
            DB::transaction(function () use ($satker) {
                $personnels = Personnel::where('satker_id', $satker->id)->get();
                $count = $personnels->count();

                foreach ($personnels as $personnel) {
                    // Delete submissions
                    $personnel->submissions()->delete();

                    // Delete user account if it exists
                    if ($personnel->user) {
                        $personnel->user->delete();
                    }

                    // Delete personnel
                    $personnel->delete();
                }

                AuditLogger::log('Hapus Bulk Personil', 'Manajemen Personil', $satker, null, null, 'success', "Berhasil menghapus {$count} personil dari Satker: {$satker->name}");
            });

            // Sinkronkan jumlah Polri/PNS di satker setelah bulk delete
            PersonnelImport::recalculateSatkerCount($satker->id);

            return redirect()->back()->with('success', "Berhasil menghapus seluruh data personil dari Satker {$satker->name}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }

    /**
     * Bulk delete ALL personnel.
     */
    public function bulkDeleteAll(Request $request)
    {
        $request->validate([
            'confirm_text' => 'required|string',
        ]);

        if (strtoupper($request->confirm_text) !== 'KOSONGKAN') {
            return redirect()->back()->with('error', 'Konfirmasi kata kunci salah. Silakan ketik KOSONGKAN untuk melanjutkan.');
        }

        try {
            DB::transaction(function () {
                Personnel::chunkById(500, function ($personnels) {
                    foreach ($personnels as $personnel) {
                        // Delete submissions
                        $personnel->submissions()->delete();

                        // Delete user account if it exists
                        if ($personnel->user) {
                            $personnel->user->delete();
                        }

                        // Delete personnel
                        $personnel->delete();
                    }
                });

                AuditLogger::log('Kosongkan Semua Personil', 'Manajemen Personil', null, null, null, 'success', 'Berhasil mengosongkan seluruh database personil.');
            });

            // Sinkronkan jumlah Polri/PNS di semua satker setelah bulk delete all
            $satkers = Satker::all();
            foreach ($satkers as $satker) {
                PersonnelImport::recalculateSatkerCount($satker->id);
            }

            return redirect()->back()->with('success', 'Berhasil mengosongkan seluruh database personil.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengosongkan database: '.$e->getMessage());
        }
    }

    private function ensureSuperadmin(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Hanya Superadmin yang dapat mengakses fitur ini.');
    }

    private function findDuplicatePersonnelIdentity(string $nrpNip, ?Personnel $ignorePersonnel = null, ?User $ignoreUser = null): ?array
    {
        $personnel = Personnel::query()
            ->with(['satker', 'user'])
            ->where('nrp', $nrpNip)
            ->when($ignorePersonnel?->exists, fn ($query) => $query->whereKeyNot($ignorePersonnel->id))
            ->first();

        if ($personnel !== null) {
            return [
                'name' => $this->normalizeIdentityName($personnel->full_name ?: $personnel->user?->name),
                'satker_id' => (int) $personnel->satker_id,
                'satker_name' => $personnel->satker?->name ?? 'Tanpa Satker',
            ];
        }

        $user = User::query()
            ->with('satker')
            ->where('nrp_nip', $nrpNip)
            ->when($ignoreUser?->exists, fn ($query) => $query->whereKeyNot($ignoreUser->id))
            ->first();

        if ($user !== null) {
            return [
                'name' => $this->normalizeIdentityName($user->name),
                'satker_id' => (int) $user->satker_id,
                'satker_name' => $user->satker?->name ?? 'Tanpa Satker',
            ];
        }

        return null;
    }

    private function buildDuplicatePersonnelIdentityMessage(string $nrpNip, array $duplicateIdentity, int $requestedSatkerId): string
    {
        $message = "NRP/NIP {$nrpNip} sudah terdaftar atas nama {$duplicateIdentity['name']} pada satker {$duplicateIdentity['satker_name']}.";

        if ((int) ($duplicateIdentity['satker_id'] ?? 0) !== $requestedSatkerId) {
            $message .= ' Silakan koordinasikan dengan superadmin.';
        }

        return $message;
    }

    private function normalizeIdentityName(?string $name): string
    {
        $normalized = trim((string) $name);

        return $normalized !== '' ? $normalized : 'Tanpa Nama';
    }

    private function abortIfAdminSatkerWriteLocked(?Request $request = null): void
    {
        $request ??= request();

        if (! $request?->user()?->hasRole('admin_satker')) {
            return;
        }

        $lockMessage = SystemLock::resolveLockMessage();
        if ($lockMessage !== null) {
            abort(403, $lockMessage);
        }
    }
}
