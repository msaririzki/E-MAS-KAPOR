<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KaporItem;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Satker;

class DummyRealisticSeeder extends Seeder
{
    /**
     * Mapping filter realistis per nama barang sesuai tabel peruntukan resmi.
     * Format: 'NAMA_BARANG' => ['personnel_type' => [...], 'gender' => [...], 'rank_categories' => [...]]
     *
     * gender: 'L' = Pria, 'P' = Wanita, [] = Semua
     * personnel_type: 'Polri', 'PNS', 'PPPK'
     * rank_categories: 'PATI','PAMEN','PAMA','BINTARA','TAMTAMA', 'GOLONGAN IV','GOLONGAN III','GOLONGAN II','GOLONGAN I'
     */
    private function getItemFilterMap(): array
    {
        return [
            // ============ TUTUP KEPALA ============
            // Topi Lapangan Polri → Khusus jabatan tertentu
            'TOPI LAPANGAN PATI BINTANG 2'      => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => ['PATI']],
            'TOPI LAPANGAN PATI BINTANG 1'      => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => ['PATI']],
            'TOPI LAPANGAN PAMEN'               => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => ['PAMEN']],
            'TOPI LAPANGAN PAMA'                => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => ['PAMA']],
            'TOPI LAPANGAN BINTARA'             => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => ['BINTARA']],

            // Peci Korpri PNS → PNS saja (cowok, unisex karena peci)
            'PECI KORPRI PNS'                   => ['personnel_type' => ['PNS'],  'gender' => ['L'], 'rank_categories' => []],

            // Pet Lantas → Polri Lantas, pria/wanita sesuai nama
            'PET LANTAS PRIA PAMEN'             => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => ['PAMEN']],
            'PET LANTAS PRIA PAMA'              => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => ['PAMA']],
            'PET LANTAS PRIA BINTARA'           => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => ['BINTARA']],
            'PET LANTAS WANITA PAMEN'           => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => ['PAMEN']],
            'PET LANTAS WANITA PAMA'            => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => ['PAMA']],
            'PET LANTAS WANITA BINTARA'         => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => ['BINTARA']],

            // Baret → Polri, divisi spesifik, semua gender
            'BARET + EMBLEM SAMAPTA'            => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'BARET + EMBLEM SATWA'              => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'BARET + EMBLEM PROVOST'            => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'BARET + EMBLEM BRIMOB'             => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'BARET + EMBLEM POLAIR'             => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'BARET + EMBLEM RESKRIM'            => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'BARET + EMBLEM YANMA'              => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],

            // Topi Lapangan PNS → PNS sesuai golongan
            'TOPI LAPANGAN PNS GOL 4'           => ['personnel_type' => ['PNS'],  'gender' => [],    'rank_categories' => ['GOLONGAN IV']],
            'TOPI LAPANGAN PNS GOL 3'           => ['personnel_type' => ['PNS'],  'gender' => [],    'rank_categories' => ['GOLONGAN III']],
            'TOPI LAPANGAN PNS GOL 2'           => ['personnel_type' => ['PNS'],  'gender' => [],    'rank_categories' => ['GOLONGAN II']],
            'TOPI LAPANGAN PNS GOL 1'           => ['personnel_type' => ['PNS'],  'gender' => [],    'rank_categories' => ['GOLONGAN I']],

            // Jilbab → Polri & PNS Wanita saja
            'JILBAB POLRI DAN PNS'              => ['personnel_type' => ['Polri','PNS'], 'gender' => ['P'], 'rank_categories' => []],

            // ============ TUTUP BADAN ============
            'JAKET POLRI'                       => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'PAKAIAN KORPRI PNS PRIA'           => ['personnel_type' => ['PNS'],  'gender' => ['L'], 'rank_categories' => []],
            'PAKAIAN KORPRI PNS WANITA'         => ['personnel_type' => ['PNS'],  'gender' => ['P'], 'rank_categories' => []],

            // PDL PNS → PNS pria/wanita
            'PDL PNS PRIA'                      => ['personnel_type' => ['PNS'],  'gender' => ['L'], 'rank_categories' => []],
            'PDL PNS WANITA'                    => ['personnel_type' => ['PNS'],  'gender' => ['P'], 'rank_categories' => []],
            'PDH PNS PRIA'                      => ['personnel_type' => ['PNS'],  'gender' => ['L'], 'rank_categories' => []],
            'PDH PNS WANITA'                    => ['personnel_type' => ['PNS'],  'gender' => ['P'], 'rank_categories' => []],
            'PDU PNS PRIA'                      => ['personnel_type' => ['PNS'],  'gender' => ['L'], 'rank_categories' => []],
            'PDU PNS WANITA'                    => ['personnel_type' => ['PNS'],  'gender' => ['P'], 'rank_categories' => []],

            // PDL/PDH Polri pria/wanita
            'PDH POLRI PRIA'                    => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDH POLRI WANITA'                  => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'PDL I POLRI PRIA'                  => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL I POLRI WANITA'                => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDL Polantas → Polri Lantas pria/wanita
            'PDL I POLANTAS PRIA'               => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL I POLANTAS WANITA'             => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDL II Polri Two Tone → Polri Staf
            'PDL II POLRI TWO TONE PRIA'        => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II POLRI TWO TONE WANITA'      => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDL II Tactical Brimob → Polri Brimob
            'PDL II TACTICAL COKLAT BRIMOB PRIA'   => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II TACTICAL COKLAT BRIMOB WANITA' => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'PDL II TACTICAL HIJAU BRIMOB PRIA'    => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II TACTICAL HIJAU BRIMOB WANITA'  => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'PDL II TACTICAL HITAM BRIMOB PRIA'    => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II TACTICAL HITAM BRIMOB WANITA'  => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDL II Tactical Loreng
            'PDL II TACTICAL LORENG BIRU AIRUD PRIA'   => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II TACTICAL LORENG BIRU AIRUD WANITA' => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'PDL II TACTICAL LORENG BRIMOB PRIA'       => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II TACTICAL LORENG BRIMOB WANITA'     => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDL II Biru Airud
            'PDL II BIRU AIRUD PRIA'            => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II BIRU AIRUD WANITA'          => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDL II Provos
            'PDL II PROVOS PRIA'                => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDL II PROVOS WANITA'              => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // PDU Polri
            'PDU I/III POLRI PRIA'              => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDU I/III POLRI WANITA'            => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'PDU IV POLRI PRIA'                 => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PDU IV POLRI WANITA'               => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // Pakaian Resintelpam → Polri Reskrim/Intel/Paminal/Sikum
            'PAKAIAN LENGAN PENDEK RESINTELPAM PRIA'    => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'PAKAIAN LENGAN PANJANG RESINTELPAM WANITA' => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // Humas → Polri Humas
            'HUMAS LENGAN PANJANG PRIA'         => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'HUMAS LENGAN PANJANG WANITA'       => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // Opsnal TIK
            'OPSNAL TIK LENGAN PENDEK PRIA'     => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'OPSNAL TIK LENGAN PANJANG WANITA'  => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],

            // Pakaian Olahraga → Polri & PNS semua
            'PAKAIAN OLAHRAGA PRIA'             => ['personnel_type' => ['Polri','PNS'], 'gender' => ['L'], 'rank_categories' => []],
            'PAKAIAN OLAHRAGA WANITA'           => ['personnel_type' => ['Polri','PNS'], 'gender' => ['P'], 'rank_categories' => []],

            // Rompi & T-Shirt → Polri semua
            'ROMPI KESELAMATAN HIJAU STABILO'   => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'T-SHIRT'                           => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],

            // ============ TUTUP KAKI ============
            'SEPATU DISHAR PNS PRIA'            => ['personnel_type' => ['PNS'],  'gender' => ['L'], 'rank_categories' => []],
            'SEPATU DISHAR PNS WANITA'          => ['personnel_type' => ['PNS'],  'gender' => ['P'], 'rank_categories' => []],
            'SEPATU PDL I LANTAS POLRI PRIA'    => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'SEPATU PDL I LANTAS POLRI WANITA'  => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'SEPATU PDL II PATI'                => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => ['PATI']],
            'SEPATU PDL II PAMEN, PAMA, BINTARA DAN TAMATAMA' => ['personnel_type' => ['Polri'], 'gender' => [], 'rank_categories' => ['PAMEN','PAMA','BINTARA','TAMTAMA']],
            'SEPATU PDL II PROVOST'             => ['personnel_type' => ['Polri'], 'gender' => [],    'rank_categories' => []],
            'SEPATU TACTICAL RESINTEL PRIA'     => ['personnel_type' => ['Polri'], 'gender' => ['L'], 'rank_categories' => []],
            'SEPATU TACTICAL RESINTEL WANITA'   => ['personnel_type' => ['Polri'], 'gender' => ['P'], 'rank_categories' => []],
            'SEPATU OLAHRAGA'                   => ['personnel_type' => ['Polri','PNS'], 'gender' => [], 'rank_categories' => []],
            'KAOS KAKI OLAHRAGA'                => ['personnel_type' => ['Polri','PNS'], 'gender' => [], 'rank_categories' => []],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan semua harga kapor terisi (beri harga dummy jika masih 0)
        $this->command->info('Memperbarui harga Item KAPOR yang masih 0...');
        KaporItem::where('price', 0)->orWhereNull('price')->get()->each(function ($item) {
            $item->update(['price' => rand(10, 200) * 5000]);
        });

        // 2. Ambil Satker target
        $targetSatkerNames = [
            'POLRES LOMBOK UTARA',
            'POLRESTA MATARAM',
            'POLRES LOMBOK TIMUR',
            'POLRES BIMA',
            'BID HUMAS',
            'DIT INTELKAM',
        ];
        $satkers = Satker::whereIn('name', $targetSatkerNames)->get();
        if ($satkers->isEmpty()) {
            $this->command->warn('Satker spesifik tidak ditemukan, menggunakan random Satker.');
            $satkers = Satker::inRandomOrder()->limit(6)->get();
        }
        $satkerIds = $satkers->pluck('id')->toArray();
        $this->command->info('Satker ditemukan: ' . $satkers->pluck('name')->join(', '));

        // 3. Buat Paket
        $year = BudgetYear::firstOrCreate(['year' => 2026], ['is_active' => true]);
        $package = BudgetPackage::create([
            'budget_year_id' => $year->id,
            'name' => 'PAKET DUMMY REALISTIS',
            'description' => 'Dummy 4 Tutup Kepala, 4 Tutup Badan, 4 Tutup Kaki — filter sesuai peruntukan resmi',
            'status' => 'DRAFT'
        ]);
        $this->command->info('Paket dibuat: ' . $package->name);

        // 4. Ambil filter map
        $filterMap = $this->getItemFilterMap();

        // 5. Pilih 4 item per kategori (variasi: campuran cowok, cewek, PNS, Polri)
        $categories = ['Tutup_Kepala', 'Tutup_Badan', 'Tutup_Kaki'];

        // Item yang dipilih khusus agar bervariasi (campuran gender & tipe personel)
        $selectedItemNames = [
            // Tutup Kepala: 1 Polri pria (PAMEN), 1 PNS (PECI), 1 Wanita (JILBAB), 1 Baret
            'TOPI LAPANGAN PAMEN',
            'PECI KORPRI PNS',
            'JILBAB POLRI DAN PNS',
            'BARET + EMBLEM BRIMOB',

            // Tutup Badan: 1 PNS pria, 1 PNS/P3K wanita, 1 Polri pria, 1 Polri wanita
            'PDL PNS PRIA',
            'PAKAIAN KORPRI PNS WANITA',
            'PDL II POLRI TWO TONE PRIA',
            'HUMAS LENGAN PANJANG WANITA',

            // Tutup Kaki: 1 PNS pria, 1 Polri wanita, 1 Polri jabatan, 1 Unisex
            'SEPATU DISHAR PNS PRIA',
            'SEPATU PDL I LANTAS POLRI WANITA',
            'SEPATU PDL II PAMEN, PAMA, BINTARA DAN TAMATAMA',
            'SEPATU OLAHRAGA',
        ];

        $this->command->info('Menambahkan 12 item spesifik ke paket...');

        foreach ($selectedItemNames as $itemName) {
            $kaporItem = KaporItem::where('item_name', $itemName)->first();
            if (!$kaporItem) {
                $this->command->warn("Item tidak ditemukan: {$itemName}, dilewati.");
                continue;
            }

            // Buat PackageItem
            $packageItem = PackageItem::create([
                'budget_package_id' => $package->id,
                'kapor_item_id' => $kaporItem->id,
            ]);

            // Ambil filter dari map
            $filters = $filterMap[$itemName] ?? [
                'personnel_type' => [],
                'gender' => [],
                'rank_categories' => []
            ];

            // Pilih 3-6 satker secara acak dari target
            $numSatkers = rand(3, min(6, count($satkerIds)));
            shuffle($satkerIds);
            $chosenSatkerIds = array_slice($satkerIds, 0, $numSatkers);

            foreach ($chosenSatkerIds as $sid) {
                $recipient = PackageItemRecipient::create([
                    'package_item_id' => $packageItem->id,
                    'satker_id' => $sid,
                    'recipient_filters' => $filters
                ]);
                $recipient->calculateMatchedCount();
            }

            $genderLabel = empty($filters['gender']) ? 'Semua' : implode('/', $filters['gender']);
            $typeLabel = empty($filters['personnel_type']) ? 'Semua' : implode('/', $filters['personnel_type']);
            $rankLabel = empty($filters['rank_categories']) ? 'Semua' : implode('/', $filters['rank_categories']);
            $this->command->info("  ✓ {$kaporItem->item_name} → Gender:{$genderLabel} | Tipe:{$typeLabel} | Pangkat:{$rankLabel} | Satker:{$numSatkers}");
        }

        $this->command->info('');
        $this->command->info('════════════════════════════════════════════');
        $this->command->info('  SEEDER SELESAI! Paket Dummy Realistis terisi 12 item.');
        $this->command->info('════════════════════════════════════════════');
    }
}
