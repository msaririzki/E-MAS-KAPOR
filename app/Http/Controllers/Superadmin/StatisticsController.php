<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ItemReview;
use App\Models\Satker;
use App\Models\Setting;
use App\Services\TestimonialInsightService;
use App\Services\TestimonialSatkerReportService;
use Illuminate\Http\Request;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly TestimonialInsightService $testimonialInsightService,
        private readonly TestimonialSatkerReportService $satkerReportService,
    ) {}

    public function index(Request $request)
    {
        return view('superadmin.statistics', $this->testimonialInsightService->getStatistics([
            'year' => $request->integer('year') ?: null,
            'distribution_group' => $request->string('distribution_group')->value(),
            'distribution_rating' => $request->integer('distribution_rating') ?: null,
            'compare_items' => $request->input('compare_items', []),
        ]));
    }

    public function showSatker(Request $request, Satker $satker)
    {
        $fiscalYear = $this->fiscalYear($request);
        $filters = $this->detailFilters($request);
        $reviews = $this->satkerReportService
            ->reviewsQuery($satker, $fiscalYear, $filters)
            ->paginate(20)
            ->withQueryString();
        $satkerSummary = $this->satkerReportService
            ->summaries($fiscalYear)
            ->firstWhere('satker_id', $satker->id);

        return view('superadmin.statistics.satker-detail', [
            'activeYear' => (int) Setting::getValue('fiscal_year', date('Y')),
            'availableYears' => $this->availableYears(),
            'filters' => $filters,
            'fiscalYear' => $fiscalYear,
            'reviews' => $reviews,
            'satker' => $satker,
            'satkerSummary' => $satkerSummary,
        ]);
    }

    public function exportSatkerSummaryPdf(Request $request)
    {
        $fiscalYear = $this->fiscalYear($request);
        $satkerStats = $this->satkerReportService->summaries($fiscalYear);

        $pdf = LaravelMpdf::loadView('superadmin.statistics.satker-summary-pdf', [
            'fiscalYear' => $fiscalYear,
            'generatedAt' => now(),
            'satkerStats' => $satkerStats,
        ], [], $this->pdfOptions());

        return $this->pdfDownload($pdf, 'Rekap_Ulasan_Seluruh_Satker_TA_'.$fiscalYear.'.pdf');
    }

    public function exportSatkerDetailPdf(Request $request, Satker $satker)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $fiscalYear = $this->fiscalYear($request);
        $filters = $this->detailFilters($request);
        $reviews = $this->satkerReportService
            ->reviewsQuery($satker, $fiscalYear, $filters)
            ->get();
        $satkerSummary = $this->satkerReportService
            ->summaries($fiscalYear)
            ->firstWhere('satker_id', $satker->id);

        $pdf = LaravelMpdf::loadView('superadmin.statistics.satker-detail-pdf', [
            'filters' => $filters,
            'fiscalYear' => $fiscalYear,
            'generatedAt' => now(),
            'reviews' => $reviews,
            'satker' => $satker,
            'satkerSummary' => $satkerSummary,
        ], [], $this->pdfOptions());

        return $this->pdfDownload($pdf, 'Detail_Ulasan_'.$this->safeFilename($satker->name).'_TA_'.$fiscalYear.'.pdf');
    }

    private function fiscalYear(Request $request): int
    {
        return $request->integer('year') ?: (int) Setting::getValue('fiscal_year', date('Y'));
    }

    private function availableYears()
    {
        $activeYear = (int) Setting::getValue('fiscal_year', date('Y'));

        return ItemReview::query()
            ->distinct()
            ->pluck('fiscal_year')
            ->push($activeYear)
            ->unique()
            ->sortDesc()
            ->values();
    }

    private function detailFilters(Request $request): array
    {
        return [
            'search' => mb_substr(trim((string) $request->query('search', '')), 0, 100),
            'response_status' => (string) $request->query('response_status', ''),
            'rating' => $request->integer('rating') ?: null,
        ];
    }

    private function pdfOptions(): array
    {
        return [
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'DejaVu Sans',
            'shrink_tables_to_fit' => 1,
        ];
    }

    private function safeFilename(string $value): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '_', $value), '_');
    }

    private function pdfDownload(\Mccarlosen\LaravelMpdf\LaravelMpdf $pdf, string $filename): \Illuminate\Http\Response
    {
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
