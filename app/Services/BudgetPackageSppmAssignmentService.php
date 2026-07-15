<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\BudgetPackageSppmAssignment;
use App\Models\Personnel;
use App\Models\Satker;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BudgetPackageSppmAssignmentService
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService,
    ) {}

    public function sourceSatkers(BudgetPackage $budgetPackage): Collection
    {
        $budgetPackage->loadMissing('items.recipients.satker');

        return $budgetPackage->items
            ->flatMap(fn ($item) => $item->recipients)
            ->pluck('satker')
            ->filter()
            ->unique('id')
            ->sortBy('sort_order')
            ->values();
    }

    public function eligibleRows(BudgetPackage $budgetPackage, Satker $sourceSatker, ?string $search = null): Collection
    {
        $budgetPackage->loadMissing([
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        $rows = [];

        foreach ($budgetPackage->items as $packageItem) {
            $recipients = $packageItem->recipients->where('satker_id', $sourceSatker->id);

            foreach ($recipients as $recipient) {
                $query = Personnel::query()
                    ->with(['rank:id,name', 'satker:id,name'])
                    ->where('satker_id', $sourceSatker->id)
                    ->where('is_active', true)
                    ->select([
                        'id',
                        'full_name',
                        'nrp',
                        'rank_id',
                        'jabatan',
                        'bagian',
                        'satker_id',
                    ]);

                $this->kaporRequirementService->applyRecipientFilters(
                    $query,
                    $recipient->recipient_filters ?? [],
                    $sourceSatker,
                );

                $query->orderBy('full_name')
                    ->chunkById(500, function ($personnels) use (&$rows, $packageItem): void {
                        foreach ($personnels as $personnel) {
                            $rows[$personnel->id] ??= [
                                'personnel_id' => $personnel->id,
                                'full_name' => $personnel->full_name,
                                'nrp' => $personnel->nrp ?? '-',
                                'rank' => $personnel->rank?->name ?? '-',
                                'jabatan' => $personnel->jabatan ?? '-',
                                'bagian' => $personnel->bagian ?? '-',
                                'item_ids' => [],
                                'item_names' => [],
                            ];

                            if (! in_array($packageItem->id, $rows[$personnel->id]['item_ids'], true)) {
                                $rows[$personnel->id]['item_ids'][] = $packageItem->id;
                                $rows[$personnel->id]['item_names'][] = $packageItem->kaporItem?->item_name ?? 'Item';
                            }
                        }
                    }, 'id');
            }
        }

        $assignments = BudgetPackageSppmAssignment::query()
            ->with(['sppmSatker:id,name', 'assignedBy:id,name'])
            ->where('budget_package_id', $budgetPackage->id)
            ->where('original_satker_id', $sourceSatker->id)
            ->get()
            ->keyBy('personnel_id');

        return collect($rows)
            ->map(function (array $row) use ($assignments): array {
                $assignment = $assignments->get($row['personnel_id']);

                $row['item_count'] = count($row['item_ids']);
                $row['item_preview'] = implode(', ', array_slice($row['item_names'], 0, 3));
                $row['assignment'] = $assignment;

                return $row;
            })
            ->when(filled($search), function (Collection $rows) use ($search): Collection {
                $needle = Str::lower(trim((string) $search));

                return $rows->filter(function (array $row) use ($needle): bool {
                    return Str::contains(Str::lower($row['full_name']), $needle)
                        || Str::contains(Str::lower($row['nrp']), $needle)
                        || Str::contains(Str::lower($row['rank']), $needle)
                        || Str::contains(Str::lower($row['jabatan']), $needle)
                        || Str::contains(Str::lower($row['bagian']), $needle);
                });
            })
            ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function buildSppmSatkerData(BudgetPackage $budgetPackage): array
    {
        $budgetPackage->loadMissing([
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        $assignments = BudgetPackageSppmAssignment::query()
            ->where('budget_package_id', $budgetPackage->id)
            ->get(['personnel_id', 'sppm_satker_id'])
            ->keyBy('personnel_id');

        $satkerData = [];
        $satkerIds = [];

        foreach ($budgetPackage->items as $packageItem) {
            $kaporItem = $packageItem->kaporItem;

            if ($kaporItem === null) {
                continue;
            }

            $price = (float) ($packageItem->custom_price ?? $kaporItem->price ?? 0);

            foreach ($packageItem->recipients as $recipient) {
                $sourceSatker = $recipient->satker;

                if ($sourceSatker === null) {
                    continue;
                }

                $query = Personnel::query()
                    ->where('satker_id', $sourceSatker->id)
                    ->where('is_active', true)
                    ->select(['id', 'satker_id']);

                $this->kaporRequirementService->applyRecipientFilters(
                    $query,
                    $recipient->recipient_filters ?? [],
                    $sourceSatker,
                );

                $query->chunkById(500, function ($personnels) use (&$satkerData, &$satkerIds, $assignments, $packageItem, $kaporItem, $price): void {
                    foreach ($personnels as $personnel) {
                        $effectiveSatkerId = (int) ($assignments->get($personnel->id)?->sppm_satker_id ?? $personnel->satker_id);
                        $itemKey = $packageItem->id;
                        $satkerIds[$effectiveSatkerId] = $effectiveSatkerId;

                        $satkerData[$effectiveSatkerId]['items'][$itemKey] ??= [
                            'item_name' => $kaporItem->item_name,
                            'unit' => $kaporItem->unit ?? 'PCS',
                            'price' => $price,
                            'qty' => 0,
                            'total' => 0,
                        ];

                        $satkerData[$effectiveSatkerId]['items'][$itemKey]['qty']++;
                        $satkerData[$effectiveSatkerId]['items'][$itemKey]['total'] += $price;
                    }
                }, 'id');
            }
        }

        $satkers = Satker::query()
            ->whereIn('id', array_values($satkerIds))
            ->get()
            ->keyBy('id');

        foreach ($satkerData as $satkerId => $data) {
            $satkerData[$satkerId]['satker'] = $satkers->get($satkerId);
            $satkerData[$satkerId]['items'] = array_values($data['items'] ?? []);
        }

        return $satkerData;
    }
}
