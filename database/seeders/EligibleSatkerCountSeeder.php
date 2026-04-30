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
        // PRIMOD — hanya Polda
        'PRIMOD'       => 1,

        // POLANTAS / LANTAS — DIT LANTAS + 10 Polres
        'POLANTAS'     => 11,
        'LANTAS'       => 11,

        // POLAIR / AIRUD — DIT POLAIRUD + 10 Polres
        'AIRUD'        => 11,
        'POLAIR'       => 11,

        // RESKRIM / RESINTEL / RESINTELPAM — 17 satker
        'RESINTELPAM'  => 17,
        'RESINTEL'     => 17,
        'RESKRIM'      => 17,

        // TIK / OPSNAL TIK — BID TIK + 10 Polres
        'OPSNAL TIK'   => 11,
        'TIK'          => 11,

        // HUMAS — BID HUMAS + 10 Polres
        'HUMAS'        => 11,

        // BRIMOB — hanya Sat Brimob (1 satker Polda)
        'BRIMOB'       => 1,

        // PROVOST / PROVOS — hanya BID PROPAM (1 satker Polda)
        'PROVOST'      => 1,
        'PROVOS'       => 1,
    ];

    public function run(): void
    {
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

            // Hanya update jika ada kata kunci yang cocok
            if ($matched !== null && $item->eligible_satker_count !== $matched) {
                $item->update(['eligible_satker_count' => $matched]);
                $this->command->line(
                    "  <fg=green>✓</> [{$matched} satker] {$item->item_name}"
                );
                $updated++;
            }
        }

        $this->command->info("Selesai. {$updated} item diperbarui.");
        $this->command->newLine();
        $this->command->comment('Item tanpa kata kunci spesifik tetap NULL (berlaku untuk semua satker).');
    }
}
