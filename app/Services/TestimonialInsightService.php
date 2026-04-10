<?php

namespace App\Services;

use App\Models\Testimonial;

class TestimonialInsightService
{
    public function getStatistics(): array
    {
        $totalTestimonials = Testimonial::count();
        $averageRating = round(
            (float) (Testimonial::query()
                ->selectRaw('AVG(COALESCE(rating, 5)) as average_rating')
                ->value('average_rating') ?? 0),
            1,
        );

        $recentWindowStart = now()->subDays(30);
        $recentTestimonialsCount = Testimonial::where('created_at', '>=', $recentWindowStart)->count();
        $recentAverageRating = round(
            (float) (Testimonial::query()
                ->where('created_at', '>=', $recentWindowStart)
                ->selectRaw('AVG(COALESCE(rating, 5)) as average_rating')
                ->value('average_rating') ?? 0),
            1,
        );

        $ratingCounts = Testimonial::query()
            ->selectRaw('COALESCE(rating, 5) as normalized_rating, COUNT(*) as total')
            ->groupBy('normalized_rating')
            ->pluck('total', 'normalized_rating');

        $ratingBreakdown = collect(range(5, 1))
            ->map(function (int $stars) use ($ratingCounts, $totalTestimonials): array {
                $count = (int) ($ratingCounts[$stars] ?? 0);

                return [
                    'stars' => $stars,
                    'count' => $count,
                    'percentage' => $this->percentage($count, $totalTestimonials),
                ];
            });

        $positiveCount = $ratingBreakdown
            ->filter(fn (array $bucket): bool => $bucket['stars'] >= 4)
            ->sum('count');
        $neutralCount = $ratingBreakdown
            ->filter(fn (array $bucket): bool => $bucket['stars'] === 3)
            ->sum('count');
        $attentionCount = $ratingBreakdown
            ->filter(fn (array $bucket): bool => $bucket['stars'] <= 2)
            ->sum('count');

        $sentimentBreakdown = collect([
            [
                'label' => 'Sangat Positif',
                'count' => $positiveCount,
                'percentage' => $this->percentage($positiveCount, $totalTestimonials),
                'color' => 'var(--success)',
                'background' => 'var(--success-bg)',
            ],
            [
                'label' => 'Netral',
                'count' => $neutralCount,
                'percentage' => $this->percentage($neutralCount, $totalTestimonials),
                'color' => 'var(--warning)',
                'background' => 'var(--warning-bg)',
            ],
            [
                'label' => 'Perlu Atensi',
                'count' => $attentionCount,
                'percentage' => $this->percentage($attentionCount, $totalTestimonials),
                'color' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
            ],
        ]);

        $rawAverage = (float) (Testimonial::query()->selectRaw('AVG(COALESCE(rating, 5)) as average_rating')->value('average_rating') ?? 0);
        $fiveStarRate = $this->percentage((int) ($ratingCounts[5] ?? 0), $totalTestimonials);
        $serviceScore = $totalTestimonials > 0 ? (int) round(($rawAverage / 5) * 100) : 0;

        $topSatkers = Testimonial::query()
            ->join('users', 'users.id', '=', 'testimonials.user_id')
            ->leftJoin('satkers', 'satkers.id', '=', 'users.satker_id')
            ->selectRaw("COALESCE(satkers.name, 'Tanpa Satker') as satker_name")
            ->selectRaw('COUNT(*) as total_feedback')
            ->selectRaw('ROUND(AVG(COALESCE(testimonials.rating, 5)), 1) as average_rating')
            ->groupBy('satker_name')
            ->orderByDesc('total_feedback')
            ->orderByDesc('average_rating')
            ->limit(5)
            ->get();

        $latestTestimonials = Testimonial::with(['user.satker'])
            ->latest()
            ->take(24) // 8 batches × 3 categories
            ->get();

        // Group into batches (same user + same minute = one submission)
        $latestBatches = $latestTestimonials
            ->groupBy(fn (Testimonial $t): string => $t->user_id . '|' . $t->created_at->format('Y-m-d H:i'))
            ->take(8)
            ->values();

        $latestPositive = Testimonial::with(['user.satker'])
            ->whereRaw('COALESCE(rating, 5) >= 4')
            ->latest()
            ->first();

        $latestNeedsAttention = Testimonial::with(['user.satker'])
            ->whereRaw('COALESCE(rating, 5) <= 2')
            ->latest()
            ->first();

        $lastSubmittedAt = Testimonial::latest('created_at')->first()?->created_at;
        $serviceInsight = $this->buildServiceInsight(
            $averageRating,
            $totalTestimonials,
            $recentTestimonialsCount,
            $attentionCount,
        );
        $dashboardQuotes = $this->buildDashboardQuotes($latestPositive, $latestNeedsAttention, $latestTestimonials);
        $dashboardBadge = $this->buildDashboardBadge($serviceScore, $totalTestimonials);

        // Per-category statistics
        $categoryStats = $this->buildCategoryStats();

        return [
            'attentionCount' => $attentionCount,
            'averageRating' => $averageRating,
            'categoryStats' => $categoryStats,
            'dashboardBadge' => $dashboardBadge,
            'dashboardQuotes' => $dashboardQuotes,
            'fiveStarRate' => $fiveStarRate,
            'lastSubmittedAt' => $lastSubmittedAt,
            'latestNeedsAttention' => $latestNeedsAttention,
            'latestPositive' => $latestPositive,
            'latestBatches' => $latestBatches,
            'latestTestimonials' => $latestTestimonials,
            'ratingBreakdown' => $ratingBreakdown,
            'recentAverageRating' => $recentAverageRating,
            'recentTestimonialsCount' => $recentTestimonialsCount,
            'sentimentBreakdown' => $sentimentBreakdown,
            'serviceInsight' => $serviceInsight,
            'serviceScore' => $serviceScore,
            'topSatkers' => $topSatkers,
            'totalTestimonials' => $totalTestimonials,
        ];
    }

