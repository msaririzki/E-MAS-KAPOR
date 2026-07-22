<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ItemReview;
use App\Models\Setting;
use App\Services\TestimonialExportService;
use App\Services\TestimonialWordExportService;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function __construct(
        private readonly TestimonialExportService $testimonialExportService,
        private readonly TestimonialWordExportService $testimonialWordExportService
    ) {}

    public function index(Request $request)
    {
        $query = ItemReview::with(['user.satker', 'kaporItem', 'allocation']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('kaporItem', function ($q2) use ($search) {
                        $q2->where('item_name', 'like', "%{$search}%")
                            ->orWhere('category', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('nrp_nip', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user.satker', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('category')) {
            $category = $request->category;

            if (in_array($category, ['kepala', 'badan', 'kaki', 'lainnya'])) {
                $categorySql = "CASE
                    WHEN UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%KEPALA%'
                        OR UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%TOPI%'
                        OR UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%BARET%'
                        THEN 'kepala'
                    WHEN UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%KAKI%'
                        OR UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%SEPATU%'
                        THEN 'kaki'
                    WHEN UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%BADAN%'
                        OR UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%PAKAIAN%'
                        OR UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%BAJU%'
                        OR UPPER(COALESCE(personnel_item_allocations.item_category_snapshot, kapor_items.category, '')) LIKE '%KAOS%'
                        THEN 'badan'
                    ELSE 'lainnya'
                END";

                $query->leftJoin('personnel_item_allocations', 'personnel_item_allocations.id', '=', 'item_reviews.personnel_item_allocation_id')
                    ->leftJoin('kapor_items', 'kapor_items.id', '=', 'item_reviews.kapor_item_id')
                    ->whereRaw("($categorySql) = ?", [$category])
                    ->select('item_reviews.*');
            } else {
                $query->where(function ($q) use ($category) {
                    $q->whereHas('kaporItem', fn ($kaporQuery) => $kaporQuery->where('category', 'like', "%{$category}%"))
                        ->orWhereHas('allocation', fn ($allocQuery) => $allocQuery->where('item_category_snapshot', 'like', "%{$category}%"));
                });
            }
        }

        if ($request->filled('response_status')) {
            $query->where('response_status', $request->response_status);
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $activeYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $fiscalYear = (int) $request->query('year', $activeYear);
        $availableYears = ItemReview::query()
            ->distinct()
            ->pluck('fiscal_year')
            ->push($activeYear)
            ->unique()
            ->sortDesc()
            ->values();

        $query->where('item_reviews.fiscal_year', $fiscalYear);

        $testimonials = $query
            ->orderByRaw('COALESCE(item_reviews.submitted_at, item_reviews.created_at) DESC')
            ->orderByDesc('item_reviews.id')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.testimonials.index', compact('testimonials', 'availableYears', 'fiscalYear', 'activeYear'));
    }

    public function exportPdf(Request $request)
    {
        $activeYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $fiscalYear = (int) $request->query('year', $activeYear);
        $commentsPerRating = min(max($request->integer('comments_per_rating', 2), 1), 50);
        $data = $this->testimonialExportService->build($fiscalYear, $commentsPerRating);

        $pdf = \Mccarlosen\LaravelMpdf\Facades\LaravelMpdf::loadView('superadmin.testimonials.export-pdf', $data, [], [
            'format' => 'A4',
            'orientation' => 'L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'default_font' => 'DejaVu Sans',
            'shrink_tables_to_fit' => 0,
        ]);

        return $pdf->download('Hasil_Review_Kapor_TA_'.$fiscalYear.'.pdf');
    }

    public function exportWord(Request $request)
    {
        $activeYear = (int) Setting::getValue('fiscal_year', date('Y'));
        $fiscalYear = (int) $request->query('year', $activeYear);
        $commentsPerRating = min(max($request->integer('comments_per_rating', 2), 1), 50);
        $data = $this->testimonialExportService->build($fiscalYear, $commentsPerRating);

        $tempFile = $this->testimonialWordExportService->generate($data);

        return response()
            ->download($tempFile, 'Hasil_Review_Kapor_TA_'.$fiscalYear.'.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }
}
