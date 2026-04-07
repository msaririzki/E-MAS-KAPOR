<?php

namespace App\Http\Controllers\AdminSatker;

use App\Http\Controllers\Controller;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\ExportSignatorySettingService;
use Illuminate\Http\Request;

class AdminSatkerController extends Controller
{
    /**
     * Monitoring — Progres pengisian kapor semua personil di satker.
     */
    public function monitor(Request $request)
    {
        $user = $request->user();
        $satkerId = $user->satker_id;
        $satker = Satker::find($satkerId);
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        // Stats
        $totalPersonnel = Personnel::where('satker_id', $satkerId)->count();
        $submittedCount = Personnel::where('satker_id', $satkerId)
            ->whereNotNull('kapor_sizes')
            ->whereNotNull('rank_id')
            ->whereNotNull('nrp')
            ->count();
        $pendingCount = $totalPersonnel - $submittedCount;
        $fillRate = $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100, 1) : 0;

        $stats = [
            'satker_name' => $satker->name ?? '-',
            'total_personnel' => $totalPersonnel,
            'personnel_submitted' => $submittedCount,
            'personnel_pending' => $pendingCount,
            'fill_rate' => $fillRate,
            'fiscal_year' => $fiscalYear,
        ];

        // All personnel with status
        $query = Personnel::with(['rank', 'satker'])
            ->where('satker_id', $satkerId);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('nrp', 'LIKE', "%{$search}%")
                    ->orWhereHas('rank', fn ($rq) => $rq->where('name', 'LIKE', "%{$search}%"))
                    ->orWhere('jabatan', 'LIKE', "%{$search}%");
            });
        }

        // Status filter: submitted/pending
        if ($request->get('status') === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('kapor_sizes')
                    ->orWhereNull('rank_id')
                    ->orWhereNull('nrp');
            });
        } elseif ($request->get('status') === 'submitted') {
            $query->whereNotNull('kapor_sizes')
                ->whereNotNull('rank_id')
                ->whereNotNull('nrp');
        }

        $personnels = $query->orderBy('full_name')->paginate(20)->withQueryString();

        return view('admin-satker.monitor', compact('stats', 'personnels', 'satker'));
    }

    /**
     * Reports — Laporan data personel & ukuran kapor satker (versi web).
     */
    public function reports(Request $request)
    {
        $user = $request->user();
        $satkerId = $user->satker_id;
        $satker = Satker::find($satkerId);
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        // Get all personnel sorted by rank then name
        $personnels = Personnel::with(['rank'])
            ->where('satker_id', $satkerId)
            ->get()
            ->sort(function ($a, $b) {
                $rankA = $a->rank->sort_order ?? 999;
                $rankB = $b->rank->sort_order ?? 999;

                return $rankA !== $rankB ? ($rankA <=> $rankB) : strcasecmp($a->full_name, $b->full_name);
            })
            ->values();

        // Summary stats
        $total = $personnels->count();
        $pria = $personnels->where('gender', 'L')->count();
        $wanita = $personnels->where('gender', 'P')->count();
        $submitted = $personnels->filter(fn ($p) => $p->kapor_sizes && $p->rank_id && $p->nrp)->count();
        $pending = $total - $submitted;
        $fillRate = $total > 0 ? round(($submitted / $total) * 100, 1) : 0;

        $stats = [
            'satker_name' => $satker->name ?? '-',
            'total_personnel' => $total,
            'personnel_submitted' => $submitted,
            'personnel_pending' => $pending,
            'fill_rate' => $fillRate,
            'fiscal_year' => $fiscalYear,
            'pria' => $pria,
            'wanita' => $wanita,
        ];

        // JSON kapor_sizes key mapping (same as PDF template)
        $jsonMapping = [
            'topi' => 'Tutup Kepala',
            'kemeja' => 'Kemeja',
            'celana' => 'Celana/Rok',
            'olahraga' => 'T-Shirt/Olhrg',
            'sepatu_dinas' => 'Sepatu Dinas',
            'sepatu_olahraga' => 'Sepatu Olhrg',
            'jaket' => 'Jaket',
            'sabuk' => 'Sabuk',
            'jilbab' => 'Jilbab',
        ];

        return view('admin-satker.reports', compact('stats', 'satker', 'fiscalYear', 'personnels', 'jsonMapping'));
    }

    /**
     * Settings — Pengaturan tema untuk Admin Satker.
     */
    public function settings(Request $request, ExportSignatorySettingService $signatoryService)
    {
        $satkerId = (int) $request->user()->satker_id;
        $signatorySettings = $signatoryService->getSatkerSettings($satkerId);

        return view('admin-satker.settings', compact('signatorySettings'));
    }

    public function updateSignatorySettings(Request $request, ExportSignatorySettingService $signatoryService)
    {
        $validated = $request->validate([
            'signatory_name' => 'nullable|string|max:255',
            'signatory_rank' => 'nullable|string|max:255',
            'signatory_nrp' => 'nullable|string|max:100',
            'signatory_title' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
        ]);

        $satkerId = (int) $request->user()->satker_id;
        if ($satkerId <= 0) {
            abort(403, 'Akun Admin Satker tidak memiliki satker yang valid.');
        }

        $oldValues = $signatoryService->getSatkerSettings($satkerId);
        $signatoryService->updateSatkerSettings($satkerId, $validated);

        AuditLogger::log(
            'Update Penanda Tangan Export (Satker)',
            'Pengaturan',
            $request->user()->satker,
            $oldValues,
            $signatoryService->getSatkerSettings($satkerId),
            'success',
            'Admin Satker memperbarui konfigurasi penanda tangan export satker.',
        );

        return redirect()->back()->with('success', 'Penanda tangan export satker berhasil diperbarui.');
    }
}
