<?php

namespace Database\Seeders;

use App\Models\BagianOption;
use Illuminate\Database\Seeder;

class BagianOptionSeeder extends Seeder
{
    public function run(): void
    {
        $options = [
            'PIMPINAN',
            'BAG OPS',
            'BAG REN',
            'BAG SDM',
            'BAG LOG',
            'SIUM',
            'SIKEU',
            'SIPROPAM',
            'SIWAS',
            'SIKUM',
            'SIHUMAS',
            'SIDOKKES',
            'SITIK',
            'SPKT',
            'SAT INTELKAM',
            'SAT RESKRIM',
            'SAT RESNARKOBA',
            'SAT SAMAPTA',
            'SAT BINMAS',
            'SAT LANTAS',
            'SAT POLAIRUD',
            'SAT TAHTI',
            'SAT OBVIT',
            'POLSEK',
        ];

        foreach ($options as $index => $option) {
            BagianOption::updateOrCreate(
                ['name' => $option],
                [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
