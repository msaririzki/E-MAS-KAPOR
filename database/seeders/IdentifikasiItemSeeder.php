<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\KaporItem;
use App\Models\IdentifikasiItem;

class IdentifikasiItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kaporItems = KaporItem::where('is_active', true)->where('for_identifikasi', true)->get();

        $merged = [];

        foreach ($kaporItems as $item) {
            $name = $item->item_name;
            // Clean up name by removing suffixes
            $name = preg_replace('/\b(PRIA|WANITA|BINTARA|PAMA|PAMEN|PATI|TAMTAMA|PNS)\b/i', '', $name);
            $baseName = trim(preg_replace('/\s+/', ' ', $name));

            if (!isset($merged[$baseName])) {
                $merged[$baseName] = [
                    'item_name' => $baseName,
                    'category' => $item->category,
                    'description' => $item->description,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('identifikasi_items')->insert(array_values($merged));
    }
}
