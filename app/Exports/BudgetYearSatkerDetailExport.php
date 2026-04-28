<?php

namespace App\Exports;

use App\Models\BudgetYear;
use App\Models\Satker;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BudgetYearSatkerDetailExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly BudgetYear $budgetYear)
    {
        $this->budgetYear->load([
            'packages.items.kaporItem',
            'packages.items.recipients.satker',
        ]);
    }

    public function sheets(): array
    {
        $satkerIds = $this->budgetYear->packages
            ->flatMap(fn ($package) => $package->items)
            ->flatMap(fn ($item) => $item->recipients->pluck('satker_id'))
            ->filter()
            ->unique()
            ->values();

        $satkers = Satker::query()
            ->whereIn('id', $satkerIds)
            ->orderByRaw("CASE WHEN code = 'POLDA-NTB' THEN 1 ELSE 0 END")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sheets = [];
        $usedTitles = [];

        foreach ($satkers as $satker) {
            $title = $this->uniqueSheetTitle($satker->name, $usedTitles);
            $usedTitles[] = $title;
            $sheets[] = new BudgetYearSatkerDetailSheet($this->budgetYear, $satker, $title);
        }

        return $sheets;
    }

    private function uniqueSheetTitle(string $name, array $usedTitles): string
    {
        $base = trim(str_replace(['/', '\\', '?', '*', ':', '[', ']'], ' ', $name));
        $base = $base !== '' ? $base : 'Satker';
        $base = mb_substr($base, 0, 31);
        $title = $base;
        $counter = 2;

        while (in_array($title, $usedTitles, true)) {
            $suffix = ' '.$counter;
            $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            $counter++;
        }

        return $title;
    }
}
