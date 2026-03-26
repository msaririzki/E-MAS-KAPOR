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
            KaporItemSeeder::class,
            SettingSeeder::class,
            TestimonialSeeder::class,
        ];

        if (app()->environment(['local', 'testing'])) {
            $seeders[] = DemoUserSeeder::class;
        }

        $this->call($seeders);
    }
}
