<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PersonnelExport;
use App\Exports\PersonnelTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\PersonnelImport;
use App\Imports\PersonnelUpdateImport;
use App\Models\KaporItem;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

class PersonnelController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'latest');
        $direction = $request->get('direction', 'desc');
        $query = Personnel::with(['rank', 'satker'])->forCurrentSatker();

        if ($sort === 'recent_edit') {
            $query->latest('updated_at');
        } elseif ($sort === 'full_name') {
            $query->orderBy('full_name', $direction);
        } elseif ($sort === 'rank') {
            $query->leftJoin('ranks', 'personnels.rank_id', '=', 'ranks.id')
                ->select('personnels.*')
                ->orderBy('ranks.sort_order', $direction === 'asc' ? 'asc' : 'desc')
                ->orderBy('personnels.full_name', 'asc');
        } elseif ($sort === 'satker') {
            $query->leftJoin('satkers', 'personnels.satker_id', '=', 'satkers.id')
                ->select('personnels.*')
                ->orderBy('satkers.name', $direction);
        } elseif ($sort === 'jabatan') {
            $query->orderBy('jabatan', $direction);
        } else {
            $query->latest('created_at');
        }

        // Stats Calculation
        $totalReal = Personnel::forCurrentSatker()->count();

        // Personel "sudah input" = punya kapor_sizes DAN rank_id DAN NRP valid (bukan NULL)
        // DAN ukuran kapor_sizes terisi lengkap
        $submittedCount = Personnel::forCurrentSatker()
            ->whereNotNull('rank_id')
            ->whereNotNull('nrp')
            ->whereNotNull('kapor_sizes')
            ->where(function ($q) {
                // Semua tipe personel (Pria/Wanita) harus isi ini
                $q->whereNotNull('kapor_sizes->topi')
                    ->whereNotNull('kapor_sizes->kemeja')
                    ->whereNotNull('kapor_sizes->celana')
                    ->whereNotNull('kapor_sizes->olahraga')
                    ->whereNotNull('kapor_sizes->sepatu_dinas')
                    ->whereNotNull('kapor_sizes->sepatu_olahraga')
                    ->whereNotNull('kapor_sizes->jaket')
                    ->whereNotNull('kapor_sizes->sabuk');
            })
            ->where(function ($q) {
                // Khusus wanita, wajib isi jilbab
                $q->where('gender', 'L')
                    ->orWhere(function ($q2) {
                        $q2->where('gender', 'P')
                            ->whereNotNull('kapor_sizes->jilbab');
                    });
            })
            ->count();

        $stats = [
            'total_real' => $totalReal,
            'submitted' => $submittedCount,
            'pending' => $totalReal - $submittedCount,
            'active' => Personnel::forCurrentSatker()->where('is_active', true)->count(),
            'nrp_issues' => Personnel::forCurrentSatker()->where('has_nrp_issue', true)->whereNull('nrp_issue_resolved_at')->count(),
        ];

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

        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }

        if ($request->filled('keterangan')) {
            $query->where('keterangan', $request->keterangan);
        }

        // Filter: hanya tampilkan personel dengan data belum lengkap
        // (kapor_sizes NULL, atau ada ukuran yang hilang, ATAU rank_id NULL ATAU NRP NULL)
        $isIncompleteFilter = $request->get('status') === 'incomplete';
        $missingSizeFilter = $request->get('missing_size', ''); // e.g. 'kemeja', 'topi', etc.

        if ($isIncompleteFilter) {
            // Jika ada filter ukuran spesifik, hanya cari personel yang field itu NULL
            if (! empty($missingSizeFilter)) {
                $allowedSizeKeys = ['topi', 'kemeja', 'celana', 'olahraga', 'sepatu_dinas', 'sepatu_olahraga', 'jaket', 'sabuk', 'jilbab'];
                if (in_array($missingSizeFilter, $allowedSizeKeys)) {
                    $query->where(function ($q) use ($missingSizeFilter) {
                        if ($missingSizeFilter === 'jilbab') {
                            // Jilbab hanya wajib untuk perempuan yang BERJILBAB/BERJILBAP
                            $q->where('gender', 'P')
                                ->whereIn('keterangan', ['BERJILBAB', 'BERJILBAP'])
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

            } else {
                // Tidak ada filter spesifik — tampilkan semua yang incomplete
                $query->where(function ($q) {
                    $q->whereNull('personnels.rank_id')
                        ->orWhereNull('personnels.nrp')
                        ->orWhereNull('personnels.kapor_sizes')
                        ->orWhere(function ($q2) {
                            // Jika kapor_sizes ada, cari yang ukurannya bolong / tidak lengkap
                            $q2->whereNotNull('personnels.kapor_sizes')
                                ->where(function ($q3) {
                                    $q3->whereNull('kapor_sizes->topi')
                                        ->orWhereNull('kapor_sizes->kemeja')
                                        ->orWhereNull('kapor_sizes->celana')
                                        ->orWhereNull('kapor_sizes->olahraga')
                                        ->orWhereNull('kapor_sizes->sepatu_dinas')
                                        ->orWhereNull('kapor_sizes->sepatu_olahraga')
                                        ->orWhereNull('kapor_sizes->jaket')
                                        ->orWhereNull('kapor_sizes->sabuk')
                                        ->orWhere(function ($q4) {
                                            $q4->where('gender', 'P')
                                                ->whereNull('kapor_sizes->jilbab');
                                        });
                                });
                        });
                });
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
        $personnels = $query->paginate($perPage)->withQueryString();

        $ranks = Rank::orderBy('sort_order')->get();
        $satkers = Satker::orderBy('sort_order')->orderBy('name')->get();
        $bagians = Personnel::whereNotNull('bagian')->distinct()->pluck('bagian');

        // Note: kaporItems query removed as we now use decoupled JSON sizes in kapor_sizes column

        return view('admin.personnel.index', compact('personnels', 'stats', 'ranks', 'satkers', 'bagians', 'perPage', 'isIncompleteFilter', 'missingSizeFilter'));

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nrp' => 'required|string|unique:personnels,nrp',
            'full_name' => 'required|string|max:255',
            'rank_id' => 'required|exists:ranks,id',
            'satker_id' => 'required|exists:satkers,id',
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

        DB::beginTransaction();
        try {
            // 1. Create User Account
            $user = User::create([
                'name' => $validated['full_name'],
                'nrp_nip' => $validated['nrp'],
                'password' => Hash::make($validated['nrp']), // NRP as default password
                'satker_id' => $validated['satker_id'],
                'is_active' => true,
            ]);
            $user->assignRole('personil');

            // 2. Create Personnel Record
            $personnelData = $validated;
            $personnelData['user_id'] = $user->id;
            $personnel = Personnel::create($personnelData);

            DB::commit();

            // Sinkronkan jumlah Polri/PNS di satker setelah tambah personil
            PersonnelImport::recalculateSatkerCount($validated['satker_id']);

            return redirect()->route('admin.personnel.index')->with('success', 'Data personil dan ukuran berhasil ditambahkan.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menambahkan personil: '.$e->getMessage());
        }
    }

    public function storeMeasurements(Request $request, Personnel $personnel)
    {
        $validated = $request->validate([
            'kapor_sizes' => 'required|array',
        ]);

        try {
            $personnel->update(['kapor_sizes' => $validated['kapor_sizes']]);

            return redirect()->back()->with('success', 'Data ukuran berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan ukuran: '.$e->getMessage());
        }
    }

    public function update(Request $request, Personnel $personnel)
    {
        $validated = $request->validate([
            'nrp' => 'required|string|unique:personnels,nrp,'.$personnel->id,
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

        DB::beginTransaction();
        try {
            // Update Personnel
            $personnel->update($validated);

            // Sync User Account if exists
            if ($personnel->user) {
                $personnel->user->update([
                    'name' => $validated['full_name'],
                    'nrp_nip' => $validated['nrp'],
                    'satker_id' => $validated['satker_id'],
                    'is_active' => $request->has('is_active') ? $request->is_active : $personnel->is_active,
                ]);
            }

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

    public function destroy(Personnel $personnel)
    {
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
        // 1. Cari dulu NRP-NRP apa saja yang bermasalah di satker user saat ini
        $problematicNrps = Personnel::forCurrentSatker()
            ->where('has_nrp_issue', true)
            ->whereNotNull('nrp')
            ->pluck('nrp')
            ->unique()
            ->toArray();

        // [AUTO-CLEANUP] Jika ada NRP bermasalah, tapi ternyata di SELURUH DB 
        // hanya tersisa 1 orang (lawannya sudah dihapus), otomatis tandai selesai!
        if (!empty($problematicNrps)) {
            $nrpCounts = Personnel::whereIn('nrp', $problematicNrps)
                ->select('nrp', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
                ->groupBy('nrp')
                ->pluck('total', 'nrp')
                ->toArray();

            $orphanedNrps = [];
            foreach ($nrpCounts as $nrp => $total) {
                if ($total <= 1) {
                    $orphanedNrps[] = (string) $nrp;
                }
            }

            if (!empty($orphanedNrps)) {
                Personnel::whereIn('nrp', $orphanedNrps)
                    ->where('has_nrp_issue', true)
                    ->update([
                        'has_nrp_issue' => false,
                        'nrp_issue_resolved_at' => now(),
                        'nrp_issue_note' => 'Otomatis terselesaikan sistem: Lawan duplikat sudah dihapus dari database.'
                    ]);
                
                // Update list NRP bermasalah karena sebagian sudah diselesaikan
                // Perlu string compare agar sesuai dengan $problematicNrps (yang mungkin berupa string)
                $problematicNrps = array_values(array_filter($problematicNrps, function($p) use ($orphanedNrps) {
                    return !in_array((string)$p, $orphanedNrps, true);
                }));
            }
        }

        // 2. Jika ada personil bermasalah tapi NRP-nya NULL (blank), ambil ID mereka
        $nullNrpIssuesIds = Personnel::forCurrentSatker()
            ->where('has_nrp_issue', true)
            ->whereNull('nrp')
            ->pluck('id')
            ->toArray();

        // 3. Query SEMUA personil dari SEMUA satker yang NRP-nya masuk dalam daftar bermasalah yang TERSISA
        $personnels = Personnel::with(['rank', 'satker'])
            ->where(function ($query) use ($problematicNrps, $nullNrpIssuesIds) {
                if (!empty($problematicNrps)) {
                    $query->whereIn('nrp', $problematicNrps);
                }
                if (!empty($nullNrpIssuesIds)) {
                    $query->orWhereIn('id', $nullNrpIssuesIds);
                }
                
                if (empty($problematicNrps) && empty($nullNrpIssuesIds)) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('nrp')
            ->latest()
            ->paginate(50);

        return view('admin.personnel.nrp_issues', compact('personnels'));
    }

    /**
     * Tandai masalah NRP selesai direview.
     */
    public function resolveNrpIssue(Request $request, Personnel $personnel)
    {
        try {
            $personnel->update([
                'has_nrp_issue' => false,
                'nrp_issue_resolved_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Status masalah duplikat NRP berhasil ditandai selesai.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses resolusi: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel template for personnel import.
     */
    public function downloadTemplate()
    {
        return Excel::download(new PersonnelTemplateExport, 'template_import_personil.xlsx');
    }

    /**
     * Import personnel — hanya baca file, simpan preview ke session, redirect ke halaman preview.
     */
    public function import(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

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
                $sheetPreview = $import->generatePreview($sheetRows);
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

        return Excel::download(new PersonnelExport($satkerIds, $satkerName), $fileName);
    }

    /**
     * Import UPDATE: baca file Excel, cocokkan via NRP, tampilkan preview.
     */
    public function importUpdate(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

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
        session()->forget(['update_import_preview', 'update_import_satker_id', 'update_import_stats']);

        return redirect()->route('admin.personnel.index')->with('info', 'Proses import update dibatalkan.');
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
        ]);

        $satker = Satker::findOrFail($request->satker_id);
        $fiscalYear = $request->get('fiscal_year', Setting::getValue('fiscal_year', date('Y')));

        $personnels = Personnel::with(['rank', 'submissions' => function ($q) use ($fiscalYear) {
            $q->where('fiscal_year', $fiscalYear)->with('kaporSize', 'kaporItem');
        }])
            ->where('satker_id', $satker->id)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

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
}
