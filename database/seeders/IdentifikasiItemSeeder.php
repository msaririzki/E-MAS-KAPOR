<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use App\Models\KaporItem;
use App\Models\KebutuhanItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdentifikasiItemSeeder extends Seeder
{
    private const CATEGORY_ORDER_SQL = "CASE
        WHEN category = 'Tutup_Kepala' THEN 1
        WHEN category = 'Tutup_Badan' THEN 2
        WHEN category = 'Tutup_Kaki' THEN 3
        ELSE 999 END";

    private const MERGE_SUFFIX_PATTERN = '/\b(PRIA|WANITA|BINTARA|PAMA|PAMEN|PATI|TAMTAMA|TAMATAMA|PNS)\b/i';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = $this->standardItems();

        if ($items === []) {
            $this->command?->warn('Tidak ada item kapor aktif yang ditandai untuk identifikasi. Seeder item identifikasi dilewati.');

            return;
        }

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                $this->syncItem($item);
            }
        });

        $this->command?->info('Item Identifikasi Kebutuhan disinkronkan: '.count($items).' item standar aktif.');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function standardItems(): array
    {
        $items = [];

        KaporItem::query()
            ->where('is_active', true)
            ->where('for_identifikasi', true)
            ->orderByRaw(self::CATEGORY_ORDER_SQL)
            ->orderBy('item_name')
            ->get()
            ->each(function (KaporItem $item) use (&$items): void {
                $itemName = $this->normalizeItemName($item->item_name);
                $key = $this->itemKey($item->category, $itemName);

                $items[$key] ??= [
                    'item_name' => $itemName,
                    'category' => $item->category,
                    'description' => $item->description,
                ];
            });

        return $items;
    }

    /**
     * Menggabungkan variasi gender/pangkat dari item kapor menjadi item identifikasi standar.
     */
    private function normalizeItemName(string $itemName): string
    {
        $fixedNames = [
            'JILBAB POLRI DAN PNS' => 'JILBAB POLRI/PNS',
            'SEPATU PDL II PAMEN, PAMA, BINTARA DAN TAMATAMA' => 'SEPATU PDL II PAMEN/PAMA/BINTARA/TAMTAMA',
        ];

        if (isset($fixedNames[$itemName])) {
            return $fixedNames[$itemName];
        }

        $itemName = preg_replace(self::MERGE_SUFFIX_PATTERN, '', $itemName) ?? $itemName;

        $itemName = preg_replace('/\s+/', ' ', $itemName) ?? $itemName;
        $itemName = preg_replace('/\s+([,\/])\s+/', '$1 ', $itemName) ?? $itemName;
        $itemName = preg_replace('/\s*,\s*,+/', ',', $itemName) ?? $itemName;
        $itemName = str_replace(
            [
                'JILBAB POLRI DAN',
                'SEPATU PDL II, , DAN',
                'SEPATU PDL II , , DAN',
            ],
            [
                'JILBAB POLRI/PNS',
                'SEPATU PDL II PAMEN/PAMA/BINTARA/TAMTAMA',
                'SEPATU PDL II PAMEN/PAMA/BINTARA/TAMTAMA',
            ],
            $itemName,
        );

        return trim($itemName, " \t\n\r\0\x0B,");
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function syncItem(array $item): IdentifikasiItem
    {
        $matches = IdentifikasiItem::query()
            ->where('item_name', $item['item_name'])
            ->where('category', $item['category'])
            ->orderBy('id')
            ->get();

        $identifikasiItem = $matches->first() ?? new IdentifikasiItem([
            'item_name' => $item['item_name'],
            'category' => $item['category'],
        ]);

        $identifikasiItem->fill([
            'description' => $item['description'],
            'is_active' => true,
        ]);
        $identifikasiItem->save();

        $duplicateIds = $matches
            ->pluck('id')
            ->filter(fn (int $id): bool => $id !== $identifikasiItem->id)
            ->values();

        if ($duplicateIds->isNotEmpty()) {
            $this->moveKebutuhanItems($duplicateIds->all(), $identifikasiItem->id);

            IdentifikasiItem::query()
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        return $identifikasiItem;
    }

    /**
     * @param  array<int, int>  $fromIds
     */
    private function moveKebutuhanItems(array $fromIds, int $targetId): void
    {
        KebutuhanItem::query()
            ->whereIn('identifikasi_item_id', $fromIds)
            ->whereExists(function ($query) use ($targetId) {
                $query->selectRaw('1')
                    ->from('kebutuhan_items as existing_items')
                    ->whereColumn('existing_items.kebutuhan_id', 'kebutuhan_items.kebutuhan_id')
                    ->where('existing_items.identifikasi_item_id', $targetId);
            })
            ->delete();

        KebutuhanItem::query()
            ->whereIn('identifikasi_item_id', $fromIds)
            ->update(['identifikasi_item_id' => $targetId]);
    }

    private function itemKey(string $category, string $itemName): string
    {
        return $category.'|'.$itemName;
    }
}
