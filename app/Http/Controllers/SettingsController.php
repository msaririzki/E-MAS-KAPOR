<?php

namespace App\Http\Controllers;

use App\Imports\PersonnelImport;
use App\Models\BagianOption;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporSubmission;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\SdmSatkerAlias;
use App\Models\Setting;
use App\Models\User;
use App\Services\AnnualArchiveService;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AnnualArchiveService $annualArchiveService
    ) {}

    public function index()
    {
        $settings = [
            'fiscal_year' => Setting::getValue('fiscal_year', date('Y')),
            'is_system_locked' => Setting::getValue('is_system_locked', 'false') === 'true',
            'app_name' => Setting::getValue('app_name', 'SI-KAPOR Polda NTB'),
            'input_start_date' => Setting::getValue('input_start_date', date('Y-02-01')),
            'input_end_date' => Setting::getValue('input_end_date', date('Y-08-31')),
            'personnel_request_mode' => Setting::getValue('personnel_request_mode', 'auto'),
        ];

        // Get submission stats per year for history
        $activeYear = $settings['fiscal_year'];

        $submissionStats = KaporSubmission::select('fiscal_year', DB::raw('count(*) as total'))
            ->groupBy('fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->get()
            ->keyBy('fiscal_year');

        // Build a comprehensive list of years to show
        $yearsToShow = $submissionStats->keys()->toArray();
        if (! in_array($activeYear, $yearsToShow)) {
            $yearsToShow[] = $activeYear;
        }
        rsort($yearsToShow);

        $yearlyStats = [];
        foreach ($yearsToShow as $year) {
            $yearlyStats[] = (object) [
                'fiscal_year' => $year,
                'total' => $submissionStats[$year]->total ?? 0,
                'is_active' => $year == $activeYear,
                'status' => $year < $activeYear ? 'Selesai' : ($year == $activeYear ? 'Aktif' : 'Mendatang'),
            ];
        }

        $bagianOptions = BagianOption::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $sdmSatkerAliases = SdmSatkerAlias::query()
            ->with('satker:id,name')
            ->orderByDesc('is_active')
            ->latest()
            ->get();
        $satkers = Satker::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']);

        return view('superadmin.settings', compact('settings', 'yearlyStats', 'bagianOptions', 'sdmSatkerAliases', 'satkers'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'is_system_locked' => 'nullable|boolean',
            'input_start_date' => 'nullable|date',
            'input_end_date' => 'nullable|date|after_or_equal:input_start_date',
            'personnel_request_mode' => 'required|in:auto,pending_verification',
        ]);

        Setting::setValue('app_name', $validated['app_name']);
        Setting::setValue('fiscal_year', $validated['fiscal_year']);
        Setting::setValue('is_system_locked', $request->has('is_system_locked') ? 'true' : 'false');

        if (isset($validated['input_start_date'])) {
            Setting::setValue('input_start_date', $validated['input_start_date']);
        }
        if (isset($validated['input_end_date'])) {
            Setting::setValue('input_end_date', $validated['input_end_date']);
        }
        Setting::setValue('personnel_request_mode', $validated['personnel_request_mode']);

        return redirect()->back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }

    /**
     * Transition to next fiscal year
     */
    public function nextYear(Request $request)
    {
        $currentYear = Setting::getValue('fiscal_year', date('Y'));
        $nextYear = $currentYear + 1;
        $satkerIds = Satker::query()->pluck('id')->all();

        $this->annualArchiveService->generateForYear((int) $currentYear, auth()->id());

        DB::transaction(function () use ($currentYear, $nextYear) {
            Setting::setValue('is_system_locked', 'true');
            Setting::setValue('fiscal_year', $nextYear);
            Setting::setValue('input_start_date', $nextYear.'-02-01');
            Setting::setValue('input_end_date', $nextYear.'-08-31');

            BudgetYear::query()->update(['is_active' => false]);

            BudgetYear::updateOrCreate(
                ['year' => $nextYear],
                [
                    'name' => 'Tahun Anggaran '.$nextYear,
                    'is_active' => true,
                ]
            );

            BudgetPackage::query()
                ->whereHas('budgetYear', fn ($query) => $query->where('year', $currentYear))
                ->whereIn('status', ['draft', 'finalized'])
                ->update(['status' => 'archived']);

            $personnelUserIds = Personnel::query()
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->unique()
                ->all();

            if ($personnelUserIds !== []) {
                User::query()
                    ->whereIn('id', $personnelUserIds)
                    ->update([
                        'satker_id' => null,
                        'is_active' => false,
                    ]);
            }

            Personnel::query()
                ->delete();
        });

        foreach ($satkerIds as $satkerId) {
            PersonnelImport::recalculateSatkerCount((int) $satkerId);
        }

        AuditLogger::log(
            'Siapkan Tahun Anggaran Berikutnya',
            'Pengaturan Sistem',
            null,
            null,
            [
                'current_year' => $currentYear,
                'next_year' => $nextYear,
            ],
            'success',
            'Menyiapkan sistem untuk tahun anggaran berikutnya, mengarsipkan hasil final, menonaktifkan akun personel, dan mengosongkan dataset aktif personel.'
        );

        return redirect()->back()->with('success', "Tahun Anggaran $nextYear siap digunakan. Sistem dikunci, paket tahun $currentYear diarsipkan, akun personel tetap tersimpan, dan dataset aktif personel telah dikosongkan.");
    }
}
