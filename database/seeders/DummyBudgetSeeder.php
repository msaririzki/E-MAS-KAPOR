<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KaporItem;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Satker;

class DummyBudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Update kapor prices if empty
        $this->command->info('Memperbarui harga Item KAPOR secara dummy...');
        $items = KaporItem::all();
        foreach($items as $item) {
            if (!$item->price || $item->price == 0) {
                // Harga random kelipatan 5000: antara 50k s/d 1jt
                $item->price = rand(10, 200) * 5000;
                $item->save();
            }
        }

        // 2. Ambil ID Satker Target
        $targetSatkerNames = [
            'POLRES LOMBOK UTARA',
            'POLRESTA MATARAM',
            'POLRES LOMBOK TIMUR',
            'POLRES BIMA',
            'BID HUMAS',
            'DIT INTELKAM',
        ];

        $satkerIds = Satker::whereIn('name', $targetSatkerNames)->pluck('id')->toArray();
        if (empty($satkerIds)) {
            $this->command->warn('Tidak dapat menemukan Satker target (Lombok Utara, Mataram, dll). Menggunakan random Satker.');
            $satkerIds = Satker::inRandomOrder()->limit(5)->pluck('id')->toArray();
            if (empty($satkerIds)) {
                $this->command->error('Tabel Satker kosong, tidak bisa lanjut.');
                return;
            }
        }

        // 3. Buat Paket Anggaran 
        $this->command->info('Membuat Paket Anggaran Dummy...');
        $year = BudgetYear::firstOrCreate(['year' => 2026], ['is_active' => true]);
        $package = BudgetPackage::create([
            'budget_year_id' => $year->id,
            'name' => 'PAKET PENGADAAN LENGKAP DUMMY ' . rand(100, 999),
            'description' => 'Simulasi Rencana Pengadaan (Dibuat lewat script Seeder)',
            'status' => 'DRAFT'
        ]);

        // 4. Pilih 4 Kapor per Kategori & Buat Transaksi Pembelian
        $categories = KaporItem::select('category')->distinct()->pluck('category');

        $this->command->info('Membuat transaksi (Tujuan Personel) 4 Item per Kategori...');
        foreach($categories as $category) {
            $catItems = KaporItem::where('category', $category)->inRandomOrder()->limit(4)->get();
            
            foreach($catItems as $index => $cItem) {
                // Tambahkan Item ke Paket
                $packageItem = PackageItem::create([
                    'budget_package_id' => $package->id,
                    'kapor_item_id' => $cItem->id,
                ]);

                // Definisikan target spesifik untuk filter (Berdasarkan text/nama)
                $gender = null;
                $personnelType = null;
                $rankCats = [];
                
                $nameUpper = strtoupper($cItem->name);
                
                if (str_contains($nameUpper, 'WANITA') || str_contains($nameUpper, 'POLWAN') || str_contains($nameUpper, 'JILBAB') || str_contains($nameUpper, 'ROK')) {
                    $gender = 'Wanita';
                } elseif (str_contains($nameUpper, 'PRIA') || str_contains($nameUpper, 'POLKI') || str_contains($nameUpper, 'CELANA')) {
                    $gender = 'Pria';
                }

                if (str_contains($nameUpper, 'PNS') || str_contains($nameUpper, 'KORPRI')) {
                    $personnelType = 'PNS';
                } elseif (str_contains($nameUpper, 'POLRI') || str_contains($nameUpper, 'POLANTAS') || str_contains($nameUpper, 'BRIMOB') || str_contains($nameUpper, 'SABHARA') || str_contains($nameUpper, 'HUMAS')) {
                    $personnelType = 'POLRI';
                }

                // Polisi Jabatan/Pangkat Tertentu
                if (str_contains($nameUpper, 'PATI')) {
                    $personnelType = 'POLRI';
                    $rankCats = ['PATI'];
                } elseif (str_contains($nameUpper, 'GOL 3') || str_contains($nameUpper, 'GOL 4')) {
                    $personnelType = 'PNS';
                    $rankCats = ['GOLONGAN III', 'GOLONGAN IV']; 
                }

                // Kalau tetap kosong (Unisex / All)
                if (is_null($gender)) {
                    $gender = ($index % 2 == 0) ? 'Pria' : 'Semua Gender'; // Contoh variasi
                }
                if (is_null($personnelType)) {
                    $personnelType = ($index % 3 == 0) ? 'PNS' : 'POLRI';
                }

                // Pilih Kombinasi Satker (Setiap item diberikan 2 hingga 4 satker)
                $numSatkers = rand(2, 4);
                $chosenKeys = (array) array_rand(array_flip($satkerIds), min($numSatkers, count($satkerIds)));
                
                $filters = [
                    'personnel_type' => $personnelType ? [$personnelType] : [],
                    'gender' => $gender ? [$gender === 'Pria' ? 'L' : 'P'] : [],
                    'rank_categories' => $rankCats
                ];

                foreach($chosenKeys as $sid) {
                    $recipient = PackageItemRecipient::create([
                        'package_item_id' => $packageItem->id,
                        'satker_id' => $sid,
                        'recipient_filters' => $filters
                    ]);
                    $recipient->calculateMatchedCount();
                }
            }
        }

        $this->command->info('Seeder berhasil: Paket Pengadaan Dummy telah dibuat dengan sukses.');
    }
}
