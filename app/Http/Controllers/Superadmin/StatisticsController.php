<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\TestimonialInsightService;

class StatisticsController extends Controller
{
    public function __construct(
        private readonly TestimonialInsightService $testimonialInsightService
    ) {}

    public function index()
    {
        return view('superadmin.statistics', $this->testimonialInsightService->getStatistics());
    }
}
