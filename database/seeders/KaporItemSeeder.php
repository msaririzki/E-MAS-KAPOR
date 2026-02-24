<?php

namespace Database\Seeders;

use App\Models\KaporItem;
use App\Models\KaporSize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class KaporItemSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        Schema::disableForeignKeyConstraints();
        KaporSize::truncate();
        KaporItem::truncate();
        Schema::enableForeignKeyConstraints();

        $items = [
            // --- TUTUP KEPALA ---
            ['category' => 'Tutup_Kepala', 'item_name' => 'Topi Lapangan', 'sizes_config' => ['U' => range(54, 60)]],
            ['category' => 'Tutup_Kepala', 'item_name' => 'Pet', 'sizes_config' => ['U' => range(54, 60)]],
            ['category' => 'Tutup_Kepala', 'item_name' => 'Baret', 'sizes_config' => ['U' => range(54, 60)]],
            ['category' => 'Tutup_Kepala', 'item_name' => 'Peci', 'sizes_config' => ['U' => range(54, 60)]],
            ['category' => 'Tutup_Kepala', 'item_name' => 'Jilbab', 'sizes_config' => ['U' => ['S', 'M', 'L', 'XL']]],

            // --- TUTUP BADAN ---
            ['category' => 'Tutup_Badan', 'item_name' => 'Kemeja (PDH/PDL)', 'sizes_config' => ['L' => ['14', '14.5', '15', '15.5', '16', '16.5', '17', '18'], 'P' => ['K', 'SD', 'B', 'EB']]],
            ['category' => 'Tutup_Badan', 'item_name' => 'Celana/Rok', 'sizes_config' => ['L' => range(28, 42), 'P' => ['K', 'SD', 'B', 'EB']]],
            ['category' => 'Tutup_Badan', 'item_name' => 'Jaket', 'sizes_config' => ['U' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']]],
            ['category' => 'Tutup_Badan', 'item_name' => 'T-Shirt/Olahraga', 'sizes_config' => ['U' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']]],

            // --- TUTUP KAKI ---
            ['category' => 'Tutup_Kaki', 'item_name' => 'Sepatu Dinas', 'sizes_config' => ['L' => range(38, 46), 'P' => range(36, 42)]],
            ['category' => 'Tutup_Kaki', 'item_name' => 'Sepatu Olahraga', 'sizes_config' => ['U' => range(36, 46)]],
        ];

        foreach ($items as $itemData) {
            $sizesConfig = $itemData['sizes_config'];
            unset($itemData['sizes_config']);

            // Create Item
            $item = KaporItem::create($itemData);

            // Create Sizes
            foreach ($sizesConfig as $genderKey => $sizeList) {
                // Map 'U' to null for database
                $gender = ($genderKey === 'U') ? null : $genderKey;

                $order = 1;
                foreach ($sizeList as $sizeLabel) {
                    KaporSize::create([
                        'kapor_item_id' => $item->id,
                        'size_label' => (string)$sizeLabel,
                        'gender' => $gender,
                        'sort_order' => $order++,
                    ]);
                }
            }
        }
    }
}
