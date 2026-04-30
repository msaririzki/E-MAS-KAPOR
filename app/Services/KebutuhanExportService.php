<?php

namespace App\Services;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\KebutuhanItem;
use App\Models\Satker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KebutuhanExportService
{
    public function build(?int $fiscalYear = null, bool $includeSatkers = false): array
    {
        $fiscalYear ??= (int) (Kebutuhan::max('fiscal_year') ?: ((int) date('Y') + 1));
        $totalSatkers = Satker::count();

        $itemCounts = KebutuhanItem::query()
            ->select('identifikasi_item_id', DB::raw('COUNT(DISTINCT kebutuhans.satker_id) as satker_count'))
            ->join('kebutuhans', 'kebutuhans.id', '=', 'kebutuhan_items.kebutuhan_id')
            ->where('kebutuhans.fiscal_year', (string) $fiscalYear)
            ->whereIn('kebutuhans.status', ['diajukan', 'disetujui'])
            ->groupBy('identifikasi_item_id')
            ->pluck('satker_count', 'identifikasi_item_id');

        $itemSatkers = [];
        if ($includeSatkers) {
            $satkerRows = KebutuhanItem::query()
                ->select('identifikasi_item_id', 'satkers.name as satker_name')
                ->join('kebutuhans', 'kebutuhans.id', '=', 'kebutuhan_items.kebutuhan_id')
                ->join('satkers', 'satkers.id', '=', 'kebutuhans.satker_id')
                ->where('kebutuhans.fiscal_year', (string) $fiscalYear)
                ->whereIn('kebutuhans.status', ['diajukan', 'disetujui'])
                ->get();
                
            foreach ($satkerRows as $row) {
                $itemSatkers[$row->identifikasi_item_id][] = $row->satker_name;
            }
            
            foreach ($itemSatkers as $itemId => $names) {
                $uniqueNames = array_unique($names);
                sort($uniqueNames);
                $itemSatkers[$itemId] = $uniqueNames;
            }
        }

        $categoryGroups = IdentifikasiItem::query()
            ->where('is_active', true)
            ->orderByRaw("CASE
                WHEN category = 'Tutup_Kepala' THEN 1
                WHEN category = 'Tutup_Badan' THEN 2
                WHEN category = 'Tutup_Kaki' THEN 3
                ELSE 999 END")
            ->orderBy('item_name')
            ->get()
            ->groupBy('category')
            ->map(function (Collection $items, string $category) use ($itemCounts, $totalSatkers, $includeSatkers, $itemSatkers): array {
                $mappedItems = $items
                    ->map(function (IdentifikasiItem $item) use ($itemCounts, $totalSatkers, $includeSatkers, $itemSatkers): array {
                        $satkerCount = (int) ($itemCounts[$item->id] ?? 0);
                        // Gunakan eligible_satker_count jika diisi, jika tidak pakai total satker aplikasi
                        $eligible = $item->eligible_satker_count ?? $totalSatkers;

                        return [
                            'name'            => $item->item_name,
                            'satker_count'    => $satkerCount,
                            'eligible_count'  => $eligible,
                            'percentage'      => $this->percentage($satkerCount, $eligible),
                            'satkers'         => $includeSatkers ? ($itemSatkers[$item->id] ?? []) : [],
                        ];
                    })
                    ->filter(fn (array $item): bool => $item['satker_count'] > 0)
                    ->sortByDesc('satker_count')
                    ->values();

                return [
                    'name'         => str_replace('_', ' ', $category),
                    'items'        => $mappedItems,
                    'satker_count' => (int) $mappedItems->sum('satker_count'),
                    'percentage'   => $mappedItems->isNotEmpty()
                        ? (int) round($mappedItems->avg('percentage'))
                        : 0,
                ];
            })
            ->filter(fn (array $category): bool => $category['items']->isNotEmpty())
            ->values();

        $submittedSatkers = Kebutuhan::query()
            ->where('fiscal_year', (string) $fiscalYear)
            ->whereIn('status', ['diajukan', 'disetujui'])
            ->distinct('satker_id')
            ->count('satker_id');

        return [
            'fiscalYear' => $fiscalYear,
            'generatedAt' => now(),
            'totalSatkers' => $totalSatkers,
            'submittedSatkers' => $submittedSatkers,
            'totalItems' => (int) $categoryGroups->sum(fn (array $category): int => $category['items']->count()),
            'categoryGroups' => $categoryGroups,
        ];
    }

    private function percentage(int $count, int $total): int
    {
        return (int) round(($count / max($total, 1)) * 100);
    }
}
