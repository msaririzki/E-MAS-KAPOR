<?php

namespace App\Http\Controllers;

use App\Imports\PersonnelImport;
use App\Models\AnnualArchive;
use App\Models\BagianOption;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\InvoiceSetting;
use App\Models\KaporSubmission;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\SdmSatkerAlias;
use App\Models\Setting;
use App\Models\User;
use App\Services\AnnualArchiveService;
use App\Services\AuditLogger;
use App\Services\ExportSignatorySettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AnnualArchiveService $annualArchiveService
    ) {}

    public function index()
    {
        $signatoryService = app(ExportSignatorySettingService::class);

        $settings = [
            'fiscal_year' => Setting::getValue('fiscal_year', date('Y')),
            'is_system_locked' => Setting::getValue('is_system_locked', 'false') === 'true',
            'is_review_locked' => Setting::getValue('is_review_locked', 'false') === 'true',
            'app_name' => Setting::getValue('app_name', 'SI-KAPOR Polda NTB'),
            'input_start_date' => Setting::getValue('input_start_date', date('Y-02-01')),
            'input_end_date' => Setting::getValue('input_end_date', date('Y-08-31')),
            'review_start_date' => Setting::getValue('review_start_date', date('Y-10-01')),
            'review_end_date' => Setting::getValue('review_end_date', date('Y-12-31')),
            'personnel_request_mode' => Setting::getValue('personnel_request_mode', 'auto'),
        ];

        // Build yearly history so archived years stay locked to their saved snapshot.
        $activeYear = $settings['fiscal_year'];

        $budgetYears = BudgetYear::query()
            ->get(['year', 'is_active'])
            ->keyBy('year');

        $annualArchives = AnnualArchive::query()
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('fiscal_year');

        $currentSnapshot = $this->annualArchiveService->buildSnapshot((int) $activeYear);
        $currentSubmissionTotal = KaporSubmission::query()
            ->where('fiscal_year', $activeYear)
            ->count();

        $yearsToShow = array_values(array_unique(array_merge(
            $annualArchives->keys()->all(),
            $budgetYears->keys()->all(),
            [(int) $activeYear]
        )));

        if (! in_array($activeYear, $yearsToShow)) {
            $yearsToShow[] = $activeYear;
        }
        rsort($yearsToShow);

        $yearlyStats = [];
        foreach ($yearsToShow as $year) {
            $budgetYear = $budgetYears->get((int) $year);
            $archiveItems = $annualArchives->get((int) $year);
            $archiveItem = $archiveItems?->first();
            $archiveFiles = (int) ($archiveItems?->count() ?? 0);
            $isActive = ((int) $year === (int) $activeYear) || (bool) ($budgetYear?->is_active);

            if ($isActive) {
                $personnelTotal = (int) ($currentSnapshot['total_personnel'] ?? 0);
                $submittedTotal = (int) ($currentSnapshot['submitted_personnel'] ?? 0);
                $submissionTotal = $currentSubmissionTotal;
                $status = 'Berjalan';
                $snapshotSource = 'Data aktif saat ini';
            } elseif ($archiveItem) {
                $personnelTotal = (int) data_get($archiveItem->metadata, 'total_personnel', 0);
                $submittedTotal = (int) data_get($archiveItem->metadata, 'submitted_personnel', 0);
                $submissionTotal = 0;
                $status = 'Terkunci';
                $snapshotSource = 'Snapshot arsip final';
            } else {
                $personnelTotal = 0;
                $submittedTotal = 0;
                $submissionTotal = 0;
                $status = (int) $year < (int) $activeYear ? 'Belum Diarsipkan' : 'Mendatang';
                $snapshotSource = 'Belum ada snapshot final';
            }

            $yearlyStats[] = (object) [
                'fiscal_year' => $year,
                'personnel_total' => $personnelTotal,
                'submitted_total' => $submittedTotal,
                'submission_total' => $submissionTotal,
                'archive_files' => $archiveFiles,
                'is_active' => $isActive,
                'status' => $status,
                'snapshot_source' => $snapshotSource,
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
        $signatorySettings = $signatoryService->getGlobalSettings();

        return view('superadmin.settings', compact('settings', 'yearlyStats', 'bagianOptions', 'sdmSatkerAliases', 'satkers', 'signatorySettings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'app_name' => 'required|string|max:255',
            'fiscal_year' => 'required|integer|min:2020|max:2099',
            'is_system_locked' => 'nullable|boolean',
            'is_review_locked' => 'nullable|boolean',
            'input_start_date' => 'nullable|date',
            'input_end_date' => 'nullable|date|after_or_equal:input_start_date',
            'review_start_date' => 'nullable|date',
            'review_end_date' => 'nullable|date|after_or_equal:review_start_date',
            'personnel_request_mode' => 'required|in:auto,pending_verification',
        ]);

        Setting::setValue('app_name', $validated['app_name']);
        Setting::setValue('fiscal_year', $validated['fiscal_year']);
        Setting::setValue('is_system_locked', $request->has('is_system_locked') ? 'true' : 'false');
        Setting::setValue('is_review_locked', $request->has('is_review_locked') ? 'true' : 'false');

        if (isset($validated['input_start_date'])) {
            Setting::setValue('input_start_date', $validated['input_start_date']);
        }
        if (isset($validated['input_end_date'])) {
            Setting::setValue('input_end_date', $validated['input_end_date']);
        }
        if (isset($validated['review_start_date'])) {
            Setting::setValue('review_start_date', $validated['review_start_date']);
        }
        if (isset($validated['review_end_date'])) {
            Setting::setValue('review_end_date', $validated['review_end_date']);
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
            Setting::setValue('is_review_locked', 'false');
            Setting::setValue('fiscal_year', $nextYear);
            Setting::setValue('input_start_date', $nextYear.'-02-01');
            Setting::setValue('input_end_date', $nextYear.'-08-31');
            Setting::setValue('review_start_date', $nextYear.'-10-01');
            Setting::setValue('review_end_date', $nextYear.'-12-31');

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
            'Menyiapkan sistem untuk tahun anggaran berikutnya, mengarsipkan hasil final, menonaktifkan akun personel, mengosongkan satker akun personel, dan menghapus dataset aktif personel untuk import ulang.'
        );

        return redirect()->back()->with('success', "Tahun Anggaran $nextYear siap digunakan. Sistem dikunci, paket tahun $currentYear diarsipkan, akun personel dinonaktifkan dan dilepas dari satker, lalu dataset aktif personel direset untuk import ulang SDM.");
    }

    public function updateSignatory(Request $request, ExportSignatorySettingService $signatoryService)
    {
        $validated = $request->validate([
            'signatory_name' => 'nullable|string|max:255',
            'signatory_rank' => 'nullable|string|max:255',
            'signatory_nrp' => 'nullable|string|max:100',
            'signatory_title' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
        ]);

        $oldValues = $signatoryService->getGlobalSettings();
        $signatoryService->updateGlobalSettings($validated);

        InvoiceSetting::getSettings()->update([
            'signatory_name' => $validated['signatory_name'] ?? '',
            'signatory_rank' => $validated['signatory_rank'] ?? '',
            'signatory_nrp' => $validated['signatory_nrp'] ?? '',
            'signatory_title' => $validated['signatory_title'] ?? '',
            'location' => $validated['location'] ?? '',
            'organization_name' => $validated['organization_name'] ?? '',
        ]);

        AuditLogger::log(
            'Update Penanda Tangan Export (Global)',
            'Pengaturan',
            null,
            $oldValues,
            $signatoryService->getGlobalSettings(),
            'success',
            'Superadmin memperbarui konfigurasi penanda tangan export global.',
        );

        return redirect()->back()->with('success', 'Penanda tangan export (global) berhasil diperbarui.');
    }
}
