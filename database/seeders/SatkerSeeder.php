<?php

namespace Database\Seeders;

use App\Models\Satker;
use Illuminate\Database\Seeder;

class SatkerSeeder extends Seeder
{
    public function run(): void
    {
        // ── Level 1: Polda ──
        $polda = Satker::create(['name' => 'Polda NTB', 'code' => 'POLDA-NTB', 'sort_order' => 0]);

        // ── Level 2: Satker Mabes Polda (urutan sesuai struktur organisasi) ──
        $satkerPolda = [
            ['name' => 'ITWASDA', 'code' => 'ITWASDA', 'parent_id' => $polda->id, 'sort_order' => 1],
            ['name' => 'BIRO OPS', 'code' => 'BIRO-OPS', 'parent_id' => $polda->id, 'sort_order' => 2],
            ['name' => 'BIRO RENA', 'code' => 'BIRO-RENA', 'parent_id' => $polda->id, 'sort_order' => 3],
            ['name' => 'BIRO SDM', 'code' => 'BIRO-SDM', 'parent_id' => $polda->id, 'sort_order' => 4],
            ['name' => 'BIRO LOGISTIK', 'code' => 'BIRO-LOG', 'parent_id' => $polda->id, 'sort_order' => 5],
            ['name' => 'DIT SAMAPTA', 'code' => 'SABHARA', 'parent_id' => $polda->id, 'sort_order' => 6],
            ['name' => 'DIT LANTAS', 'code' => 'LANTAS-POLDA', 'parent_id' => $polda->id, 'sort_order' => 7],
            ['name' => 'DIT BINMAS', 'code' => 'BINMAS', 'parent_id' => $polda->id, 'sort_order' => 8],
            ['name' => 'DIT PAMOBVIT', 'code' => 'PAMOBVIT', 'parent_id' => $polda->id, 'sort_order' => 9],
            ['name' => 'DIT TAHTI', 'code' => 'TAHTI', 'parent_id' => $polda->id, 'sort_order' => 10],
            ['name' => 'DIT POLAIRUD', 'code' => 'POLAIRUD', 'parent_id' => $polda->id, 'sort_order' => 11],
            ['name' => 'SAT BRIMOB', 'code' => 'BRIMOB', 'parent_id' => $polda->id, 'sort_order' => 12],
            ['name' => 'DIT INTELKAM', 'code' => 'INTELKAM', 'parent_id' => $polda->id, 'sort_order' => 13],
            ['name' => 'DIT RESKRIMSUS', 'code' => 'RESKRIMSUS', 'parent_id' => $polda->id, 'sort_order' => 14],
            ['name' => 'DIT RESKRIMUM', 'code' => 'RESKRIMUM', 'parent_id' => $polda->id, 'sort_order' => 15],
            ['name' => 'DIT RESNARKOBA', 'code' => 'NARKOBA', 'parent_id' => $polda->id, 'sort_order' => 16],
            ['name' => 'BID PROPAM', 'code' => 'PROPAM', 'parent_id' => $polda->id, 'sort_order' => 17],
            ['name' => 'BID KUM', 'code' => 'KUM', 'parent_id' => $polda->id, 'sort_order' => 18],
            ['name' => 'BID HUMAS', 'code' => 'HUMAS', 'parent_id' => $polda->id, 'sort_order' => 19],
            ['name' => 'BID DOKKES', 'code' => 'DOKKES', 'parent_id' => $polda->id, 'sort_order' => 20],
            ['name' => 'BID TIK', 'code' => 'TIK', 'parent_id' => $polda->id, 'sort_order' => 21],
            ['name' => 'BID KEU', 'code' => 'KEU', 'parent_id' => $polda->id, 'sort_order' => 22],
            ['name' => 'YANMA', 'code' => 'YANMA', 'parent_id' => $polda->id, 'sort_order' => 23],
            ['name' => 'SPRIPIM', 'code' => 'SPRIPIM', 'parent_id' => $polda->id, 'sort_order' => 24],
            ['name' => 'SPN', 'code' => 'SPN', 'parent_id' => $polda->id, 'sort_order' => 25],
            ['name' => 'SETUM', 'code' => 'SETUM', 'parent_id' => $polda->id, 'sort_order' => 26],
            ['name' => 'RUMKIT', 'code' => 'RUMKIT', 'parent_id' => $polda->id, 'sort_order' => 27],
            ['name' => 'SPKT', 'code' => 'SPKT-POLDA', 'parent_id' => $polda->id, 'sort_order' => 28],
        ];

        foreach ($satkerPolda as $s) {
            Satker::create($s);
        }

        // ── Level 2: Polres di bawah Polda NTB ──
        $polresData = [
            ['name' => 'POLRESTA MATARAM', 'code' => 'RES-MTR', 'sort_order' => 29],
            ['name' => 'POLRES LOMBOK BARAT', 'code' => 'RES-LOBAR', 'sort_order' => 30],
            ['name' => 'POLRES LOMBOK UTARA', 'code' => 'RES-LOTARA', 'sort_order' => 31],
            ['name' => 'POLRES LOMBOK TENGAH', 'code' => 'RES-LOTENG', 'sort_order' => 32],
            ['name' => 'POLRES LOMBOK TIMUR', 'code' => 'RES-LOTIM', 'sort_order' => 33],
            ['name' => 'POLRES SUMBAWA BARAT', 'code' => 'RES-SBB', 'sort_order' => 34],
            ['name' => 'POLRES SUMBAWA', 'code' => 'RES-SBW', 'sort_order' => 35],
            ['name' => 'POLRES DOMPU', 'code' => 'RES-DOMPU', 'sort_order' => 36],
            ['name' => 'POLRES BIMA', 'code' => 'RES-BIMA', 'sort_order' => 37],
            ['name' => 'POLRES BIMA KOTA', 'code' => 'RES-BIMA-KOTA', 'sort_order' => 38],
        ];

        foreach ($polresData as $pr) {
            Satker::create(array_merge($pr, ['parent_id' => $polda->id]));
        }
    }
}
