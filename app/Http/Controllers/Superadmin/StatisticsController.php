<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\TestimonialInsightService;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly TestimonialInsightService $testimonialInsightService
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
}
