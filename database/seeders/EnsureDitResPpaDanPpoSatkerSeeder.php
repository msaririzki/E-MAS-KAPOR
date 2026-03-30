<?php

namespace Database\Seeders;

use App\Models\Satker;
use Illuminate\Database\Seeder;

class EnsureDitResPpaDanPpoSatkerSeeder extends Seeder
{
    public function run(): void
    {
        $polda = Satker::query()->firstOrCreate(
            ['code' => 'POLDA-NTB'],
            [
                'name' => 'Polda NTB',
                'sort_order' => 0,
            ]
        );

        Satker::query()->updateOrCreate(
            ['code' => 'RESPPAPPO'],
            [
                'name' => 'DIT RES PPA DAN PPO',
                'parent_id' => $polda->id,
                'sort_order' => 16,
            ]
        );
    }
}
