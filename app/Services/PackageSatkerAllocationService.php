<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\Personnel;
use App\Models\Satker;
use Illuminate\Support\Collection;

class PackageSatkerAllocationService
{
    public function __construct(
        private readonly KaporRequirementService $kaporRequirementService,
    ) {}

    public function buildRows(BudgetPackage $budgetPackage, Satker $satker): Collection
    {
        $budgetPackage->loadMissing([
            'items.kaporItem',
            'items.recipients.satker',
        ]);

        return $this->buildRowsForPackageItems($budgetPackage->items, $satker);
    }

    public function buildRowsForBudgetYearPackages(Collection $budgetPackages, Satker $satker): Collection
    {
        $packageItems = $budgetPackages
            ->each(function (BudgetPackage $budgetPackage): void {
                $budgetPackage->loadMissing([
                    'items.kaporItem',
                    'items.recipients.satker',
                ]);
            })
            ->flatMap(fn (BudgetPackage $budgetPackage) => $budgetPackage->items);

        return $this->buildRowsForPackageItems($packageItems, $satker);
    }

    private function buildRowsForPackageItems(Collection $packageItems, Satker $satker): Collection
    {
        $packageItems = $packageItems->filter();

        $personnelRows = [];
        $exportedPersonnelItems = [];

        foreach ($packageItems as $packageItem) {
            $recipients = $packageItem->recipients->where('satker_id', $satker->id);

            if ($recipients->isEmpty()) {
                continue;
            }

            $kaporItem = $packageItem->kaporItem;
            $sizeKey = $this->sizeKeyFor($kaporItem->item_name);

            foreach ($recipients as $recipient) {
                $query = Personnel::query()
                    ->where('satker_id', $satker->id)
                    ->where('is_active', true)
                    ->select([
                        'id',
                        'full_name',
                        'nrp',
                        'rank_id',
                        'jabatan',
                        'bagian',
                        'gender',
                        'personnel_type',
                        'kapor_sizes',
                    ]);

                $this->kaporRequirementService->applyRecipientFilters(
                    $query,
                    $recipient->recipient_filters ?? [],
                    $satker,
                );

                $query
                    ->with('rank:id,name')
                    ->orderBy('full_name')
                    ->chunk(500, function ($personnels) use (&$personnelRows, &$exportedPersonnelItems, $kaporItem, $packageItem, $sizeKey) {
                        foreach ($personnels as $personnel) {
                            $exportKey = $packageItem->id.'-'.$personnel->id;

                            if (isset($exportedPersonnelItems[$exportKey])) {
                                continue;
                            }

                            $exportedPersonnelItems[$exportKey] = true;

                            if (! isset($personnelRows[$personnel->id])) {
                                $personnelRows[$personnel->id] = [
                                    'personnel_id' => $personnel->id,
                                    'full_name' => $personnel->full_name,
                                    'nrp' => $personnel->nrp ?? '-',
                                    'rank' => $personnel->rank?->name ?? '-',
                                    'jabatan' => $personnel->jabatan ?? '-',
                                    'bagian' => $personnel->bagian ?? '-',
                                    'personnel_type' => $personnel->personnel_type ?? '-',
                                    'gender' => $this->genderLabel($personnel->gender),
                                    'items' => [],
                                    'categories' => [],
                                    'sizes' => [],
                                ];
                            }

                            $personnelRows[$personnel->id]['items'][] = $kaporItem->item_name;
                            $personnelRows[$personnel->id]['categories'][] = $kaporItem->category ?? '-';
                            $personnelRows[$personnel->id]['sizes'][] = $this->sizeValue($personnel->kapor_sizes, $sizeKey);
                        }
                    });
            }
        }

        return collect($personnelRows)
            ->map(function (array $row) {
                $row['item_count'] = count($row['items']);

                return $row;
            })
            ->sortBy('full_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function sizeKeyFor(string $itemName): string
    {
        $name = strtoupper($itemName);

        if (str_contains($name, 'TOPI') || str_contains($name, 'PET') || str_contains($name, 'BARET') || str_contains($name, 'PECI')) {
            return 'topi';
        }
        if (str_contains($name, 'JILBAB')) {
            return 'jilbab';
        }
        if (str_contains($name, 'CELANA') || str_contains($name, 'ROK')) {
            return 'celana';
        }
        if (str_contains($name, 'SEPATU OLAHRAGA')) {
            return 'sepatu_olahraga';
        }
        if (str_contains($name, 'SEPATU')) {
            return 'sepatu_dinas';
        }
        if (str_contains($name, 'JAKET') || str_contains($name, 'ROMPI')) {
            return 'jaket';
        }
        if (str_contains($name, 'OLAHRAGA')) {
            return 'olahraga';
        }
        if (str_contains($name, 'SABUK')) {
            return 'sabuk';
        }

        return 'kemeja';
    }

    public function sizeValue(array|string|null $kaporSizes, string $sizeKey): string
    {
        $sizes = is_string($kaporSizes) ? json_decode($kaporSizes, true) : $kaporSizes;
        $value = is_array($sizes) ? ($sizes[$sizeKey] ?? null) : null;
        $value = (string) $value;

        return filled($value) && $value !== '-' && $value !== 'null' ? $value : '-';
    }

    private function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }
}
