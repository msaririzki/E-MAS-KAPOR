<?php

namespace Database\Seeders;

use App\Models\KaporItem;
use App\Models\KaporSize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class KaporItemSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        Schema::disableForeignKeyConstraints();
        KaporSize::truncate();
        KaporItem::truncate();
        Schema::enableForeignKeyConstraints();

        // Size configurations
        $sizesHat = ['U' => range(54, 60)];
        $sizesHijab = ['U' => ['S', 'M', 'L', 'XL']];
        $sizesClothePria = ['L' => ['14', '14.5', '15', '15.5', '16', '16.5', '17', '18'], 'P' => ['K', 'SD', 'B', 'EB']];
        $sizesClotheWanita = ['L' => ['14', '14.5', '15', '15.5', '16', '16.5', '17', '18'], 'P' => ['K', 'SD', 'B', 'EB']]; // Standardize for now
        $sizesJacket = ['U' => ['S', 'M', 'L', 'XL', 'XXL', 'XXXL']];
        $sizesShoePria = ['L' => range(38, 46)];
        $sizesShoeWanita = ['P' => range(36, 42)];
        $sizesShoeUnisex = ['U' => range(36, 46)];

        $items = [
            // ================== TUTUP KEPALA ==================
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PATI BINTANG 2', 'price' => 181000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PATI BINTANG 1', 'price' => 177000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PAMEN', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PAMA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN BINTARA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PECI KORPRI PNS', 'price' => 43000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PET LANTAS PRIA PAMEN', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PET LANTAS PRIA PAMA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PET LANTAS PRIA BINTARA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PET LANTAS WANITA PAMEN', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PET LANTAS WANITA PAMA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'PET LANTAS WANITA BINTARA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM SAMAPTA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM SATWA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM PROVOST', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM BRIMOB', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM POLAIR', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM RESKRIM', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'BARET + EMBLEM YANMA', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PNS GOL 4', 'price' => 108000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PNS GOL 3', 'price' => 103000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PNS GOL 2', 'price' => 98000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'TOPI LAPANGAN PNS GOL 1', 'price' => 93000, 'unit' => 'PCS', 'sizes_config' => $sizesHat],
            ['category' => 'Tutup_Kepala', 'item_name' => 'JILBAB POLRI DAN PNS', 'price' => 89000, 'unit' => 'PCS', 'sizes_config' => $sizesHijab],

            // ================== TUTUP BADAN ==================
            ['category' => 'Tutup_Badan', 'item_name' => 'JAKET POLRI', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesJacket],
            ['category' => 'Tutup_Badan', 'item_name' => 'PAKAIAN KORPRI PNS PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PAKAIAN KORPRI PNS WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PAKAIAN LENGAN PENDEK RESINTELPAM PRIA', 'price' => 548000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PAKAIAN LENGAN PANJANG RESINTELPAM WANITA', 'price' => 618000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PAKAIAN OLAHRAGA PRIA', 'price' => 437000, 'unit' => 'STEL', 'sizes_config' => $sizesJacket],
            ['category' => 'Tutup_Badan', 'item_name' => 'PAKAIAN OLAHRAGA WANITA', 'price' => 437000, 'unit' => 'STEL', 'sizes_config' => $sizesJacket],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDH PNS PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDH PNS WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL PNS PRIA', 'price' => 548000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL PNS WANITA', 'price' => 573000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDH POLRI PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDH POLRI WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL I POLANTAS PRIA', 'price' => 648000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL I POLANTAS WANITA', 'price' => 648000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL I POLRI PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL I POLRI WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II BIRU AIRUD PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II BIRU AIRUD WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II POLRI TWO TONE PRIA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II POLRI TWO TONE WANITA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II PROVOS PRIA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II PROVOS WANITA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL COKLAT BRIMOB PRIA', 'price' => 823000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL COKLAT BRIMOB WANITA', 'price' => 823000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL HIJAU BRIMOB PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL HIJAU BRIMOB WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL HITAM BRIMOB PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL HITAM BRIMOB WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL LORENG BIRU AIRUD PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL LORENG BIRU AIRUD WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL LORENG BRIMOB PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDL II TACTICAL LORENG BRIMOB WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDU I/III POLRI PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDU I/III POLRI WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDU IV POLRI PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDU IV POLRI WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDU PNS PRIA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'PDU PNS WANITA', 'price' => 0, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'ROMPI KESELAMATAN HIJAU STABILO', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesJacket],
            ['category' => 'Tutup_Badan', 'item_name' => 'T-SHIRT', 'price' => 0, 'unit' => 'PCS', 'sizes_config' => $sizesJacket],
            ['category' => 'Tutup_Badan', 'item_name' => 'HUMAS LENGAN PANJANG PRIA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'HUMAS LENGAN PANJANG WANITA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],
            ['category' => 'Tutup_Badan', 'item_name' => 'OPSNAL TIK LENGAN PENDEK PRIA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClothePria],
            ['category' => 'Tutup_Badan', 'item_name' => 'OPSNAL TIK LENGAN PANJANG WANITA', 'price' => 748000, 'unit' => 'STEL', 'sizes_config' => $sizesClotheWanita],

            // ================== TUTUP KAKI ==================
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU DISHAR PNS PRIA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoePria],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU DISHAR PNS WANITA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeWanita],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU PDL I LANTAS POLRI PRIA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoePria],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU PDL I LANTAS POLRI WANITA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeWanita],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU PDL II PATI', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoePria],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU PDL II PAMEN, PAMA, BINTARA DAN TAMATAMA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeUnisex],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU PDL II PROVOST', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeUnisex],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU TACTICAL RESINTEL PRIA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoePria],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU TACTICAL RESINTEL WANITA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeWanita],
            ['category' => 'Tutup_Kaki', 'item_name' => 'SEPATU OLAHRAGA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeUnisex],
            ['category' => 'Tutup_Kaki', 'item_name' => 'KAOS KAKI OLAHRAGA', 'price' => 0, 'unit' => 'PASANG', 'sizes_config' => $sizesShoeUnisex],
        ];

        foreach ($items as $itemData) {
            $sizesConfig = $itemData['sizes_config'];
            unset($itemData['sizes_config']);
            
            // Format category if needed, ensure invoice_group is set correctly as category 
            $itemData['invoice_group'] = $itemData['category'];

            // Create Item
            $item = KaporItem::create($itemData);

            // Create Sizes
            foreach ($sizesConfig as $genderKey => $sizeList) {
                // Map 'U' to null for database
                $gender = ($genderKey === 'U') ? null : $genderKey;

                $order = 1;
                foreach ($sizeList as $sizeLabel) {
                    KaporSize::create([
                        'kapor_item_id' => $item->id,
                        'size_label' => (string) $sizeLabel,
                        'gender' => $gender,
                        'sort_order' => $order++,
                    ]);
                }
            }
        }
    }
}
