<?php

namespace App\Http\Controllers;

use App\Models\ItemReview;
use App\Models\PersonnelItemAllocation;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\KaporRequirementService;
use App\Services\PackageSatkerAllocationService;
use App\Support\PeriodGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonilPortalController extends Controller
{
    public function __construct(
        private readonly PackageSatkerAllocationService $packageSatkerAllocationService,
    ) {}

    public function storeKapor(Request $request, KaporRequirementService $kaporRequirementService): RedirectResponse
    {
        $personnel = $request->user()?->personnel;

        if ($personnel === null) {
            return redirect()->route('dashboard')->with('error', 'Data personel Anda belum tersedia di sistem.');
        }

        $mode = $request->input('mode') === 'identity' ? 'identity' : 'sizes';
        $requiresJilbab = $personnel->gender === 'P'
            && strtoupper(trim((string) $personnel->religion)) === 'ISLAM';
        $requiresBagian = ($personnel->satker ?? $request->user()?->satker)?->recipientScope() === 'polres';

        $rules = [
            'jabatan' => 'required|string|max:255',
            'phone' => ['required', 'string', 'max:20', 'regex:/^(?:0|62)\d{8,15}$/'],
        ];

        if ($requiresBagian) {
            $rules['bagian'] = 'required|string|max:255';
        }

        if ($mode === 'sizes') {
            $rules = array_merge($rules, [
                'kemeja' => 'required|string',
                'celana' => 'required|string',
                'olahraga' => 'required|string',
                'jaket' => 'required|string',
                'topi' => 'required|string',
                'sabuk' => 'required|string',
                'sepatu_dinas' => 'required|string',
                'sepatu_olahraga' => 'required|string',
            ]);

            if ($requiresJilbab) {
                $rules['jilbab'] = 'required|string';
            }
        }

        $validated = $request->validate($rules);
        $normalizedPhone = User::normalizePhone($validated['phone'] ?? null);

        $previousIdentity = [
            'jabatan' => $personnel->jabatan,
            'bagian' => $personnel->bagian,
            'phone' => User::normalizePhone($personnel->phone ?: $request->user()?->phone),
        ];

        $nextIdentity = [
            'jabatan' => $this->normalizeIdentityValue($validated['jabatan']),
            'bagian' => $requiresBagian
                ? $this->normalizeIdentityValue($validated['bagian'])
                : $personnel->bagian,
            'phone' => $normalizedPhone,
        ];

        $personnel->jabatan = $nextIdentity['jabatan'];
        $personnel->bagian = $nextIdentity['bagian'];
        $personnel->phone = $nextIdentity['phone'];

        if ($mode === 'sizes') {
            $sizePayload = collect($validated)
                ->except(['jabatan', 'bagian'])
                ->all();

            $currentSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];
            $personnel->kapor_sizes = $kaporRequirementService->sanitizeSubmittedSizes(
                array_merge($currentSizes, $sizePayload),
                $personnel->gender,
            );
        }

        DB::transaction(function () use ($request, $personnel): void {
            $personnel->save();

            $user = $request->user();
            if ($user !== null) {
                $user->forceFill([
                    'phone' => $personnel->phone,
                ])->save();
            }
        });

        $this->logIdentityChanges($personnel, $previousIdentity, $nextIdentity);

        if ($mode === 'identity') {
            return redirect()->to(route('dashboard').'#ukuran-form')
                ->with('success', $requiresBagian
                    ? 'Data jabatan, bag/fungsi, dan no. HP tersimpan. Lanjutkan ke form ukuran kaporlap.'
                    : 'Data jabatan dan no. HP tersimpan. Lanjutkan ke form ukuran kaporlap.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Data ukuran Anda berhasil disimpan dan disinkronkan ke sistem.');
    }

    public function showHistory(Request $request, KaporRequirementService $kaporRequirementService): View
    {
        $personnel = $request->user()?->personnel;
        $kaporSizes = $personnel ? ($personnel->kapor_sizes ?? []) : [];
        $hasSubmitted = ! empty(array_filter((array) $kaporSizes));
        $isComplete = $personnel ? $kaporRequirementService->personnelHasAllRequiredSizes($personnel) : false;

        return view('personil.kapor.history', compact('kaporSizes', 'hasSubmitted', 'isComplete', 'personnel'));
    }

    public function showTestimoni(Request $request): View
    {
        $user = $request->user();
        $activeYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $fiscalYear = (int) $request->get('year', $activeYear);
        $reviewPeriodStatus = PeriodGate::resolveReviewStatus();

        $availableYears = PersonnelItemAllocation::query()
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('fiscal_year')
            ->merge(
                ItemReview::query()
                    ->where('user_id', $user->id)
                    ->distinct()
                    ->pluck('fiscal_year')
            )
            ->push($activeYear)
            ->unique()
            ->sortDesc()
            ->values();

        if (! $availableYears->contains($fiscalYear)) {
            $fiscalYear = (int) $availableYears->first();
        }

        $isHistoricalYear = $fiscalYear !== $activeYear;

        if ($isHistoricalYear) {
            $reviewPeriodStatus = [
                'state' => 'historical',
                'is_open' => false,
                'title' => 'Riwayat Tahun Sebelumnya',
                'message' => 'Tahun anggaran ini sudah tidak aktif. Review item hanya ditampilkan sebagai arsip dan tidak dapat diubah lagi.',
                'period_label' => 'TA '.$fiscalYear.' (arsip)',
                'tone' => 'info',
            ];
        }

        $allocations = PersonnelItemAllocation::query()
            ->with(['kaporItem:id,item_name,category', 'budgetPackage:id,name'])
            ->where('user_id', $user->id)
            ->where('fiscal_year', $fiscalYear)
            ->orderByDesc('allocated_at')
            ->orderByDesc('id')
            ->get();

        $existingReviews = ItemReview::query()
            ->with(['kaporItem:id,item_name,category', 'allocation:id,kapor_item_id,budget_package_name_snapshot'])
            ->where('user_id', $user->id)
            ->where('fiscal_year', $fiscalYear)
            ->orderByDesc('updated_at')
            ->get()
            ->keyBy('kapor_item_id');

        $personnel = $user->personnel;
        $allocationCards = $allocations
            ->groupBy('kapor_item_id')
            ->map(function ($group) use ($existingReviews, $personnel) {
                /** @var \App\Models\PersonnelItemAllocation $allocation */
                $allocation = $group->sortByDesc('allocated_at')->first();
                $review = $existingReviews->get($allocation->kapor_item_id);
                $sizeKey = $this->packageSatkerAllocationService->sizeKeyFor($allocation->kapor_item_name_snapshot);

                return [
                    'allocation' => $allocation,
                    'review' => $review,
                    'item_name' => $allocation->kapor_item_name_snapshot,
                    'item_category' => $allocation->item_category_snapshot,
                    'package_name' => $allocation->budget_package_name_snapshot,
                    'status' => $review?->response_status,
                    'is_reviewed' => $review !== null,
                    'updated_at' => $review?->updated_at,
                    'size_value' => $personnel
                        ? $this->packageSatkerAllocationService->sizeValue($personnel->kapor_sizes, $sizeKey)
                        : '-',
                ];
            })
            ->values();

        $pendingCards = $allocationCards->where('is_reviewed', false)->values();
        $reviewedCards = $allocationCards->where('is_reviewed', true)->values();
        $orphanReviews = $existingReviews
            ->reject(fn (ItemReview $review) => $allocationCards->contains(fn (array $card): bool => (int) $card['allocation']->kapor_item_id === (int) $review->kapor_item_id))
            ->values();

        return view('personil.testimoni.index', compact(
            'fiscalYear',
            'reviewPeriodStatus',
            'allocationCards',
            'pendingCards',
            'reviewedCards',
            'orphanReviews',
            'availableYears',
            'activeYear',
            'isHistoricalYear',
        ));
    }

    public function storeTestimoni(Request $request): RedirectResponse
    {
        $user = $request->user();
        $activeYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $requestedYear = (int) $request->input('year', Setting::getValue('fiscal_year', date('Y')));

        $validated = $request->validate([
            'allocation_id' => 'required|integer',
            'year' => 'nullable|integer',
            'response_status' => 'required|in:'.implode(',', array_keys(ItemReview::RESPONSE_STATUSES)),
            'rating' => 'nullable|integer|min:1|max:5',
            'message' => 'nullable|string|max:2000',
        ]);

        $allocation = PersonnelItemAllocation::query()
            ->whereKey($validated['allocation_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($allocation === null) {
            return redirect()->route('personil.testimoni.index', ['year' => $requestedYear])
                ->with('error_testimoni', 'Item review yang dipilih tidak ditemukan atau sudah tidak tersedia untuk akun Anda.');
        }

        $fiscalYear = (int) $allocation->fiscal_year;

        if ($fiscalYear !== $activeYear) {
            return redirect()->route('personil.testimoni.index', ['year' => $fiscalYear])
                ->with('error_testimoni', 'Tahun anggaran tersebut sudah tidak aktif. Review lama hanya bisa dilihat sebagai riwayat.');
        }

        if ($validated['response_status'] === ItemReview::STATUS_REVIEWED && ! isset($validated['rating'])) {
            return redirect()->route('personil.testimoni.index', ['year' => $fiscalYear])
                ->with('error_testimoni', 'Rating wajib diisi jika Anda memilih status sudah menerima item.');
        }

        $review = ItemReview::updateOrCreate(
            [
                'user_id' => $user->id,
                'kapor_item_id' => $allocation->kapor_item_id,
                'fiscal_year' => $fiscalYear,
            ],
            [
                'personnel_item_allocation_id' => $allocation->id,
                'personnel_id' => $allocation->personnel_id,
                'response_status' => $validated['response_status'],
                'rating' => $validated['response_status'] === ItemReview::STATUS_REVIEWED
                    ? (int) $validated['rating']
                    : null,
                'comment' => filled($validated['message'] ?? null) ? trim((string) $validated['message']) : null,
                'submitted_at' => now(),
            ],
        );

        AuditLogger::log(
            $review->wasRecentlyCreated ? 'Kirim Review Item Kapor' : 'Perbarui Review Item Kapor',
            'Review Kapor',
            $review,
            null,
            [
                'kapor_item' => $allocation->kapor_item_name_snapshot,
                'response_status' => $review->response_status,
                'rating' => $review->rating,
            ],
            'success',
            $review->response_status === ItemReview::STATUS_NOT_RECEIVED
            ? 'Personil melaporkan item kapor belum diterima.'
            : 'Personil mengirim atau memperbarui review item kapor.',
        );

        return redirect()->route('personil.testimoni.index', ['year' => $fiscalYear])
            ->with('success_testimoni', $review->response_status === ItemReview::STATUS_NOT_RECEIVED
                ? 'Laporan item belum diterima berhasil disimpan. Admin dapat menindaklanjuti status penerimaan item tersebut.'
                : 'Review item kapor berhasil disimpan. Anda masih bisa memperbaruinya selama periode review masih berjalan.');
    }

    private function normalizeIdentityValue(string $value): string
    {
        return strtoupper(trim($value));
    }

    private function logIdentityChanges(object $personnel, array $previousIdentity, array $nextIdentity): void
    {
        if ($previousIdentity === $nextIdentity) {
            return;
        }

        AuditLogger::log(
            'Edit Referensi SDM Personil',
            'Data Personil',
            $personnel,
            $previousIdentity,
            $nextIdentity,
            'success',
            'Personil memperbarui jabatan, bag/fungsi, atau no. HP pada profil tahun berjalan.',
        );
    }
}
