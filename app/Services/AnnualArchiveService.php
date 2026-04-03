<?php

namespace App\Services;

use App\Exports\PersonnelExport;
use App\Models\AnnualArchive;
use App\Models\BudgetPackage;
use App\Models\Personnel;
use App\Models\Satker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AnnualArchiveService
{
    /**
     * @return array<int, AnnualArchive>
     */
    public function generateForYear(int $fiscalYear, ?int $generatedBy = null): array
    {
        $disk = 'local';
        $basePath = 'annual-archives/'.$fiscalYear;
        $generatedAt = Carbon::now();

        $snapshot = $this->buildSnapshot($fiscalYear);

        $excelFileName = 'arsip_final_tahunan_'.$fiscalYear.'.xlsx';
        $excelPath = $basePath.'/'.$excelFileName;
        Excel::store(new PersonnelExport(null, 'SEMUA SATKER'), $excelPath, $disk);

        $pdfFileName = 'arsip_final_tahunan_'.$fiscalYear.'.pdf';
        $pdfPath = $basePath.'/'.$pdfFileName;
        $pdfContent = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('admin.reports.annual_archive_pdf', [
            'snapshot' => $snapshot,
            'fiscalYear' => $fiscalYear,
            'generatedAt' => $generatedAt,
        ])->output();
        Storage::disk($disk)->put($pdfPath, $pdfContent);

        return [
            $this->storeArchiveRecord($fiscalYear, 'xlsx', $excelFileName, $excelPath, $disk, $generatedAt, $generatedBy, $snapshot),
            $this->storeArchiveRecord($fiscalYear, 'pdf', $pdfFileName, $pdfPath, $disk, $generatedAt, $generatedBy, $snapshot),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSnapshot(int $fiscalYear): array
    {
        $satkerSummaries = Satker::withCount(['personnels as total_personnel'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (Satker $satker) {
                $submittedCount = Personnel::query()
                    ->where('satker_id', $satker->id)
                    ->whereNotNull('kapor_sizes')
                    ->get()
                    ->filter(fn (Personnel $personnel) => ! empty($personnel->kapor_sizes))
                    ->count();

                return [
                    'satker_name' => $satker->name,
                    'total_personnel' => $satker->total_personnel,
                    'submitted_count' => $submittedCount,
                    'pending_count' => max(0, $satker->total_personnel - $submittedCount),
                ];
            })
            ->all();

        $budgetPackages = BudgetPackage::query()
            ->with('budgetYear')
            ->whereHas('budgetYear', fn ($query) => $query->where('year', $fiscalYear))
            ->orderBy('name')
            ->get()
            ->map(fn (BudgetPackage $package) => [
                'name' => $package->name,
                'status' => $package->status_label,
                'total_budget' => $package->formatted_budget,
                'items_count' => $package->items()->count(),
            ])
            ->all();

        return [
            'total_personnel' => Personnel::count(),
            'submitted_personnel' => Personnel::query()
                ->get()
                ->filter(fn (Personnel $personnel) => ! empty($personnel->kapor_sizes))
                ->count(),
            'satker_summaries' => $satkerSummaries,
            'budget_packages' => $budgetPackages,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    private function storeArchiveRecord(int $fiscalYear, string $format, string $fileName, string $filePath, string $disk, Carbon $generatedAt, ?int $generatedBy, array $snapshot): AnnualArchive
    {
        return AnnualArchive::updateOrCreate(
            [
                'fiscal_year' => $fiscalYear,
                'format' => $format,
            ],
            [
                'title' => 'Arsip Final Tahunan '.$fiscalYear,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'disk' => $disk,
                'generated_by' => $generatedBy,
                'generated_at' => $generatedAt,
                'metadata' => [
                    'total_personnel' => $snapshot['total_personnel'],
                    'submitted_personnel' => $snapshot['submitted_personnel'],
                    'budget_package_count' => count($snapshot['budget_packages']),
                ],
            ]
        );
    }
}
