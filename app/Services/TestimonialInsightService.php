<?php

namespace App\Services;

use App\Models\ItemReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TestimonialInsightService
{
    private const DISTRIBUTION_GROUPS = [
        'kepala' => [
            'label' => 'Tutup Kepala',
            'icon' => 'ri-shield-user-line',
            'bg' => '#eff6ff',
            'color' => '#2563eb',
            'type' => 'rating',
        ],
        'badan' => [
            'label' => 'Tutup Badan',
            'icon' => 'ri-t-shirt-2-line',
            'bg' => '#f0fdf4',
            'color' => '#059669',
            'type' => 'rating',
        ],
        'kaki' => [
            'label' => 'Tutup Kaki',
            'icon' => 'ri-footprint-line',
            'bg' => '#fff7ed',
            'color' => '#d97706',
            'type' => 'rating',
        ],
        'lainnya' => [
            'label' => 'Item Lainnya',
            'icon' => 'ri-more-fill',
            'bg' => '#f1f5f9',
            'color' => '#475569',
            'type' => 'rating',
        ],
    ];

    public function getStatistics(array $filters = []): array
    {
        $activeYear = (int) \App\Models\Setting::getValue('fiscal_year', date('Y'));
        $fiscalYear = (string) ($filters['year'] ?? $activeYear);

        $distributionFilters = [
            'group' => $filters['distribution_group'] ?? 'all',
            'rating' => $filters['distribution_rating'] ?? null,
            'compare_items' => $filters['compare_items'] ?? [],
        ];

        $reviewQuery = ItemReview::query()
            ->where('item_reviews.fiscal_year', $fiscalYear)
            ->where('item_reviews.response_status', ItemReview::STATUS_REVIEWED)
            ->whereNotNull('item_reviews.rating');

        $baseQuery = ItemReview::where('item_reviews.fiscal_year', $fiscalYear);

        $totalTestimonials = (clone $baseQuery)->count();
        $totalReviewed = (clone $reviewQuery)->count();
        $notReceivedCount = (clone $baseQuery)->where('response_status', ItemReview::STATUS_NOT_RECEIVED)->count();
        $averageRating = round((float) ((clone $reviewQuery)->avg('rating') ?? 0), 1);

        $recentWindowStart = now()->subDays(30);
        $recentTestimonialsCount = (clone $baseQuery)
            ->whereRaw($this->submissionTimestampExpression().' >= ?', [$recentWindowStart])
            ->count();
        $recentAverageRating = round(
            (float) ((clone $reviewQuery)
                ->whereRaw($this->submissionTimestampExpression().' >= ?', [$recentWindowStart])
                ->avg('rating') ?? 0),
            1,
        );

        $ratingCounts = (clone $reviewQuery)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $ratingBreakdown = collect(range(5, 1))
            ->map(function (int $stars) use ($ratingCounts, $totalReviewed): array {
                $count = (int) ($ratingCounts[$stars] ?? 0);

                return [
                    'stars' => $stars,
                    'count' => $count,
                    'percentage' => $this->percentage($count, $totalReviewed),
                ];
            });

        $positiveCount = (int) $ratingBreakdown->filter(fn (array $bucket): bool => $bucket['stars'] >= 4)->sum('count');
        $neutralCount = (int) $ratingBreakdown->filter(fn (array $bucket): bool => $bucket['stars'] === 3)->sum('count');
        $lowRatingCount = (int) $ratingBreakdown->filter(fn (array $bucket): bool => $bucket['stars'] <= 2)->sum('count');
        $attentionCount = $lowRatingCount + $notReceivedCount;

        $sentimentBreakdown = collect([
            [
                'label' => 'Review Positif',
                'count' => $positiveCount,
                'percentage' => $this->percentage($positiveCount, max($totalTestimonials, 1)),
                'color' => 'var(--success)',
                'background' => 'var(--success-bg)',
            ],
            [
                'label' => 'Review Netral',
                'count' => $neutralCount,
                'percentage' => $this->percentage($neutralCount, max($totalTestimonials, 1)),
                'color' => 'var(--warning)',
                'background' => 'var(--warning-bg)',
            ],
            [
                'label' => 'Belum/Tindak Lanjut',
                'count' => $attentionCount,
                'percentage' => $this->percentage($attentionCount, max($totalTestimonials, 1)),
                'color' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
            ],
        ]);

        $serviceScore = $totalReviewed > 0 ? (int) round(($averageRating / 5) * 100) : 0;
        $fiveStarRate = $this->percentage((int) ($ratingCounts[5] ?? 0), $totalReviewed);

        $topSatkers = (clone $baseQuery)
            ->leftJoin('personnel_item_allocations', 'personnel_item_allocations.id', '=', 'item_reviews.personnel_item_allocation_id')
            ->leftJoin('users', 'users.id', '=', 'item_reviews.user_id')
            ->leftJoin('satkers', 'satkers.id', '=', 'users.satker_id')
            ->selectRaw("COALESCE(personnel_item_allocations.satker_name_snapshot, satkers.name, 'Tanpa Satker') as satker_name")
            ->selectRaw('COUNT(item_reviews.id) as total_feedback')
            ->selectRaw("ROUND(AVG(CASE WHEN item_reviews.response_status = 'reviewed' THEN item_reviews.rating END), 1) as average_rating")
            ->groupBy('satker_name')
            ->orderByDesc('total_feedback')
            ->orderByDesc('average_rating')
            ->limit(5)
            ->get();

        $latestTestimonials = (clone $baseQuery)->with(['user.satker', 'kaporItem', 'allocation'])
            ->orderByRaw($this->submissionTimestampExpression().' DESC')
            ->orderByDesc('item_reviews.id')
            ->take(18)
            ->get();

        $latestBatches = $latestTestimonials
            ->groupBy(function (ItemReview $review): string {
                $submittedAt = $review->submitted_at ?? $review->created_at;

                return $review->user_id.'|'.$submittedAt?->format('Y-m-d H:i');
            })
            ->take(8)
            ->values();

        $latestPositive = (clone $baseQuery)->with(['user.satker', 'kaporItem', 'allocation'])
            ->where('response_status', ItemReview::STATUS_REVIEWED)
            ->where('rating', '>=', 4)
            ->orderByRaw($this->submissionTimestampExpression().' DESC')
            ->orderByDesc('item_reviews.id')
            ->first();

        $latestNeedsAttention = (clone $baseQuery)->with(['user.satker', 'kaporItem', 'allocation'])
            ->where(function ($query): void {
                $query->where('response_status', ItemReview::STATUS_NOT_RECEIVED)
                    ->orWhere(function ($ratingQuery): void {
                        $ratingQuery->where('response_status', ItemReview::STATUS_REVIEWED)
                            ->where('rating', '<=', 2);
                    });
            })
            ->orderByRaw($this->submissionTimestampExpression().' DESC')
            ->orderByDesc('item_reviews.id')
            ->first();

        $lastSubmittedAt = (clone $baseQuery)->latest('submitted_at')->first()?->submitted_at;
        $serviceInsight = $this->buildServiceInsight($averageRating, $totalTestimonials, $recentTestimonialsCount, $attentionCount, $notReceivedCount);
        $dashboardQuotes = $this->buildDashboardQuotes($latestPositive, $latestNeedsAttention, $latestTestimonials);
        $dashboardBadge = $this->buildDashboardBadge($serviceScore, $totalTestimonials, $notReceivedCount);
        $categoryStats = $this->buildCategoryStats($fiscalYear);
        $distributionStats = $this->buildDistributionStats($distributionFilters, $fiscalYear);

        $availableYears = ItemReview::distinct()->pluck('fiscal_year')->push($activeYear)->unique()->sortDesc();

        return [
            'fiscal_year' => $fiscalYear,
            'active_year' => $activeYear,
            'availableYears' => $availableYears,
            'attentionCount' => $attentionCount,
            'averageRating' => $averageRating,
            'categoryStats' => $categoryStats,
            'dashboardBadge' => $dashboardBadge,
            'dashboardQuotes' => $dashboardQuotes,
            'distributionStats' => $distributionStats,
            'distributionFilters' => $distributionFilters,
            'distributionGroups' => self::DISTRIBUTION_GROUPS,
            'fiveStarRate' => $fiveStarRate,
            'lastSubmittedAt' => $lastSubmittedAt,
            'latestNeedsAttention' => $latestNeedsAttention,
            'latestPositive' => $latestPositive,
            'latestBatches' => $latestBatches,
            'latestTestimonials' => $latestTestimonials,
            'notReceivedCount' => $notReceivedCount,
            'ratingBreakdown' => $ratingBreakdown,
            'recentAverageRating' => $recentAverageRating,
            'recentTestimonialsCount' => $recentTestimonialsCount,
            'sentimentBreakdown' => $sentimentBreakdown,
            'serviceInsight' => $serviceInsight,
            'serviceScore' => $serviceScore,
            'topSatkers' => $topSatkers,
            'totalTestimonials' => $totalTestimonials,
            'comparisonStats' => $this->buildComparisonStats($distributionFilters['compare_items'], $fiscalYear),
            'availableItems' => \App\Models\KaporItem::active()
                ->orderBy('item_name')
                ->get(['id', 'item_name', 'category'])
                ->map(function ($item) {
                    $cat = strtoupper($item->category);
                    if (str_contains($cat, 'KEPALA') || str_contains($cat, 'TOPI') || str_contains($cat, 'BARET')) {
                        $item->group = 'Kepala';
                    } elseif (str_contains($cat, 'KAKI') || str_contains($cat, 'SEPATU')) {
                        $item->group = 'Kaki';
                    } elseif (str_contains($cat, 'BADAN') || str_contains($cat, 'PAKAIAN') || str_contains($cat, 'BAJU') || str_contains($cat, 'KAOS')) {
                        $item->group = 'Badan';
                    } else {
                        $item->group = 'Lainnya';
                    }

                    return $item;
                }),
        ];
    }

    private function buildCategoryStats(string $fiscalYear): array
    {
        return $this->buildDistributionStats([
            'group' => 'all',
            'rating' => null,
        ], $fiscalYear);
    }

    private function buildDistributionStats(array $filters, string $fiscalYear): array
    {
        $selectedGroup = $filters['group'] ?? 'all';
        $selectedRating = $filters['rating'] ?? null;

        $stats = [];

        foreach (self::DISTRIBUTION_GROUPS as $key => $meta) {
            if ($selectedGroup !== 'all' && $selectedGroup !== $key) {
                continue;
            }

            $baseQuery = $this->baseGroupedReviewQuery($key, $fiscalYear);
            $summaryQuery = $this->baseGroupedReviewQuery($key, $fiscalYear);
            if ($selectedRating !== null) {
                $summaryQuery->where('item_reviews.rating', (int) $selectedRating);
            }

            $count = (clone $summaryQuery)->count();
            $averageRating = round((float) ((clone $summaryQuery)->avg('item_reviews.rating') ?? 0), 1);

            $ratingCountQuery = $this->baseGroupedReviewQuery($key, $fiscalYear);
            if ($selectedRating !== null) {
                $ratingCountQuery->where('item_reviews.rating', (int) $selectedRating);
            }

            $ratingCounts = $ratingCountQuery
                ->selectRaw('item_reviews.rating, COUNT(item_reviews.id) as total')
                ->groupBy('item_reviews.rating')
                ->pluck('total', 'item_reviews.rating');

            $reviewedCount = $selectedRating !== null
                ? (int) ($ratingCounts[$selectedRating] ?? 0)
                : array_sum($ratingCounts->all());

            $stats[$key] = [
                'key' => $key,
                'label' => $meta['label'],
                'count' => $count,
                'average_rating' => $averageRating,
                'score' => $averageRating > 0 ? (int) round(($averageRating / 5) * 100) : 0,
                'icon' => $meta['icon'],
                'bg' => $meta['bg'],
                'color' => $meta['color'],
                'type' => $meta['type'],
                'ratingBreakdown' => collect(range(5, 1))
                    ->map(function (int $stars) use ($ratingCounts, $reviewedCount, $selectedRating): array {
                        if ($selectedRating !== null && $selectedRating !== $stars) {
                            return [
                                'stars' => $stars,
                                'count' => 0,
                                'percentage' => 0,
                            ];
                        }

                        $count = (int) ($ratingCounts[$stars] ?? 0);

                        return [
                            'stars' => $stars,
                            'count' => $count,
                            'percentage' => $this->percentage($count, max($reviewedCount, 1)),
                        ];
                    })
                    ->toArray(),
            ];
        }

        return $stats;
    }

    private function baseGroupedReviewQuery(string $groupKey, string $fiscalYear): Builder
    {
        $query = ItemReview::query()
            ->leftJoin('personnel_item_allocations', 'personnel_item_allocations.id', '=', 'item_reviews.personnel_item_allocation_id')
            ->leftJoin('kapor_items', 'kapor_items.id', '=', 'item_reviews.kapor_item_id')
            ->where('item_reviews.fiscal_year', $fiscalYear)
            ->where('item_reviews.response_status', ItemReview::STATUS_REVIEWED)
            ->whereNotNull('item_reviews.rating');

        return match ($groupKey) {
            'kepala' => $query->whereRaw($this->categorySql().' = ?', ['kepala']),
            'kaki' => $query->whereRaw($this->categorySql().' = ?', ['kaki']),
            'badan' => $query->whereRaw($this->categorySql().' = ?', ['badan']),
            default => $query->whereRaw($this->categorySql().' = ?', ['lainnya']),
        };
    }

    private function categorySql(): string
    {
        return "CASE
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
    }

    private function buildServiceInsight(float $averageRating, int $totalResponses, int $recentResponses, int $attentionCount, int $notReceivedCount): array
    {
        if ($totalResponses === 0) {
            return [
                'label' => 'Menunggu Respons Perdana',
                'tone' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'message' => 'Belum ada review atau laporan penerimaan item. Begitu personel mulai merespons item yang dialokasikan, panel ini akan berubah menjadi indikator kualitas layanan dan distribusi.',
            ];
        }

        if ($notReceivedCount > 0) {
            return [
                'label' => 'Distribusi Perlu Dipantau',
                'tone' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
                'message' => 'Sudah ada laporan item belum diterima. Jadikan panel ini sebagai daftar prioritas untuk memastikan distribusi benar-benar sampai ke personel yang dituju.',
            ];
        }

        if ($averageRating >= 4.5 && $attentionCount === 0) {
            return [
                'label' => 'Layanan Sangat Baik',
                'tone' => 'var(--success)',
                'background' => 'var(--success-bg)',
                'message' => 'Mayoritas review item menunjukkan pengalaman yang sangat baik. Ini sinyal kuat bahwa kualitas item dan alur penyalurannya berjalan rapi.',
            ];
        }

        if ($averageRating >= 4.0) {
            return [
                'label' => 'Layanan Stabil',
                'tone' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'message' => $recentResponses > 0
                    ? 'Respons 30 hari terakhir tetap aktif dan penilaiannya baik. Fokus berikutnya adalah menjaga kualitas item dan menindaklanjuti laporan yang lebih kritis.'
                    : 'Penilaian keseluruhan baik. Perlu lebih banyak respons baru agar tren pengalaman personel tetap terpantau dari waktu ke waktu.',
            ];
        }

        if ($attentionCount > 0) {
            return [
                'label' => 'Perlu Tindak Lanjut',
                'tone' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
                'message' => 'Ada review bernada kurang puas atau laporan distribusi bermasalah. Gunakan daftar ini sebagai prioritas evaluasi kualitas dan penerimaan item.',
            ];
        }

        return [
            'label' => 'Perlu Penguatan Layanan',
            'tone' => 'var(--warning)',
            'background' => 'var(--warning-bg)',
            'message' => 'Skor review belum cukup kuat untuk disebut unggul. Tambahkan perbaikan kecil pada kualitas item, kejelasan distribusi, dan tindak lanjut laporan lapangan.',
        ];
    }

    private function buildDashboardBadge(int $serviceScore, int $totalResponses, int $notReceivedCount): array
    {
        if ($totalResponses === 0) {
            return [
                'label' => 'Belum Ada Data',
                'color' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'icon' => 'ri-feedback-line',
            ];
        }

        if ($notReceivedCount > 0) {
            return [
                'label' => 'Perlu Cek Distribusi',
                'color' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
                'icon' => 'ri-truck-line',
            ];
        }

        if ($serviceScore >= 90) {
            return [
                'label' => 'Sangat Puas',
                'color' => 'var(--success)',
                'background' => 'var(--success-bg)',
                'icon' => 'ri-emotion-happy-line',
            ];
        }

        if ($serviceScore >= 75) {
            return [
                'label' => 'Puas',
                'color' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'icon' => 'ri-emotion-line',
            ];
        }

        if ($serviceScore >= 60) {
            return [
                'label' => 'Cukup',
                'color' => 'var(--warning)',
                'background' => 'var(--warning-bg)',
                'icon' => 'ri-emotion-normal-line',
            ];
        }

        return [
            'label' => 'Perlu Perbaikan',
            'color' => 'var(--danger)',
            'background' => 'var(--danger-bg)',
            'icon' => 'ri-emotion-unhappy-line',
        ];
    }

    private function buildDashboardQuotes($latestPositive, $latestNeedsAttention, Collection $latestResponses): Collection
    {
        $quotes = collect();

        if ($latestPositive) {
            $quotes->push([
                'type' => 'Apresiasi Terbaru',
                'accent' => 'var(--success)',
                'background' => 'var(--success-bg)',
                'testimonial' => $latestPositive,
            ]);
        }

        if ($latestNeedsAttention && (! $latestPositive || $latestNeedsAttention->id !== $latestPositive->id)) {
            $quotes->push([
                'type' => 'Perlu Tindak Lanjut',
                'accent' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
                'testimonial' => $latestNeedsAttention,
            ]);
        }

        foreach ($latestResponses as $response) {
            if ($quotes->contains(fn (array $quote): bool => $quote['testimonial']->id === $response->id)) {
                continue;
            }

            $quotes->push([
                'type' => $response->response_status === ItemReview::STATUS_NOT_RECEIVED ? 'Belum Diterima' : 'Review Terbaru',
                'accent' => $response->response_status === ItemReview::STATUS_NOT_RECEIVED ? 'var(--warning)' : 'var(--info)',
                'background' => $response->response_status === ItemReview::STATUS_NOT_RECEIVED ? 'var(--warning-bg)' : 'var(--info-bg)',
                'testimonial' => $response,
            ]);

            if ($quotes->count() >= 4) {
                break;
            }
        }

        return $quotes;
    }

    private function buildComparisonStats(array $itemIds, string $fiscalYear): array
    {
        if (empty($itemIds)) {
            // Default to top 3 items by review count if none selected
            $itemIds = ItemReview::query()
                ->where('fiscal_year', $fiscalYear)
                ->selectRaw('kapor_item_id, COUNT(*) as count')
                ->whereNotNull('kapor_item_id')
                ->groupBy('kapor_item_id')
                ->orderByDesc('count')
                ->limit(3)
                ->pluck('kapor_item_id')
                ->toArray();
        }

        $stats = [];
        foreach ($itemIds as $id) {
            if (! $id) {
                continue;
            }

            $item = \App\Models\KaporItem::find($id);
            if (! $item) {
                continue;
            }

            $reviewQuery = ItemReview::query()
                ->where('fiscal_year', $fiscalYear)
                ->where('kapor_item_id', $id)
                ->where('response_status', ItemReview::STATUS_REVIEWED)
                ->whereNotNull('rating');

            $totalReviewed = (clone $reviewQuery)->count();
            $notReceivedCount = ItemReview::query()
                ->where('fiscal_year', $fiscalYear)
                ->where('kapor_item_id', $id)
                ->where('response_status', ItemReview::STATUS_NOT_RECEIVED)
                ->count();

            $ratingCounts = (clone $reviewQuery)
                ->selectRaw('rating, COUNT(*) as total')
                ->groupBy('rating')
                ->pluck('total', 'rating');

            $ratingBreakdown = collect(range(5, 1))
                ->map(function (int $stars) use ($ratingCounts, $totalReviewed): array {
                    $count = (int) ($ratingCounts[$stars] ?? 0);

                    return [
                        'stars' => $stars,
                        'count' => $count,
                        'percentage' => $this->percentage($count, $totalReviewed),
                    ];
                });

            $stats[] = [
                'id' => $item->id,
                'name' => $item->item_name,
                'total_reviewed' => $totalReviewed,
                'not_received_count' => $notReceivedCount,
                'rating_breakdown' => $ratingBreakdown,
                'average_rating' => round((float) ((clone $reviewQuery)->avg('rating') ?? 0), 1),
            ];
        }

        return $stats;
    }

    private function percentage(int $count, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 1);
    }

    private function submissionTimestampExpression(): string
    {
        return 'COALESCE(item_reviews.submitted_at, item_reviews.created_at)';
    }
}
