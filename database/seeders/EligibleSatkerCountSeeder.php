<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk mengisi eligible_satker_count berdasarkan fungsi item.
 *
 * Aturan Bisnis:
 * - LANTAS  : 11 satker (DIT LANTAS + 10 Polres)
 * - POLAIR  : 11 satker (DIT POLAIRUD + 10 Polres)
 * - RESKRIM : 17 satker (Dit Intelkam, Reskrimum, Reskrimsus, Res PPA PPO,
 *               Resnarkoba, + Sat Reskrim 10 Polres + 1 Satwa/lain)
 * - TIK     : 11 satker (BID TIK + 10 Polres)
 * - HUMAS   : 11 satker (BID HUMAS + 10 Polres)
 * - PRIMOD  : 1 satker  (Polda saja)
 * - NULL    : berlaku untuk semua satker (default, tidak diubah)
 *
 * Pencocokan berdasarkan KATA KUNCI di nama item (case-insensitive).
 * Jalankan ulang jika item baru ditambahkan.
 */
class EligibleSatkerCountSeeder extends Seeder
{
    /**
     * Peta kata kunci → jumlah satker eligible.
     * Urutan penting: lebih spesifik dulu, lebih umum di bawah.
     */
    private const KEYWORD_MAP = [
        // Primod - 1 (Polda saja)
        'PRIMOD'       => 1,
        'PROVOS'       => 1, // Menambahkan provost untuk berjaga-jaga
        'PROVOST'      => 1,
        'BRIMOB'       => 1, // Menambahkan brimob

        // Lantas - 11 (DIT LANTAS + 10 Polres)
        'LANTAS'       => 11,
        'POLANTAS'     => 11,

        // Polair - 11 (DIT POLAIRUD + 10 Polres)
        'POLAIR'       => 11,
        'AIRUD'        => 11,

        // Reskrim - 17 (dari intel/intelkam sampai bitkum)
        'RESKRIM'      => 17,
        'INTEL'        => 17,
        'KUM'          => 17,
        'RESINTEL'     => 17,
        'PAMOBVIT'     => 17, // Termasuk satker lain di rentang tersebut

        // TIK - 11 (BID TIK + 10 Polres)
        'TIK'          => 11,

        // Humas - 11 (BID HUMAS + 10 Polres)
        'HUMAS'        => 11,
    ];

    public function run(): void
    {
        // Reset semua item menjadi null dulu agar bersih
        IdentifikasiItem::query()->update(['eligible_satker_count' => null]);

        $items = IdentifikasiItem::all();
        $updated = 0;

        foreach ($items as $item) {
            $name = strtoupper($item->item_name);
            $matched = null;

            foreach (self::KEYWORD_MAP as $keyword => $count) {
                if (str_contains($name, $keyword)) {
                    $matched = $count;
                    break; // gunakan aturan pertama yang cocok
                }
            }

            // Update jika ada kata kunci yang cocok
            if ($matched !== null) {
                $item->update(['eligible_satker_count' => $matched]);
                $this->command->line(
                    "  <fg=green>✓</> [{$matched} satker] {$item->item_name}"
                );
                $updated++;
            }
        }

        $this->command->info("Selesai. {$updated} item diperbarui dengan eligible satker spesifik.");
        $this->command->newLine();
        $this->command->comment('Item lainnya telah direset menjadi NULL (berlaku untuk semua 40 satker).');
    }
}
