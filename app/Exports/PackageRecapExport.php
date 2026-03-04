<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use App\Models\PackageItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PackageRecapExport implements WithMultipleSheets
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

        // Satu sheet per package item (atau bisa di group per nama item, tapi lebih aman per package item
        // karena filter personilnya bisa berbeda-beda tiap item).
        foreach ($this->budgetPackage->items as $packageItem) {
            $kaporItem = $packageItem->kaporItem;
            
            // Nama sheet maksimal 31 karakter, dan hindari karakter spesial
            $sheetName = substr(str_replace(['/', '\\', '?', '*', ':', '[', ']'], ' ', $kaporItem->item_name), 0, 31);
            
            // Buat instance sheet
            $sheets[] = new PackageItemSheet($packageItem, $sheetName, $this->budgetPackage);
        }

        return $sheets;
    }
}
