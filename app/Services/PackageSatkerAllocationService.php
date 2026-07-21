<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Satker;
use App\Models\Setting;
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

        if ($this->shouldReadSnapshot($packageItems)) {
            return $this->buildRowsFromAllocationSnapshots($packageItems, $satker);
        }

        $personnelRows = [];
        $exportedPersonnelItems = [];

        foreach ($packageItems as $packageItem) {
            $recipients = $packageItem->recipients->where('satker_id', $satker->id);

            if ($recipients->isEmpty()) {
                continue;
            }

            $kaporItem = $packageItem->kaporItem;

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
                    ->chunk(500, function ($personnels) use (&$personnelRows, &$exportedPersonnelItems, $kaporItem, $packageItem) {
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
                            $personnelRows[$personnel->id]['sizes'][] = $this->sizeDisplayValue(
                                $kaporItem->item_name,
                                $personnel->kapor_sizes,
                                $kaporItem->unit,
                            );
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

    private function shouldReadSnapshot(Collection $packageItems): bool
    {
        $activeFiscalYear = (int) Setting::getValue('fiscal_year', date('Y'));

        foreach ($packageItems as $packageItem) {
            $packageItem->loadMissing('budgetPackage.budgetYear');
            $package = $packageItem->budgetPackage;
            $year = (int) ($package?->budgetYear?->year ?? 0);

            if ($package?->status === 'archived' || ($year > 0 && $year < $activeFiscalYear)) {
                return true;
            }
        }

        return false;
    }

    private function buildRowsFromAllocationSnapshots(Collection $packageItems, Satker $satker): Collection
    {
        $packageItemIds = $packageItems->pluck('id')->filter()->values();

        if ($packageItemIds->isEmpty()) {
            return collect();
        }

        $allocations = PersonnelItemAllocation::query()
            ->whereIn('package_item_id', $packageItemIds)
            ->where('satker_id', $satker->id)
            ->orderBy('full_name_snapshot')
            ->get();

        $personnelRows = [];
        $exportedPersonnelItems = [];

        foreach ($allocations as $allocation) {
            $personnelKey = $allocation->user_id ?: $allocation->personnel_id ?: $allocation->nrp_snapshot ?: $allocation->full_name_snapshot;
            $exportKey = $allocation->package_item_id.'-'.$personnelKey;

            if (isset($exportedPersonnelItems[$exportKey])) {
                continue;
            }

            $exportedPersonnelItems[$exportKey] = true;

            if (! isset($personnelRows[$personnelKey])) {
                $personnelRows[$personnelKey] = [
                    'personnel_id' => $allocation->personnel_id,
                    'full_name' => $allocation->full_name_snapshot,
                    'nrp' => $allocation->nrp_snapshot ?? '-',
                    'rank' => $allocation->rank_snapshot ?? '-',
                    'jabatan' => $allocation->jabatan_snapshot ?? '-',
                    'bagian' => $allocation->bagian_snapshot ?? '-',
                    'personnel_type' => $allocation->personnel_type_snapshot ?? '-',
                    'gender' => $this->genderLabel($allocation->gender_snapshot),
                    'items' => [],
                    'categories' => [],
                    'sizes' => [],
                ];
            }

            $personnelRows[$personnelKey]['items'][] = $allocation->kapor_item_name_snapshot;
            $personnelRows[$personnelKey]['categories'][] = $allocation->item_category_snapshot ?? '-';
            $personnelRows[$personnelKey]['sizes'][] = $this->sizeDisplayValue(
                $allocation->kapor_item_name_snapshot,
                $allocation->kapor_sizes_snapshot,
            );
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

    public function sizeDisplayMeta(string $itemName, array|string|null $kaporSizes, ?string $unit = null): array
    {
        $primaryKey = $this->sizeKeyFor($itemName);

        if ($this->needsCompanionPants($itemName, $unit)) {
            return [
                'label' => 'Ukuran Baju / Celana',
                'value' => $this->sizeValue($kaporSizes, $primaryKey).' / '.$this->sizeValue($kaporSizes, 'celana'),
            ];
        }

        return [
            'label' => $this->kaporRequirementService->sizeLabel($primaryKey),
            'value' => $this->sizeValue($kaporSizes, $primaryKey),
        ];
    }

    public function sizeDisplayValue(string $itemName, array|string|null $kaporSizes, ?string $unit = null): string
    {
        return $this->sizeDisplayMeta($itemName, $kaporSizes, $unit)['value'];
    }

    private function genderLabel(?string $gender): string
    {
        return match ($gender) {
            'L' => 'Laki-laki',
            'P' => 'Perempuan',
            default => '-',
        };
    }

    private function needsCompanionPants(string $itemName, ?string $unit = null): bool
    {
        $name = strtoupper($itemName);
        $unit = strtoupper((string) $unit);
        $isStel = $unit === 'STEL';
        $isAlreadyPants = str_contains($name, 'CELANA') || str_contains($name, 'ROK');
        $isNonClothing = str_contains($name, 'TOPI')
            || str_contains($name, 'SEPATU')
            || str_contains($name, 'JILBAB')
            || str_contains($name, 'SABUK')
            || str_contains($name, 'BARET')
            || str_contains($name, 'PECI')
            || str_contains($name, 'PET');

        return $isStel && ! $isAlreadyPants && ! $isNonClothing;
    }
}
