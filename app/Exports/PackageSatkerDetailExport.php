<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use App\Models\Satker;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PackageSatkerDetailExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private readonly BudgetPackage $budgetPackage)
    {
        $this->budgetPackage->load([
            'budgetYear',
            'items.kaporItem',
            'items.recipients.satker',
        ]);
    }

    public function sheets(): array
    {
        $satkers = Satker::query()
            ->whereIn('id', $this->budgetPackage->items
                ->flatMap(fn ($item) => $item->recipients->pluck('satker_id'))
                ->unique()
                ->values())
            ->orderByRaw("CASE WHEN code = 'POLDA-NTB' THEN 1 ELSE 0 END")
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sheets = [];
        $usedTitles = [];

        foreach ($satkers as $satker) {
            $title = $this->uniqueSheetTitle($satker->name, $usedTitles);
            $usedTitles[] = $title;
            $sheets[] = new PackageSatkerDetailSheet($this->budgetPackage, $satker, $title);
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
