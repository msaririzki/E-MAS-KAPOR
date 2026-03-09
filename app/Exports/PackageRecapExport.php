<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PackageRecapExport implements WithMultipleSheets
{
    use Exportable;

    protected $budgetPackage;

    // Ukuran celana standar untuk companion sheet
    private array $celanaPriaSizes = [
        '27', '28', '29', '30', '31', '32', '33', '34', '35',
        '36', '37', '38', '39', '40', '41', '42', '43', '44',
        '45', '46', '47', '48', '49', '50',
    ];

    private array $celanaWanitaSizes = [
        'K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB',
    ];

    public function __construct(BudgetPackage $budgetPackage)
    {
        $this->budgetPackage = $budgetPackage;
        $this->budgetPackage->load(['items.kaporItem', 'items.recipients']);
    }

    /**
     * Deteksi apakah item ini 1 stel (baju + celana).
     * Item STEL yang bukan celana perlu sheet celana tambahan.
     */
    private function needsCelanaSheet(object $packageItem): bool
    {
        $unit = strtoupper($packageItem->kaporItem->unit ?? '');
        $name = strtoupper($packageItem->kaporItem->item_name);

        // Item yang satuannya STEL dan BUKAN celana/rok/topi/sepatu/jilbab/sabuk
        $isStel = $unit === 'STEL';

        $isAlreadyCelana = str_contains($name, 'CELANA') || str_contains($name, 'ROK');
        $isNonClothing = str_contains($name, 'TOPI') || str_contains($name, 'SEPATU')
            || str_contains($name, 'JILBAB') || str_contains($name, 'SABUK')
            || str_contains($name, 'BARET') || str_contains($name, 'PECI')
            || str_contains($name, 'PET');

        return $isStel && ! $isAlreadyCelana && ! $isNonClothing;
    }

    /**
     * Deteksi apakah item ini olahraga (pria+wanita ukuran sama, gabung 1 sheet).
     */
    private function isOlahraga(object $packageItem): bool
    {
        $name = strtoupper($packageItem->kaporItem->item_name);

        return str_contains($name, 'OLAHRAGA') || str_contains($name, 'T-SHIRT') || str_contains($name, 'T SHIRT');
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
                    $gendersInItem['L'] = true;
                    $gendersInItem['P'] = true;
                } else {
                    foreach ($filterGenders as $g) {
                        $gendersInItem[$g] = true;
                    }
                }
            }

            if (empty($gendersInItem)) {
                $gendersInItem = ['L' => true, 'P' => true];
            }

            $hasCelana = $this->needsCelanaSheet($packageItem);
            $isOlahraga = $this->isOlahraga($packageItem);

            // ── OLAHRAGA: 1 sheet gabungan pria+wanita ──
            if ($isOlahraga && isset($gendersInItem['L']) && isset($gendersInItem['P'])) {
                $sheetName = substr(trim($baseName), 0, 31);
                $sheets[] = new PackageItemSheet(
                    $packageItem, $sheetName, $this->budgetPackage, null,
                    null, null, null, true // combinedGender = true
                );
                continue; // skip pembuatan sheet terpisah
            }

            // ── Sheet BAJU ──────────────────────────────
            if (isset($gendersInItem['L'])) {
                $label = $hasCelana ? ' Baju Pria' : ' Pria';
                $sheetName = substr(trim($baseName) . $label, 0, 31);
                $sheets[] = new PackageItemSheet(
                    $packageItem, $sheetName, $this->budgetPackage, 'L',
                    null, $hasCelana ? 'Ukuran Baju' : null
                );
            }

            if (isset($gendersInItem['P'])) {
                $label = $hasCelana ? ' Baju Wanita' : ' Wanita';
                $sheetName = substr(trim($baseName) . $label, 0, 31);
                $sheets[] = new PackageItemSheet(
                    $packageItem, $sheetName, $this->budgetPackage, 'P',
                    null, $hasCelana ? 'Ukuran Baju' : null
                );
            }

            // ── Sheet CELANA (companion untuk item STEL) ──
            if ($hasCelana) {
                if (isset($gendersInItem['L'])) {
                    $sheetName = substr(trim($baseName) . ' Celana Pria', 0, 31);
                    $sheets[] = new PackageItemSheet(
                        $packageItem, $sheetName, $this->budgetPackage, 'L',
                        'celana', 'Ukuran Celana', $this->celanaPriaSizes
                    );
                }

                if (isset($gendersInItem['P'])) {
                    $sheetName = substr(trim($baseName) . ' Celana Wanita', 0, 31);
                    $sheets[] = new PackageItemSheet(
                        $packageItem, $sheetName, $this->budgetPackage, 'P',
                        'celana', 'Ukuran Celana', $this->celanaWanitaSizes
                    );
                }
            }
        }

        return $sheets;
    }
}
