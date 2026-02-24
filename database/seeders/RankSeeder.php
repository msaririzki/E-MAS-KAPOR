<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class RankSeeder extends Seeder
{
    public function run(): void
    {
        // Cleanup existing PNS ranks to avoid duplicates
        Rank::where('category', 'PNS')->delete();

        $ranks = [
            // ── Polri — PATI ──
            ['name' => 'JENDERAL', 'category' => 'PATI', 'sort_order' => 1],
            ['name' => 'KOMJEN', 'category' => 'PATI', 'sort_order' => 2],
            ['name' => 'IRJEN', 'category' => 'PATI', 'sort_order' => 3],
            ['name' => 'BRIGJEN', 'category' => 'PATI', 'sort_order' => 4],

            // ── Polri — PAMEN ──
            ['name' => 'KOMBES', 'category' => 'PAMEN', 'sort_order' => 5],
            ['name' => 'AKBP', 'category' => 'PAMEN', 'sort_order' => 6],
            ['name' => 'KOMPOL', 'category' => 'PAMEN', 'sort_order' => 7],

            // ── Polri — PAMA ──
            ['name' => 'AKP', 'category' => 'PAMA', 'sort_order' => 8],
            ['name' => 'IPTU', 'category' => 'PAMA', 'sort_order' => 9],
            ['name' => 'IPDA', 'category' => 'PAMA', 'sort_order' => 10],

            // ── Polri — BINTARA ──
            ['name' => 'AIPTU', 'category' => 'BINTARA', 'sort_order' => 11],
            ['name' => 'AIPDA', 'category' => 'BINTARA', 'sort_order' => 12],
            ['name' => 'BRIPKA', 'category' => 'BINTARA', 'sort_order' => 13],
            ['name' => 'BRIGADIR', 'category' => 'BINTARA', 'sort_order' => 14],
            ['name' => 'BRIPTU', 'category' => 'BINTARA', 'sort_order' => 15],
            ['name' => 'BRIPDA', 'category' => 'BINTARA', 'sort_order' => 16],
            ['name' => 'ABRIPTU', 'category' => 'BINTARA', 'sort_order' => 17],
            ['name' => 'ABRIPDA', 'category' => 'BINTARA', 'sort_order' => 18],
            ['name' => 'BHARAKA', 'category' => 'BINTARA', 'sort_order' => 19],
            ['name' => 'BHARATU', 'category' => 'BINTARA', 'sort_order' => 20],
            ['name' => 'BHARADA', 'category' => 'BINTARA', 'sort_order' => 21],

            // ── PNS ──
            ['name' => 'Pembina Utama', 'category' => 'PNS', 'sort_order' => 22],
            ['name' => 'Pembina Utama Madya', 'category' => 'PNS', 'sort_order' => 23],
            ['name' => 'Pembina Utama Muda', 'category' => 'PNS', 'sort_order' => 24],
            ['name' => 'Pembina Tingkat I', 'category' => 'PNS', 'sort_order' => 25],
            ['name' => 'Pembina', 'category' => 'PNS', 'sort_order' => 26],
            ['name' => 'Penata Tingkat I', 'category' => 'PNS', 'sort_order' => 27],
            ['name' => 'Penata', 'category' => 'PNS', 'sort_order' => 28],
            ['name' => 'Penata Muda Tingkat I', 'category' => 'PNS', 'sort_order' => 29],
            ['name' => 'Penata Muda', 'category' => 'PNS', 'sort_order' => 30],
            ['name' => 'Pengatur Tingkat I', 'category' => 'PNS', 'sort_order' => 31],
            ['name' => 'Pengatur', 'category' => 'PNS', 'sort_order' => 32],
            ['name' => 'Pengatur Muda Tingkat I', 'category' => 'PNS', 'sort_order' => 33],
            ['name' => 'Pengatur Muda', 'category' => 'PNS', 'sort_order' => 34],
            ['name' => 'Juru Tingkat I', 'category' => 'PNS', 'sort_order' => 35],
            ['name' => 'Juru', 'category' => 'PNS', 'sort_order' => 36],
            ['name' => 'Juru Muda Tingkat I', 'category' => 'PNS', 'sort_order' => 37],
            ['name' => 'Juru Muda', 'category' => 'PNS', 'sort_order' => 38],
        ];

        foreach ($ranks as $rank) {
            Rank::updateOrCreate(
            ['name' => $rank['name']],
                $rank
            );
        }
    }
}
