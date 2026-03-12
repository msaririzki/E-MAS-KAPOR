<?php

namespace App\Http\Controllers;

use App\Models\KaporItem;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    /**
     * Route to the appropriate dashboard based on user role.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('superadmin')) {
            return $this->superadminDashboard($request);
        }

        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        }

        if ($user->hasRole('admin_satker')) {
            return $this->adminSatkerDashboard($user);
        }

        return $this->personilDashboard($user);
    }

    private function superadminDashboard(Request $request)
    {
        $defaultYear = Setting::getValue('fiscal_year', date('Y'));
        $fiscalYear = $request->get('year', $defaultYear);

        // Get available years for filter (Kept for legacy support or future use)
        $availableYears = [$defaultYear];

        $totalPolri = Satker::sum('polri_count');
        $totalPns = Satker::sum('pns_count');

        // Total personel rill = jumlah record di database (sinkron dengan halaman Personel)
        $totalPersonnel = Personnel::count();

        // Count Personnel who have complete data (kapor_sizes + rank_id + nrp)
        $submittedCount = Personnel::whereNotNull('kapor_sizes')
            ->whereNotNull('rank_id')
            ->whereNotNull('nrp')
            ->count();
        $pendingCount = $totalPersonnel - $submittedCount;
        $fillRate = $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100, 1) : 0;

        // Cek status kunci sistem (Manual & Tanggal)
        $isLocked = Setting::getValue('is_system_locked', 'false') === 'true';
        if (! $isLocked) {
            try {
                $startDate = Carbon::parse(Setting::getValue('input_start_date', date('Y-02-01')))->startOfDay();
                $endDate = Carbon::parse(Setting::getValue('input_end_date', date('Y-08-31')))->endOfDay();
                $now = now();
                if ($now->lessThan($startDate) || $now->greaterThan($endDate)) {
                    $isLocked = true;
                }
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        $stats = [
            'total_users' => User::count(),
            'total_personnel' => $totalPersonnel,
            'total_polri' => $totalPolri,
            'total_pns' => $totalPns,
            'total_satkers' => Satker::count(),
            'total_submissions' => $submittedCount,
            'personnel_submitted' => $submittedCount, // Now consistent
            'personnel_pending' => $pendingCount,
            'fill_rate' => $fillRate,
            'total_kapor_items' => KaporItem::where('is_active', true)->count(),
            'fiscal_year' => $fiscalYear,
            'is_locked' => $isLocked,
        ];

        // Fill rate per satker (top-level)
        $poldaId = Satker::where('code', 'POLDA-NTB')->value('id');
        $satkerStats = Satker::query()
            ->selectRaw('satkers.*')
            ->withCount(['personnels as total_personnel'])
            ->withCount([
                'personnels as submitted_count' => function ($q) {
                    // Sinkron dengan PersonnelController: data lengkap = kapor_sizes + rank_id + nrp
                    $q->whereNotNull('kapor_sizes')
                        ->whereNotNull('rank_id')
                        ->whereNotNull('nrp');
                },
            ])
            ->where(function ($query) use ($poldaId) {
                $query->whereNull('parent_id')->orWhere('parent_id', $poldaId);
            })
            ->orderBy('sort_order')
            ->get();

        // Needs Attention: Incomplete Personnel Limit 5
        $incompletePersonnel = Personnel::with(['satker'])
            ->where(function ($q) {
                $q->whereNull('kapor_sizes')
                    ->orWhereNull('rank_id')
                    ->orWhereNull('nrp');
            })
            ->inRandomOrder() // So it feels dynamic, or we can use latest()
            ->limit(5)
            ->get();

        // Activity Log (Spatie)
        $activities = [];
        if (class_exists(Activity::class)) {
            $activities = Activity::with('causer')
                ->latest()
                ->limit(6)
                ->get();
        }

        return view('dashboard.superadmin', compact(
            'stats',
            'satkerStats',
            'availableYears',
            'fiscalYear',
            'defaultYear',
            'incompletePersonnel',
            'activities'
        ));
    }

    private function adminDashboard()
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        $totalPolri = Satker::sum('polri_count');
        $totalPns = Satker::sum('pns_count');

        $submittedCount = Personnel::whereNotNull('kapor_sizes')
            ->whereNotNull('rank_id')
            ->whereNotNull('nrp')
            ->count();
        $totalPersonnel = Personnel::count();

        $stats = [
            'total_polri' => $totalPolri,
            'total_pns' => $totalPns,
            'total_personnel' => $totalPersonnel,
            'total_satkers' => Satker::count(),
            'total_submissions' => $submittedCount,
            'personnel_submitted' => $submittedCount,
            'personnel_pending' => $totalPersonnel - $submittedCount,
            'fill_rate' => $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100) : 0,
            'fiscal_year' => $fiscalYear,
        ];

        return view('dashboard.admin', compact('stats'));
    }

    private function adminSatkerDashboard(User $user)
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        $satkerId = $user->satker_id;
        $satker = Satker::find($satkerId);

        $totalPolri = $satker->polri_count ?? 0;
        $totalPns = $satker->pns_count ?? 0;

        $totalPersonnel = Personnel::where('satker_id', $satkerId)->count();
        $submittedCount = Personnel::where('satker_id', $satkerId)
            ->whereNotNull('kapor_sizes')
            ->whereNotNull('rank_id')
            ->whereNotNull('nrp')
            ->count();

        $stats = [
            'satker_name' => $satker->name ?? '-',
            'total_polri' => $totalPolri,
            'total_pns' => $totalPns,
            'total_personnel' => $totalPersonnel,
            'personnel_submitted' => $submittedCount,
            'personnel_pending' => $totalPersonnel - $submittedCount,
            'fill_rate' => $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100) : 0,
            'fiscal_year' => $fiscalYear,
        ];

        $pendingPersonnel = Personnel::with(['user', 'rank'])
            ->where('satker_id', $satkerId)
            ->where(function ($q) {
                $q->whereNull('kapor_sizes')
                    ->orWhereNull('rank_id')
                    ->orWhereNull('nrp');
            })
            ->limit(20)
            ->get();

        return view('dashboard.admin-satker', compact('stats', 'pendingPersonnel'));
    }

    private function personilDashboard(User $user)
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        $personnel = $user->personnel;

        $kaporSizes = [];
        $hasSubmitted = false;

        if ($personnel) {
            $kaporSizes = $personnel->kapor_sizes ?? [];
            $hasSubmitted = ! empty($kaporSizes);
        }

        return view('dashboard.personil', compact('user', 'personnel', 'kaporSizes', 'hasSubmitted', 'fiscalYear'));
    }
}
