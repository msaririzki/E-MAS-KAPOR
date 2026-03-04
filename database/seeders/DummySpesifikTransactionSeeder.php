<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KaporItem;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Satker;

class DummySpesifikTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan semua harga item terisi (dummy data)
        $this->command->info('Memperbarui harga Item KAPOR secara dummy...');
        $items = KaporItem::all();
        foreach($items as $item) {
            if (!$item->price || $item->price == 0) {
                $item->price = rand(10, 200) * 5000;
                $item->save();
            }
        }

        // 2. Daftar Satker Target (Spesifik sesuai request user)
        $targetSatkerNames = [
            'POLRES LOMBOK UTARA',
            'POLRESTA MATARAM',
            'POLRES LOMBOK TIMUR',
            'POLRES BIMA',
            'BID HUMAS',
            'DIT INTELKAM',
        ];

        // Retrieve valid Satker IDs based on names
        $satkers = Satker::whereIn('name', $targetSatkerNames)->get();
        if ($satkers->isEmpty()) {
            $this->command->warn('Satker spesifik tidak ditemukan. Memakai beberapa random Satker...');
            $satkers = Satker::inRandomOrder()->limit(6)->get();
        }
        $satkerIds = $satkers->pluck('id')->toArray();

        // 3. Buat Paket Anggaran Baru Khusus Order Dummy Spesifik Ini
        $this->command->info('Membuat Paket Rencana Anggaran Spesifik...');
        $year = BudgetYear::firstOrCreate(['year' => 2026], ['is_active' => true]);
        $package = BudgetPackage::create([
            'budget_year_id' => $year->id,
            'name' => 'PAKET PENGADAAN SPESIFIK ' . rand(100, 999),
            'description' => 'Dummy transaksi 4 Tutup Kepala, 4 Badan, 4 Kaki',
            'status' => 'DRAFT'
        ]);

        // 4. Proses Ekstraksi Kategori
        $categories = ['Tutup_Kepala', 'Tutup_Badan', 'Tutup_Kaki'];

        $this->command->info('Menyiapkan masing-masing 4 produk untuk tiap kategori...');
        
        foreach ($categories as $idx => $category) {
            // Ambil 4 Item acak dari Kategori ini
            $catItems = KaporItem::where('category', $category)->inRandomOrder()->limit(4)->get();
            
            foreach ($catItems as $itemIndex => $cItem) {
                // Tambahkan Barang dalam Paket
                $packageItem = PackageItem::create([
                    'budget_package_id' => $package->id,
                    'kapor_item_id' => $cItem->id,
                ]);

                // Definisikan target spesifik/filter (Berdasarkan text/nama agar masuk akal)
                $gender = null;
                $personnelType = null;
                $rankCats = [];
                
                $nameUpper = strtoupper($cItem->name);
                
                // --- Deteksi Gender ---
                if (str_contains($nameUpper, 'WANITA') || str_contains($nameUpper, 'POLWAN') || str_contains($nameUpper, 'JILBAB') || str_contains($nameUpper, 'ROK')) {
                    $gender = 'P';
                } elseif (str_contains($nameUpper, 'PRIA') || str_contains($nameUpper, 'POLKI') || str_contains($nameUpper, 'CELANA')) {
                    $gender = 'L';
                }

                // --- Deteksi Tipe Personel ---
                if (str_contains($nameUpper, 'PNS') || str_contains($nameUpper, 'KORPRI')) {
                    $personnelType = 'PNS';
                } elseif (str_contains($nameUpper, 'POLRI') || str_contains($nameUpper, 'POLANTAS') || str_contains($nameUpper, 'BRIMOB') || str_contains($nameUpper, 'SABHARA') || str_contains($nameUpper, 'HUMAS')) {
                    $personnelType = 'Polri';
                }

                // --- Deteksi Polisi Jabatan/Pangkat Tertentu ---
                if (str_contains($nameUpper, 'PATI')) {
                    $personnelType = 'Polri';
                    $rankCats = ['PATI'];
                } elseif (str_contains($nameUpper, 'PAMEN') || str_contains($nameUpper, 'PAMA')) {
                    $personnelType = 'Polri';
                    $rankCats = ['PAMEN', 'PAMA'];
                } elseif (str_contains($nameUpper, 'GOL 3') || str_contains($nameUpper, 'GOL 4')) {
                    $personnelType = 'PNS';
                    $rankCats = ['GOLONGAN III', 'GOLONGAN IV']; 
                }

                // Jika terlampau kosong (kasus baju all size unisex biasa)
                // Kita force berikan variasi kriteria agar datanya padat sesuai instruksi user
                if (is_null($gender)) {
                    $gender = ($itemIndex % 2 == 0) ? 'L' : 'P';
                }
                if (is_null($personnelType)) {
                    // Item ke-0, 1 -> Polri | Item ke-2, 3 -> PNS
                    $personnelType = ($itemIndex < 2) ? 'Polri' : 'PNS';
                }

                // Masukkan setidaknya 2~4 Satker dari targetSatker yang disediakan user
                $numSatkers = rand(2, 4);
                $chosenKeys = (array) array_rand(array_flip($satkerIds), min($numSatkers, count($satkerIds)));
                
                $filters = [
                    'personnel_type' => $personnelType ? [$personnelType] : [],
                    'gender' => $gender ? [$gender] : [],
                    'rank_categories' => $rankCats
                ];

                // Simpan Setiap Satker yang terpilih sebagai Recipient
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

        $this->command->info('Seeder berhasil: Database telah terisi paket spesifik berisi Tutup Kepala (4), Badan (4), Kaki (4)!');
    }
}
