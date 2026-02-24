<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PersonnelTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\PersonnelImport;
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

        // Calculate submitted count based on kapor_sizes column availability
        $submittedCount = Personnel::forCurrentSatker()->whereNotNull('kapor_sizes')->count();

        $stats = [
            'total_real' => $totalReal,
            'submitted' => $submittedCount,
            'pending' => $totalReal - $submittedCount,
            'active' => Personnel::forCurrentSatker()->where('is_active', true)->count(),
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

        // Pagination
        $perPage = $request->get('per_page', 10);
        $personnels = $query->paginate($perPage)->withQueryString();

        $ranks = Rank::orderBy('sort_order')->get();
        $satkers = Satker::orderBy('name')->get();
        $bagians = Personnel::whereNotNull('bagian')->distinct()->pluck('bagian');

        // Note: kaporItems query removed as we now use decoupled JSON sizes in kapor_sizes column

        return view('admin.personnel.index', compact('personnels', 'stats', 'ranks', 'satkers', 'bagians', 'perPage'));
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
            'is_active' => 'nullable|boolean',
            'kapor_sizes' => 'nullable|array',
        ]);

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

            return redirect()->back()->with('success', 'Data personil dan akun berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal memperbarui: '.$e->getMessage());
        }
    }

    public function destroy(Personnel $personnel)
    {
        DB::beginTransaction();
        try {
            if ($personnel->user) {
                $personnel->user->delete();
            }
            $personnel->delete();

            DB::commit();

            return redirect()->back()->with('success', 'Personil dan akun terkait berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menghapus: '.$e->getMessage());
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
        ini_set('memory_limit', '512M');

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:51200', // max 50MB
            'satker_id' => 'required|exists:satkers,id',
        ]);

        try {
            $import = new PersonnelImport($request->satker_id);
            $collection = Excel::toCollection($import, $request->file('file'));

            // Gabungkan semua sheet menjadi satu koleksi baris data
            $dataRows = collect();
            foreach ($collection as $sheetIndex => $sheetRows) {
                // $sheetRows sudah dipotong 10 baris pertama oleh WithStartRow(11) di Import class
                // Jangan dislice(10) lagi karena akan menghilangkan 10 data pertama di tiap sheet!
                $dataRows = $dataRows->concat($sheetRows);
            }

            $preview = $import->generatePreview($dataRows);

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

        // Validasi: pastikan tidak ada baris error yang rank_id-nya masih kosong
        $missing = [];
        foreach ($preview as $i => $row) {
            if ($row['status'] === 'error' && empty($row['rank_id'])) {
                $missing[] = ($row['full_name'] ?? "Baris #{$i}");
            }
        }

        if (! empty($missing)) {
            return redirect()->back()
                ->with('error', 'Masih ada '.count($missing).' baris yang belum dipilih pangkatnya: '.implode(', ', array_slice($missing, 0, 5)));
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
        ini_set('memory_limit', '512M');

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

            return redirect()->back()->with('success', "Berhasil menghapus seluruh data personil dari Satker {$satker->name}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }
}
