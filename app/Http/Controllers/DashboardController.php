<?php

namespace App\Http\Controllers;

use App\Models\BagianOption;
use App\Models\ItemReview;
use App\Models\KaporItem;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use App\Services\KaporRequirementService;
use App\Services\SatkerPersonnelCountService;
use App\Services\TestimonialInsightService;
use App\Support\PeriodGate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService,
        private readonly SatkerPersonnelCountService $satkerPersonnelCountService,
        private readonly TestimonialInsightService $testimonialInsightService,
    ) {}

    /**
     * Route to the appropriate dashboard based on user role.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('superadmin')) {
            return $this->superadminDashboard($request);
        }

        if ($user->hasRole('admin_gudang')) {
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

        $globalCounts = $this->satkerPersonnelCountService->getGlobalCounts();
        $totalPolri = $globalCounts['polri_count'];
        $totalPns = $globalCounts['pns_count'];
        $totalPersonnel = $globalCounts['total_personnel'];

        $submittedCount = $this->countPersonnelWithCompleteSizes();
        $pendingCount = $totalPersonnel - $submittedCount;
        $fillRate = $totalPersonnel > 0 ? round(($submittedCount / $totalPersonnel) * 100, 1) : 0;

        // Cek status kunci sistem (Manual & Tanggal)
        $isLocked = ! (PeriodGate::resolveInputStatus()['is_open'] ?? true);

        $stats = [
            'total_users' => User::count(),
            'total_personnel' => $totalPersonnel,
            'total_polri' => $totalPolri,
            'total_pns' => $totalPns,
            'total_satkers' => Satker::count(),
            'total_submissions' => $submittedCount,
            'personnel_submitted' => $submittedCount,
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
                    $q->whereNotNull('kapor_sizes');
                },
            ])
            ->where(function ($query) use ($poldaId) {
                $query->whereNull('parent_id')->orWhere('parent_id', $poldaId);
            })
            ->orderBy('sort_order')
            ->get();

        $satkerStats->transform(function (Satker $satker) {
            $satker->submitted_count = $this->countPersonnelWithCompleteSizes($satker->id);

            return $satker;
        });

        // Needs Attention: Incomplete Personnel Limit 5
        $incompletePersonnel = Personnel::with(['satker'])
            ->select(['id', 'full_name', 'nrp', 'satker_id', 'gender', 'kapor_sizes', 'keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4'])
            ->inRandomOrder()
            ->get()
            ->filter(fn (Personnel $personnel) => ! $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel))
            ->take(5)
            ->values();

        // Activity Log (Spatie)
        $activities = [];
        $activityModel = '\\Spatie\\Activitylog\\Models\\Activity';
        if (class_exists($activityModel)) {
            $activities = $activityModel::with('causer')
                ->latest()
                ->limit(6)
                ->get();
        }

        $testimonialInsights = $this->testimonialInsightService->getStatistics();

        return view('dashboard.superadmin', compact(
            'stats',
            'satkerStats',
            'availableYears',
            'fiscalYear',
            'defaultYear',
            'incompletePersonnel',
            'activities',
            'testimonialInsights',
        ));
    }

    private function adminDashboard()
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        $globalCounts = $this->satkerPersonnelCountService->getGlobalCounts();
        $totalPolri = $globalCounts['polri_count'];
        $totalPns = $globalCounts['pns_count'];
        $totalPersonnel = $globalCounts['total_personnel'];
        $submittedCount = $this->countPersonnelWithCompleteSizes();

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

        $satkerCounts = $this->satkerPersonnelCountService->getCountsForSatker($satkerId);
        $totalPolri = $satkerCounts['polri_count'];
        $totalPns = $satkerCounts['pns_count'];
        $totalPersonnel = $satkerCounts['total_personnel'];
        $submittedCount = $this->countPersonnelWithCompleteSizes($satkerId);

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
            ->select(['id', 'user_id', 'rank_id', 'satker_id', 'full_name', 'nrp', 'gender', 'kapor_sizes', 'keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4'])
            ->get()
            ->filter(fn (Personnel $personnel) => ! $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel))
            ->take(20)
            ->values();

        return view('dashboard.admin-satker', compact('stats', 'pendingPersonnel'));
    }

    private function personilDashboard(User $user)
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        $personnel = $user->personnel;
        $inputPeriodStatus = PeriodGate::resolveInputStatus();
        $reviewPeriodStatus = PeriodGate::resolveReviewStatus();
        $bagianOptions = BagianOption::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');

        $kaporSizes = [];
        $hasSubmitted = false;
        $isComplete = false;
        $identityReady = false;
        $requiresBagian = false;
        $contactPhone = User::normalizePhone($user->phone);
        $latestReview = ItemReview::query()
            ->where('user_id', $user->id)
            ->where('fiscal_year', (int) $fiscalYear)
            ->latest()
            ->first();
        $eligibleItems = PersonnelItemAllocation::query()
            ->where('user_id', $user->id)
            ->where('fiscal_year', (int) $fiscalYear)
            ->distinct()
            ->count('kapor_item_id');
        $eligibleReviewItems = PersonnelItemAllocation::query()
            ->where('user_id', $user->id)
            ->where('fiscal_year', (int) $fiscalYear)
            ->orderBy('kapor_item_name_snapshot')
            ->pluck('kapor_item_name_snapshot')
            ->unique()
            ->values();
        $reviewedItems = ItemReview::query()
            ->where('user_id', $user->id)
            ->where('fiscal_year', (int) $fiscalYear)
            ->count();
        $pendingReviewItems = max($eligibleItems - $reviewedItems, 0);

        if ($personnel) {
            $kaporSizes = $personnel->kapor_sizes ?? [];
            $hasSubmitted = ! empty(array_filter((array) $kaporSizes));
            $isComplete = $this->kaporRequirementService->personnelHasAllRequiredSizes($personnel);
            $requiresBagian = ($personnel->satker ?? $user->satker)?->recipientScope() === 'polres';
            $contactPhone = User::normalizePhone($personnel->phone ?: $user->phone);
            $identityReady = filled(trim((string) $personnel->jabatan))
                && (! $requiresBagian || filled(trim((string) $personnel->bagian)))
                && filled(trim((string) $contactPhone));
        }

        $reviewPrompt = [
            'title' => 'Belum Ada Item Review',
            'message' => 'Daftar item review akan muncul setelah ada paket pengadaan finalized yang menempatkan item kapor untuk akun Anda.',
            'action_label' => 'Lihat Halaman Review',
            'action' => 'testimoni',
            'tone' => 'info',
        ];

        if ($eligibleItems === 0) {
            $reviewPrompt = [
                'title' => 'Belum Ada Item Review',
                'message' => 'Daftar item review akan muncul setelah ada paket pengadaan finalized yang menempatkan item kapor untuk akun Anda.',
                'action_label' => 'Lihat Halaman Review',
                'action' => 'testimoni',
                'tone' => 'info',
            ];
        } elseif (! ($reviewPeriodStatus['is_open'] ?? true)) {
            $reviewPrompt = [
                'title' => 'Review Sementara Mode Baca Saja',
                'message' => 'Halaman review tetap bisa dibuka untuk melihat item dan riwayat respons, tetapi pengiriman baru mengikuti status periode review yang sedang berlaku.',
                'action_label' => 'Lihat Halaman Review',
                'action' => 'testimoni',
                'tone' => 'info',
            ];
        } elseif ($pendingReviewItems > 0) {
            $reviewPrompt = [
                'title' => 'Ada '.$pendingReviewItems.' Item Menunggu Respons',
                'message' => 'Anda dapat memberi review item yang sudah diterima atau melaporkan item yang belum sampai agar admin bisa memantau distribusi.',
                'action_label' => 'Buka Halaman Review',
                'action' => 'testimoni',
                'tone' => 'warning',
            ];
        } else {
            $reviewPrompt = [
                'title' => 'Semua Item Sudah Direspons',
                'message' => 'Anda sudah merespons semua item review yang saat ini tersedia. Jika ada perubahan pengalaman, Anda masih bisa memperbarui review selama periode review belum ditutup.',
                'action_label' => 'Kelola Review',
                'action' => 'testimoni',
                'tone' => 'success',
            ];
        }

        return view('dashboard.personil', compact(
            'user',
            'personnel',
            'kaporSizes',
            'hasSubmitted',
            'isComplete',
            'identityReady',
            'requiresBagian',
            'fiscalYear',
            'bagianOptions',
            'contactPhone',
            'inputPeriodStatus',
            'reviewPeriodStatus',
            'latestReview',
            'eligibleItems',
            'eligibleReviewItems',
            'reviewedItems',
            'pendingReviewItems',
            'reviewPrompt',
        ));
    }

    private function countPersonnelWithCompleteSizes(?int $satkerId = null): int
    {
        $query = Personnel::query()
            ->select(['id', 'gender', 'kapor_sizes', 'keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4']);

        if ($satkerId !== null) {
            $query->where('satker_id', $satkerId);
        }

        $count = 0;

        foreach ($query->cursor() as $personnel) {
            if ($this->kaporRequirementService->personnelHasAllRequiredSizes($personnel)) {
                $count++;
            }
        }

        return $count;
    }
}
