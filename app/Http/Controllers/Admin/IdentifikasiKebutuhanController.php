<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\KebutuhanItem;
use App\Models\Satker;
use App\Models\Setting;
use App\Services\ExportSignatorySettingService;
use App\Services\IdentifikasiKebutuhanWordExportService;
use App\Services\KebutuhanExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IdentifikasiKebutuhanController extends Controller
{
    /**
     * Semua pengajuan kebutuhan untuk Superadmin.
     */
    public function index(Request $request)
    {
        $activeFiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $targetFiscalYear = (int) Setting::getValue('kebutuhan_target_year', 0);
        $targetFiscalYear = $targetFiscalYear > 0 ? $targetFiscalYear : $activeFiscalYear + 1;
        $selectedYear = $request->integer('year') ?: $targetFiscalYear;

        $query = Kebutuhan::with(['satker', 'user', 'items'])
            ->where('fiscal_year', (string) $selectedYear)
            ->orderByDesc('created_at');

        // Filter by satker
        if ($request->filled('satker_id')) {
            $query->where('satker_id', $request->satker_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhereHas('satker', fn ($sq) => $sq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        $kebutuhans = $query->paginate(15)->withQueryString();

        $satkers = Satker::orderBy('name')->get();
        $availableYears = Kebutuhan::query()
            ->select('fiscal_year')
            ->distinct()
            ->pluck('fiscal_year')
            ->map(fn ($year): int => (int) $year)
            ->merge([$activeFiscalYear, $targetFiscalYear])
            ->filter(fn (int $year): bool => $year > 0)
            ->unique()
            ->sortDesc()
            ->values();

        $baseYearQuery = Kebutuhan::query()
            ->where('fiscal_year', (string) $selectedYear);

        // Stats
        $stats = [
            'totalPengajuan' => (clone $baseYearQuery)->count(),
            'totalSatker' => (clone $baseYearQuery)->distinct('satker_id')->count('satker_id'),
            'totalItem' => KebutuhanItem::query()
                ->join('kebutuhans', 'kebutuhans.id', '=', 'kebutuhan_items.kebutuhan_id')
                ->where('kebutuhans.fiscal_year', (string) $selectedYear)
                ->distinct('identifikasi_item_id')
                ->count('identifikasi_item_id'),
        ];

        // ── Item Popularity Statistics ─────────────────────────────
        $totalKebutuhans = Satker::count();

        // Top 10 items per category
        $itemStatsByCategory = collect();
        if ($totalKebutuhans > 0) {
            $categories = IdentifikasiItem::where('is_active', true)
                ->select('category')
                ->distinct()
                ->orderByRaw("CASE
                    WHEN category = 'Tutup_Kepala' THEN 1
                    WHEN category = 'Tutup_Badan' THEN 2
                    WHEN category = 'Tutup_Kaki' THEN 3
                    ELSE 999 END")
                ->pluck('category');

            foreach ($categories as $category) {
                $categoryItemIds = IdentifikasiItem::where('is_active', true)
                    ->where('category', $category)
                    ->pluck('id');

                $topItems = KebutuhanItem::query()
                    ->select('identifikasi_item_id', DB::raw('COUNT(DISTINCT kebutuhans.satker_id) as satker_count'))
                    ->join('kebutuhans', 'kebutuhans.id', '=', 'kebutuhan_items.kebutuhan_id')
                    ->whereIn('identifikasi_item_id', $categoryItemIds)
                    ->where('kebutuhans.fiscal_year', (string) $selectedYear)
                    ->whereIn('kebutuhans.status', ['diajukan', 'disetujui'])
                    ->groupBy('identifikasi_item_id')
                    ->get()
                    ->map(function ($row) use ($totalKebutuhans) {
                        $item = IdentifikasiItem::find($row->identifikasi_item_id);
                        // Gunakan eligible_satker_count sebagai penyebut statistik, jika tidak diisi pakai total satker.
                        $eligible = $item?->eligible_satker_count ?? $totalKebutuhans;

                        return [
                            'item_name' => $item?->item_name ?? '-',
                            'satker_count' => (int) $row->satker_count,
                            'percentage' => (int) round(($row->satker_count / max($eligible, 1)) * 100),
                        ];
                    })
                    ->sort(function (array $a, array $b): int {
                        return $b['percentage'] <=> $a['percentage']
                            ?: $b['satker_count'] <=> $a['satker_count']
                            ?: strnatcasecmp($a['item_name'], $b['item_name']);
                    })
                    ->values();

                if ($topItems->isNotEmpty()) {
                    $itemStatsByCategory[$category] = $topItems;
                }
            }
        }

        return view('admin.identifikasi-kebutuhan.index', compact(
            'kebutuhans',
            'satkers',
            'stats',
            'itemStatsByCategory',
            'totalKebutuhans',
            'selectedYear',
            'targetFiscalYear',
            'activeFiscalYear',
            'availableYears',
        ));
    }

    /**
     * Detail pengajuan kebutuhan.
     */
    public function show(Kebutuhan $kebutuhan)
    {
        $kebutuhan->load(['satker', 'user', 'reviewer', 'items.identifikasiItem']);

        return view('admin.identifikasi-kebutuhan.show', compact('kebutuhan'));
    }

    public function exportPdf(
        Request $request,
        KebutuhanExportService $exportService,
        ExportSignatorySettingService $signatoryService
    ) {
        $data = $exportService->build($request->integer('year') ?: null);
        $data['signatorySettings'] = $signatoryService->resolveForCurrentUser();

        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('admin.identifikasi-kebutuhan.export-pdf', $data, [], [
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'DejaVu Sans',
            'shrink_tables_to_fit' => 0,
        ]);

        return $pdf->download('Identifikasi_Kebutuhan_Kapor_TA_'.$data['fiscalYear'].'.pdf');
    }

    public function exportDetailPdf(
        Request $request,
        KebutuhanExportService $exportService,
        ExportSignatorySettingService $signatoryService
    ) {
        $data = $exportService->build($request->integer('year') ?: null, true); // true for $includeSatkers
        $data['signatorySettings'] = $signatoryService->resolveForCurrentUser();

        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('admin.identifikasi-kebutuhan.export-detail-pdf', $data, [], [
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'DejaVu Sans',
            'shrink_tables_to_fit' => 0,
        ]);

        return $pdf->download('Detail_Identifikasi_Kebutuhan_Kapor_TA_'.$data['fiscalYear'].'.pdf');
    }

    public function exportWord(
        Request $request,
        KebutuhanExportService $exportService,
        ExportSignatorySettingService $signatoryService,
        IdentifikasiKebutuhanWordExportService $wordExportService
    ) {
        $data = $exportService->build($request->integer('year') ?: null);
        $data['signatorySettings'] = $signatoryService->resolveForCurrentUser();

        $tempFile = $wordExportService->generate($data, false);
        $fileName = 'Identifikasi_Kebutuhan_Kapor_TA_'.$data['fiscalYear'].'.docx';

        return response()
            ->download($tempFile, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    public function exportDetailWord(
        Request $request,
        KebutuhanExportService $exportService,
        ExportSignatorySettingService $signatoryService,
        IdentifikasiKebutuhanWordExportService $wordExportService
    ) {
        $data = $exportService->build($request->integer('year') ?: null, true);
        $data['signatorySettings'] = $signatoryService->resolveForCurrentUser();

        $tempFile = $wordExportService->generate($data, true);
        $fileName = 'Detail_Identifikasi_Kebutuhan_Kapor_TA_'.$data['fiscalYear'].'.docx';

        return response()
            ->download($tempFile, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * Setujui pengajuan.
     */
    public function approve(Request $request, Kebutuhan $kebutuhan)
    {
        if (! $request->user()->hasRole('superadmin')) {
            abort(403, 'Hanya Superadmin yang dapat menyetujui pengajuan.');
        }

        if (! $kebutuhan->isDiajukan()) {
            return back()->with('error', 'Hanya pengajuan berstatus "Diajukan" yang dapat disetujui.');
        }

        $kebutuhan->update([
            'status' => 'disetujui',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pengajuan kebutuhan berhasil disetujui.');
    }

    /**
     * Tolak pengajuan.
     */
    public function reject(Request $request, Kebutuhan $kebutuhan)
    {
        if (! $request->user()->hasRole('superadmin')) {
            abort(403, 'Hanya Superadmin yang dapat menolak pengajuan.');
        }

        if (! $kebutuhan->isDiajukan()) {
            return back()->with('error', 'Hanya pengajuan berstatus "Diajukan" yang dapat ditolak.');
        }

        $request->validate([
            'admin_notes' => 'required|string|max:2000',
        ], [
            'admin_notes.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $kebutuhan->update([
            'status' => 'ditolak',
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pengajuan kebutuhan telah ditolak.');
    }

    /**
     * Hapus pengajuan kebutuhan (Superadmin only).
     */
    public function destroy(Request $request, Kebutuhan $kebutuhan)
    {
        if (! $request->user()->hasRole('superadmin')) {
            abort(403, 'Hanya Superadmin yang dapat menghapus pengajuan ini.');
        }

        try {
            DB::transaction(function () use ($kebutuhan) {
                // Hapus item-item terkait terlebih dahulu
                $kebutuhan->items()->delete();

                // Kemudian hapus kebutuhan
                $kebutuhan->delete();
            });

            // Log action jika AuditLogger digunakan
            if (class_exists(\App\Services\AuditLogger::class)) {
                \App\Services\AuditLogger::log(
                    action: 'delete_kebutuhan',
                    category: 'Identifikasi Kebutuhan',
                    model: $kebutuhan,
                    details: "Menghapus pengajuan kebutuhan secara permanen: {$kebutuhan->title} (Satker: ".($kebutuhan->satker->name ?? '-').')'
                );
            }

            return back()->with('success', 'Pengajuan kebutuhan berhasil dihapus secara permanen.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus pengajuan kebutuhan: '.$e->getMessage());
        }
    }
}
