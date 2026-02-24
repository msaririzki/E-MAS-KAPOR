<?php

namespace App\Http\Controllers;

use App\Models\KaporItem;
use App\Models\KaporSubmission;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;

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
        $totalPersonnel = $totalPolri + $totalPns;

        // Count Personnel who have kapor_sizes data (Profile Attribute)
        $submittedCount = Personnel::whereNotNull('kapor_sizes')->count();
        $pendingCount = $totalPersonnel - $submittedCount;
        $fillRate = $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100, 1) : 0;

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
            'is_locked' => Setting::getValue('is_system_locked', 'false') === 'true',
        ];

        // Fill rate per satker (top-level)
        $poldaId = Satker::where('code', 'POLDA-NTB')->value('id');
        $satkerStats = Satker::query()
            ->selectRaw('satkers.*, (satkers.polri_count + satkers.pns_count) as total_personnel')
            ->withCount(['personnels as submitted_count' => function ($q) {
            // Check if kapor_sizes is not null
            $q->whereNotNull('kapor_sizes');
        }])
            ->where(function ($query) use ($poldaId) {
            $query->whereNull('parent_id')->orWhere('parent_id', $poldaId);
        })
            ->orderBy('sort_order')
            ->get();

        // Recent users
        $recentUsers = User::with(['roles', 'satker'])
            ->latest()
            ->limit(8)
            ->get();

        return view('dashboard.superadmin', compact('stats', 'satkerStats', 'recentUsers', 'availableYears', 'fiscalYear', 'defaultYear'));
    }

    private function adminDashboard()
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        $submittedCount = Personnel::whereNotNull('kapor_sizes')->count();
        $totalPersonnel = Personnel::count();

        $stats = [
            'total_personnel' => $totalPersonnel,
            'total_satkers' => Satker::count(),
            'total_submissions' => $submittedCount,
            'personnel_submitted' => $submittedCount,
            'personnel_pending' => $totalPersonnel - $submittedCount,
            'fiscal_year' => $fiscalYear,
        ];

        return view('dashboard.admin', compact('stats'));
    }

    private function adminSatkerDashboard(User $user)
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        $satkerId = $user->satker_id;
        $satker = Satker::find($satkerId);

        $totalPersonnel = Personnel::where('satker_id', $satkerId)->count();
        $submittedCount = Personnel::where('satker_id', $satkerId)
            ->whereNotNull('kapor_sizes')
            ->count();

        $stats = [
            'satker_name' => $satker->name ?? '-',
            'total_personnel' => $totalPersonnel,
            'submitted' => $submittedCount,
            'pending' => $totalPersonnel - $submittedCount,
            'fill_rate' => $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100) : 0,
            'fiscal_year' => $fiscalYear,
        ];

        $pendingPersonnel = Personnel::with(['user', 'rank'])
            ->where('satker_id', $satkerId)
            ->whereNull('kapor_sizes')
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
            $hasSubmitted = !empty($kaporSizes);
        }

        return view('dashboard.personil', compact('user', 'personnel', 'kaporSizes', 'hasSubmitted', 'fiscalYear'));
    }
}
