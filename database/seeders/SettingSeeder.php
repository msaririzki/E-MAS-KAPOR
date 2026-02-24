<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'fiscal_year', 'value' => '2026'],
            ['key' => 'is_system_locked', 'value' => 'false'],
            ['key' => 'app_title', 'value' => 'SI-KAPOR Polda NTB'],
            ['key' => 'submission_deadline', 'value' => '2026-12-31'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
