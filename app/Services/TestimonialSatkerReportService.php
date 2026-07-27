<?php

namespace App\Services;

use App\Models\ItemReview;
use App\Models\Satker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TestimonialSatkerReportService
{
    public function summaries(int $fiscalYear): Collection
    {
        $aggregates = ItemReview::query()
            ->leftJoin('personnel_item_allocations as review_allocations', 'review_allocations.id', '=', 'item_reviews.personnel_item_allocation_id')
            ->leftJoin('users as review_users', 'review_users.id', '=', 'item_reviews.user_id')
            ->where('item_reviews.fiscal_year', $fiscalYear)
            ->whereRaw($this->resolvedSatkerExpression().' IS NOT NULL')
            ->selectRaw($this->resolvedSatkerExpression().' as resolved_satker_id')
            ->selectRaw('COUNT(item_reviews.id) as total_feedback')
            ->selectRaw('COUNT(DISTINCT COALESCE(item_reviews.user_id, review_allocations.user_id)) as respondent_count')
            ->selectRaw("SUM(CASE WHEN item_reviews.response_status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count")
            ->selectRaw("SUM(CASE WHEN item_reviews.response_status = 'belum_menerima' THEN 1 ELSE 0 END) as not_received_count")
            ->selectRaw("ROUND(AVG(CASE WHEN item_reviews.response_status = 'reviewed' THEN item_reviews.rating END), 1) as average_rating")
            ->groupByRaw($this->resolvedSatkerExpression())
            ->get()
            ->keyBy(fn (object $row): string => (string) $row->resolved_satker_id);

        return Satker::query()
            ->get(['id', 'name', 'code', 'sort_order'])
            ->map(function (Satker $satker) use ($aggregates): array {
                $aggregate = $aggregates->get((string) $satker->id);

                return [
                    'satker_id' => $satker->id,
                    'satker_name' => $satker->name,
                    'satker_code' => $satker->code,
                    'respondent_count' => (int) ($aggregate->respondent_count ?? 0),
                    'total_feedback' => (int) ($aggregate->total_feedback ?? 0),
                    'reviewed_count' => (int) ($aggregate->reviewed_count ?? 0),
                    'not_received_count' => (int) ($aggregate->not_received_count ?? 0),
                    'average_rating' => $aggregate?->average_rating !== null
                        ? (float) $aggregate->average_rating
                        : null,
                ];
            })
            ->sortBy([
                ['total_feedback', 'desc'],
                ['satker_name', 'asc'],
            ])
            ->values();
    }

    public function reviewsQuery(Satker $satker, int $fiscalYear, array $filters = []): Builder
    {
        $query = ItemReview::query()
            ->with(['user.satker', 'personnel.rank', 'kaporItem', 'allocation'])
            ->leftJoin('personnel_item_allocations as review_allocations', 'review_allocations.id', '=', 'item_reviews.personnel_item_allocation_id')
            ->leftJoin('users as review_users', 'review_users.id', '=', 'item_reviews.user_id')
            ->leftJoin('kapor_items as review_items', 'review_items.id', '=', 'item_reviews.kapor_item_id')
            ->where('item_reviews.fiscal_year', $fiscalYear)
            ->whereRaw($this->resolvedSatkerExpression().' = ?', [$satker->id])
            ->select('item_reviews.*');

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search): void {
                $like = '%'.$search.'%';

                $searchQuery
                    ->where('review_users.name', 'like', $like)
                    ->orWhere('review_users.nrp_nip', 'like', $like)
                    ->orWhere('review_allocations.full_name_snapshot', 'like', $like)
                    ->orWhere('review_allocations.nrp_snapshot', 'like', $like)
                    ->orWhere('review_allocations.kapor_item_name_snapshot', 'like', $like)
                    ->orWhere('review_items.item_name', 'like', $like)
                    ->orWhere('item_reviews.comment', 'like', $like);
            });
        }

        $status = (string) ($filters['response_status'] ?? '');
        if (array_key_exists($status, ItemReview::RESPONSE_STATUSES)) {
            $query->where('item_reviews.response_status', $status);
        }

        $rating = (int) ($filters['rating'] ?? 0);
        if ($rating >= 1 && $rating <= 5) {
            $query->where('item_reviews.rating', $rating);
        }

        return $query
            ->orderByRaw('COALESCE(item_reviews.submitted_at, item_reviews.created_at) DESC')
            ->orderByDesc('item_reviews.id');
    }

    private function resolvedSatkerExpression(): string
    {
        return 'COALESCE(review_allocations.satker_id, review_users.satker_id)';
    }
}
