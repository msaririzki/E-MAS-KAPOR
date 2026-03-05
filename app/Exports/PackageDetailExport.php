<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PackageDetailExport implements WithMultipleSheets
{
    use Exportable;

    protected $budgetPackage;

    public function __construct(BudgetPackage $budgetPackage)
    {
        $this->budgetPackage = $budgetPackage;
        $this->budgetPackage->load(['items.kaporItem', 'items.kaporItem.sizes']);
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->budgetPackage->items as $packageItem) {
            $kaporItem = $packageItem->kaporItem;

            // Nama sheet maksimal 31 karakter, hindari karakter spesial
            $sheetName = substr(str_replace(['/', '\\', '?', '*', ':', '[', ']'], ' ', $kaporItem->item_name), 0, 31);

            $sheets[] = new PackageDetailSheet($packageItem, $sheetName, $this->budgetPackage);
        }

        return $sheets;
    }
}