    /**
     * Build per-category average ratings and counts.
     */
    private function buildCategoryStats(): array
    {
        $stats = [];
        $icons = [
            'tutup_kepala' => ['icon' => 'ri-shield-user-line', 'bg' => '#eff6ff', 'color' => '#2563eb'],
            'tutup_badan' => ['icon' => 'ri-t-shirt-2-line', 'bg' => '#f0fdf4', 'color' => '#059669'],
            'tutup_kaki' => ['icon' => 'ri-footprint-line', 'bg' => '#fff7ed', 'color' => '#d97706'],
        ];

        foreach (Testimonial::CATEGORIES as $key => $label) {
            $query = Testimonial::where('category', $key);
            $count = $query->count();
            $avgRating = round(
                (float) (Testimonial::where('category', $key)
                    ->selectRaw('AVG(COALESCE(rating, 5)) as avg_rating')
                    ->value('avg_rating') ?? 0),
                1,
            );

            $ratingCounts = Testimonial::where('category', $key)
                ->selectRaw('COALESCE(rating, 5) as normalized_rating, COUNT(*) as total')
                ->groupBy('normalized_rating')
                ->pluck('total', 'normalized_rating');

            $ratingBreakdown = collect(range(5, 1))
                ->map(function (int $stars) use ($ratingCounts, $count): array {
                    $starCount = (int) ($ratingCounts[$stars] ?? 0);

                    return [
                        'stars' => $stars,
                        'count' => $starCount,
                        'percentage' => $this->percentage($starCount, $count),
                    ];
                })->toArray();

            $stats[$key] = [
                'label' => $label,
                'count' => $count,
                'average_rating' => $avgRating,
                'score' => $count > 0 ? (int) round(($avgRating / 5) * 100) : 0,
                'icon' => $icons[$key]['icon'] ?? 'ri-question-line',
                'bg' => $icons[$key]['bg'] ?? '#f8fafc',
                'color' => $icons[$key]['color'] ?? '#64748b',
                'ratingBreakdown' => $ratingBreakdown,
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

    private function buildServiceInsight(
        float $averageRating,
        int $totalTestimonials,
        int $recentTestimonialsCount,
        int $attentionCount,
    ): array {
        if ($totalTestimonials === 0) {
            return [
                'label' => 'Menunggu Masukan Perdana',
                'tone' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'message' => 'Belum ada testimoni yang masuk. Begitu personel mulai memberi masukan, halaman ini akan berubah menjadi panel evaluasi layanan untuk pimpinan.',
            ];
        }

        if ($averageRating >= 4.5 && $attentionCount === 0) {
            return [
                'label' => 'Layanan Sangat Baik',
                'tone' => 'var(--success)',
                'background' => 'var(--success-bg)',
                'message' => 'Mayoritas personel memberi sinyal kepuasan tinggi. Ini layak ditampilkan sebagai indikator bahwa pengalaman pengguna berjalan rapi.',
            ];
        }

        if ($averageRating >= 4.0) {
            return [
                'label' => 'Layanan Stabil',
                'tone' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'message' => $recentTestimonialsCount > 0
                    ? 'Umpan balik 30 hari terakhir tetap aktif dan penilaiannya baik. Fokus berikutnya adalah menjaga respons cepat pada masukan yang lebih kritis.'
                    : 'Penilaian keseluruhan baik. Perlu lebih banyak masukan baru agar tren kepuasan tetap terpantau dari waktu ke waktu.',
            ];
        }

        if ($attentionCount > 0) {
            return [
                'label' => 'Perlu Tindak Lanjut',
                'tone' => 'var(--danger)',
                'background' => 'var(--danger-bg)',
                'message' => 'Sudah ada testimoni bernada kurang puas. Untuk pimpinan, ini sebaiknya dilihat sebagai daftar prioritas perbaikan pengalaman pengguna.',
            ];
        }

        return [
            'label' => 'Perlu Penguatan Layanan',
            'tone' => 'var(--warning)',
            'background' => 'var(--warning-bg)',
            'message' => 'Penilaian belum cukup kuat untuk disebut unggul. Tambahkan perbaikan kecil pada alur input, kecepatan, dan kejelasan tampilan agar skor naik.',
        ];
    }

    private function buildDashboardBadge(int $serviceScore, int $totalTestimonials): array
    {
        if ($totalTestimonials === 0) {
            return [
                'label' => 'Belum Ada Data',
                'color' => 'var(--info)',
                'background' => 'var(--info-bg)',
                'icon' => 'ri-feedback-line',
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

    private function buildDashboardQuotes($latestPositive, $latestNeedsAttention, $latestTestimonials)
    {
        // Deduplicate: pick one testimonial per submission batch (same user + same minute)
        $seenBatches = [];
        $uniqueTestimonials = $latestTestimonials->filter(function (Testimonial $t) use (&$seenBatches): bool {
            $key = $t->user_id . '|' . $t->created_at->format('Y-m-d H:i');
            if (isset($seenBatches[$key])) {
                return false;
            }
            $seenBatches[$key] = true;

            return true;
        });

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

        foreach ($uniqueTestimonials as $testimonial) {
            if ($quotes->contains(fn (array $item): bool => $item['testimonial']->id === $testimonial->id)) {
                continue;
            }

            $quotes->push([
                'type' => 'Suara Pengguna',
                'accent' => 'var(--brand)',
                'background' => 'var(--brand-bg)',
                'testimonial' => $testimonial,
            ]);

            if ($quotes->count() === 6) {
                break;
            }
        }

        return $quotes->take(6)->values();
    }
}
