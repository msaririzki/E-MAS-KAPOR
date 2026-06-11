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
            $desiredIds = [];

            foreach ($items as $item) {
                $desiredIds[] = $this->syncItem($item)->id;
            }

            $this->pruneNonStandardItems($desiredIds);
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
                $itemName = trim($item->item_name);
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

    /**
     * @param  array<int, int>  $desiredIds
     */
    private function pruneNonStandardItems(array $desiredIds): void
    {
        KebutuhanItem::query()
            ->whereNotIn('identifikasi_item_id', $desiredIds)
            ->delete();

        IdentifikasiItem::query()
            ->whereNotIn('id', $desiredIds)
            ->delete();
    }

    private function itemKey(string $category, string $itemName): string
    {
        return $category.'|'.$itemName;
    }
}
