<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            RolePermissionSeeder::class,
            RankSeeder::class,
            SatkerSeeder::class,
            BagianOptionSeeder::class,
            KaporItemSeeder::class,
            IdentifikasiItemSeeder::class,
            EligibleSatkerCountSeeder::class,
            SettingSeeder::class,
        ];

        if (app()->environment(['local', 'testing'])) {
            $seeders[] = DemoUserSeeder::class;
            $seeders[] = PolrestaMataramKebutuhanSeeder::class;
            $seeders[] = ManySatkerKebutuhanSeeder::class;
        }

        $seeders[] = ItemReviewSeeder::class;

        $this->call($seeders);
    }
}
