<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PackageRecapExport implements WithMultipleSheets
{
    use Exportable;

    protected $budgetPackage;

    public function __construct(BudgetPackage $budgetPackage)
    {
        $this->budgetPackage = $budgetPackage;
        $this->budgetPackage->load(['items.kaporItem', 'items.kaporItem.sizes', 'items.recipients']);
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->budgetPackage->items as $packageItem) {
            $kaporItem = $packageItem->kaporItem;

            // Nama sheet bersih dari karakter spesial
            $baseName = str_replace(['/', '\\', '?', '*', ':', '[', ']'], ' ', $kaporItem->item_name);

            // Kumpulkan semua gender yang ada di recipient_filters item ini
            $gendersInItem = [];
            foreach ($packageItem->recipients as $recipient) {
                $filters = $recipient->recipient_filters ?? [];
                $filterGenders = $filters['gender'] ?? [];

                if (empty($filterGenders)) {
                    // Tidak ada filter gender → berarti berlaku untuk semua gender (L dan P)
                    $gendersInItem['L'] = true;
                    $gendersInItem['P'] = true;
                } else {
                    foreach ($filterGenders as $g) {
                        $gendersInItem[$g] = true;
                    }
                }
            }

            // Jika tidak ada recipient sama sekali, buat kedua sheet sebagai fallback
            if (empty($gendersInItem)) {
                $gendersInItem = ['L' => true, 'P' => true];
            }

            // Buat sheet hanya untuk gender yang relevan
            if (isset($gendersInItem['L'])) {
                $sheetNamePria = substr(trim($baseName) . ' Pria', 0, 31);
                $sheets[] = new PackageItemSheet($packageItem, $sheetNamePria, $this->budgetPackage, 'L');
            }

            if (isset($gendersInItem['P'])) {
                $sheetNameWanita = substr(trim($baseName) . ' Wanita', 0, 31);
                $sheets[] = new PackageItemSheet($packageItem, $sheetNameWanita, $this->budgetPackage, 'P');
            }
        }

        return $sheets;
    }
}

