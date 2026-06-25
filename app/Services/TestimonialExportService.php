<?php

namespace App\Services;

use App\Models\ItemReview;
use Illuminate\Support\Collection;

class TestimonialExportService
{
    public function build(int $fiscalYear, int $commentsPerRating = 2): array
    {
        $commentsPerRating = max(1, min($commentsPerRating, 50));

        $reviews = ItemReview::query()
            ->with(['user.satker', 'kaporItem', 'allocation'])
            ->where('fiscal_year', $fiscalYear)
            ->orderBy('id')
            ->get();

        $satkerNames = $reviews
            ->map(fn (ItemReview $review): string => $this->satkerName($review))
            ->unique()
            ->sort()
            ->values();

        $categoryGroups = $reviews
            ->groupBy(fn (ItemReview $review): string => $this->categoryLabel($review))
            ->sortKeysUsing(fn (string $first, string $second): int => $this->categoryOrder($first) <=> $this->categoryOrder($second))
            ->map(function (Collection $categoryReviews, string $category) use ($satkerNames): array {
                $items = $categoryReviews
                    ->groupBy(fn (ItemReview $review): string => $this->itemName($review))
                    ->sortKeys()
                    ->map(function (Collection $itemReviews, string $itemName) use ($satkerNames): array {
                        return [
                            'name' => $itemName,
                            'satker_scores' => $satkerNames
                                ->mapWithKeys(function (string $satkerName) use ($itemReviews): array {
                                    $score = $this->averageRatingPercentage(
                                        $itemReviews->filter(fn (ItemReview $review): bool => $this->satkerName($review) === $satkerName),
                                    );

                                    return [$satkerName => $score];
                                })
                                ->all(),
                            'overall_score' => $this->averageRatingPercentage($itemReviews),
                        ];
                    })
                    ->values();

                return [
                    'name' => $category,
                    'items' => $items,
                    'overall_score' => $this->averageRatingPercentage($categoryReviews),
                    'satker_scores' => $satkerNames
                        ->mapWithKeys(function (string $satkerName) use ($categoryReviews): array {
                            $score = $this->averageRatingPercentage(
                                $categoryReviews->filter(fn (ItemReview $review): bool => $this->satkerName($review) === $satkerName),
                            );

                            return [$satkerName => $score];
                        })
                        ->all(),
                ];
            })
            ->values();

        $commentsByRating = collect(range(5, 1))
            ->mapWithKeys(function (int $rating) use ($reviews, $commentsPerRating): array {
                $comments = $reviews
                    ->where('response_status', ItemReview::STATUS_REVIEWED)
                    ->where('rating', $rating)
                    ->filter(fn (ItemReview $review): bool => filled($review->comment))
                    ->sortByDesc(fn (ItemReview $review) => $review->submitted_at ?? $review->created_at)
                    ->take($commentsPerRating)
                    ->map(fn (ItemReview $review): array => [
                        'comment' => $review->comment,
                        'personnel' => $review->user?->name ?? $review->allocation?->full_name_snapshot ?? 'Personil',
                        'satker' => $this->satkerName($review),
                        'item' => $this->itemName($review),
                    ])
                    ->values();

                return [$rating => $comments];
            });

        return [
            'fiscalYear' => $fiscalYear,
            'generatedAt' => now(),
            'satkerNames' => $satkerNames,
            'categoryGroups' => $categoryGroups,
            'categorySummaries' => $categoryGroups,
            'commentsByRating' => $commentsByRating,
            'commentsPerRating' => $commentsPerRating,
            'totalReviews' => $reviews->count(),
        ];
    }

    private function averageRatingPercentage(Collection $reviews): ?float
    {
        $ratings = $reviews
            ->where('response_status', ItemReview::STATUS_REVIEWED)
            ->whereNotNull('rating')
            ->pluck('rating');

        if ($ratings->isEmpty()) {
            return null;
        }

        return round(((float) $ratings->avg() / 5) * 100, 1);
    }

    private function satkerName(ItemReview $review): string
    {
        return $review->allocation?->satker_name_snapshot
            ?? $review->user?->satker?->name
            ?? 'Tanpa Satker';
    }

    private function itemName(ItemReview $review): string
    {
        return $review->allocation?->kapor_item_name_snapshot
            ?? $review->kaporItem?->item_name
            ?? 'Item Kapor';
    }

    private function categoryLabel(ItemReview $review): string
    {
        $category = $review->allocation?->item_category_snapshot
            ?? $review->kaporItem?->category
            ?? 'Lainnya';

        return match ($this->categoryKey($category)) {
            'kepala' => 'Tutup Kepala',
            'badan' => 'Tutup Badan',
            'kaki' => 'Tutup Kaki',
            default => 'Item Lainnya / Atribut',
        };
    }

    private function categoryKey(string $category): string
    {
        $category = strtoupper($category);

        if (str_contains($category, 'KEPALA') || str_contains($category, 'TOPI') || str_contains($category, 'BARET')) {
            return 'kepala';
        }

        if (str_contains($category, 'BADAN') || str_contains($category, 'PAKAIAN') || str_contains($category, 'BAJU') || str_contains($category, 'KAOS')) {
            return 'badan';
        }

        if (str_contains($category, 'KAKI') || str_contains($category, 'SEPATU')) {
            return 'kaki';
        }

        return 'lainnya';
    }

    private function categoryOrder(string $category): int
    {
        return match ($category) {
            'Tutup Kepala' => 1,
            'Tutup Badan' => 2,
            'Tutup Kaki' => 3,
            default => 4,
        };
    }
}
