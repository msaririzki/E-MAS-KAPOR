<?php

namespace Database\Seeders;

use App\Models\KaporItem;
use App\Models\KaporSize;
use Illuminate\Database\Seeder;

class KaporSizeStandardSeeder extends Seeder
{
    // ── BAJU / KEMEJA ─────────────────────────────────
    private array $bajuPriaSizes = [
        '14', '14,5', '15', '15,5', '16', '16,5',
        '17', '17,5', '18', '18,5', '19', '19,5',
        '20', '21', '22',
    ];

    // ── CELANA ────────────────────────────────────────
    private array $celanaPriaSizes = [
        '27', '28', '29', '30', '31', '32', '33', '34', '35',
        '36', '37', '38', '39', '40', '41', '42', '43', '44',
        '45', '46', '47', '48', '49', '50',
    ];

    // ── WANITA (baju, celana, olahraga, jaket) ───────
    private array $wanitaSizes = [
        'K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB',
    ];

    // ── OLAHRAGA PRIA (huruf, sama dengan wanita) ────
    private array $olahragaSizes = [
        'K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB',
    ];

    // ── JAKET (huruf untuk pria DAN wanita) ──────────
    private array $jaketSizes = [
        'K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB',
    ];

    // ── TOPI PATI / POLRI (54-62) ────────────────────
    private array $topiPatiSizes = [
        '54', '55', '56', '57', '58', '59', '60', '61', '62',
    ];

    // ── TOPI PNS (54-60) ─────────────────────────────
    private array $topiPnsSizes = [
        '54', '55', '56', '57', '58', '59', '60',
    ];

    // ── SEPATU OLAHRAGA (36-48) ──────────────────────
    private array $sepatuOlahragaSizes = [
        '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48',
    ];

    // ── SEPATU DINAS (36-48, sama range) ─────────────
    private array $sepatuDinasSizes = [
        '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46', '47', '48',
    ];

    // ── SABUK (angka) ────────────────────────────────
    private array $sabukSizes = [
        '30', '32', '34', '36', '38', '40', '42', '44',
    ];

    // ── JILBAB (hanya wanita) ────────────────────────
    private array $jilbabSizes = [
        'K', 'SD', 'B',
    ];

    private function detectType(string $name): string
    {
        $upper = strtoupper($name);

        if (str_contains($upper, 'JILBAB')) {
            return 'jilbab';
        }
        if (str_contains($upper, 'SEPATU OLAHRAGA')) {
            return 'sepatu_olahraga';
        }
        if (str_contains($upper, 'SEPATU')) {
            return 'sepatu_dinas';
        }
        if (str_contains($upper, 'SABUK')) {
            return 'sabuk';
        }
        if (str_contains($upper, 'OLAHRAGA') || str_contains($upper, 'T-SHIRT') || str_contains($upper, 'T SHIRT')) {
            return 'olahraga';
        }
        if (str_contains($upper, 'CELANA') || str_contains($upper, 'ROK')) {
            return 'celana';
        }
        if (str_contains($upper, 'JAKET')) {
            return 'jaket';
        }
        if (str_contains($upper, 'TOPI') || str_contains($upper, 'PET') || str_contains($upper, 'BARET') || str_contains($upper, 'PECI')) {
            if (str_contains($upper, 'PNS') || str_contains($upper, 'PPPK')) {
                return 'topi_pns';
            }
            return 'topi_pati';
        }

        // Default: baju/kemeja
        return 'baju';
    }

    private function getSizesForType(string $type): array
    {
        return match ($type) {
            'baju' => [
                'L' => $this->bajuPriaSizes,
                'P' => $this->wanitaSizes,
            ],
            'celana' => [
                'L' => $this->celanaPriaSizes,
                'P' => $this->wanitaSizes,
            ],
            'olahraga' => [
                'L' => $this->olahragaSizes,
                'P' => $this->wanitaSizes,
            ],
            'jaket' => [
                'L' => $this->jaketSizes,
                'P' => $this->wanitaSizes,
            ],
            'topi_pati' => [
                'L' => $this->topiPatiSizes,
                'P' => $this->topiPatiSizes,
            ],
            'topi_pns' => [
                'L' => $this->topiPnsSizes,
                'P' => $this->topiPnsSizes,
            ],
            'sepatu_olahraga' => [
                'L' => $this->sepatuOlahragaSizes,
                'P' => $this->sepatuOlahragaSizes,
            ],
            'sepatu_dinas' => [
                'L' => $this->sepatuDinasSizes,
                'P' => $this->sepatuDinasSizes,
            ],
            'sabuk' => [
                'L' => $this->sabukSizes,
                'P' => $this->sabukSizes,
            ],
            'jilbab' => [
                'L' => [],
                'P' => $this->jilbabSizes,
            ],
            default => [
                'L' => $this->bajuPriaSizes,
                'P' => $this->wanitaSizes,
            ],
        };
    }

    public function run(): void
    {
        $items = KaporItem::where('is_active', true)->get();

        $deleted = KaporSize::whereIn('kapor_item_id', $items->pluck('id'))->delete();
        $this->command->info("Ukuran lama dihapus: {$deleted}");

        $totalCreated = 0;
        $summary = [];

        foreach ($items as $item) {
            $type = $this->detectType($item->item_name);
            $sizeMap = $this->getSizesForType($type);

            foreach (['L', 'P'] as $gender) {
                foreach ($sizeMap[$gender] ?? [] as $order => $label) {
                    KaporSize::create([
                        'kapor_item_id' => $item->id,
                        'size_label'    => $label,
                        'gender'        => $gender,
                        'sort_order'    => $order + 1,
                    ]);
                    $totalCreated++;
                }
            }

            $summary[$type] = ($summary[$type] ?? 0) + 1;
        }

        $this->command->info("Seeder selesai: {$totalCreated} ukuran ditambahkan untuk {$items->count()} item.");
        foreach ($summary as $type => $count) {
            $this->command->info("  - {$type}: {$count} item");
        }
    }
}
