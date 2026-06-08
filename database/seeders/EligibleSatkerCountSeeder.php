<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use App\Services\KebutuhanEligibilityService;
use Illuminate\Database\Seeder;

/**
 * Mengisi eligible_satker_count sebagai penyebut statistik/laporan.
 * Angka ini tidak membatasi item yang tampil di admin satker.
 *
 * - Lantas: 11 satker (DIT LANTAS + 10 Polres)
 * - Polair: 11 satker (DIT POLAIRUD + 10 Polres)
 * - Reskrim: 17 satker (Intel/Intelkam sampai Bitkum + Polres)
 * - TIK: 11 satker (BID TIK + 10 Polres)
 * - Humas: 11 satker (BID HUMAS + 10 Polres)
 * - Primod/khusus polda: 1 satker
 * - NULL: laporan memakai total semua satker
 */
class EligibleSatkerCountSeeder extends Seeder
{
    public function run(KebutuhanEligibilityService $eligibilityService): void
    {
        IdentifikasiItem::query()->update(['eligible_satker_count' => null]);

        $updated = 0;

        foreach (IdentifikasiItem::all() as $item) {
            $eligibleSatkerCount = $eligibilityService->eligibleSatkerCountForItem($item);

            if ($eligibleSatkerCount === null) {
                continue;
            }

            $item->update(['eligible_satker_count' => $eligibleSatkerCount]);
            $this->command->line("  <fg=green>OK</> [{$eligibleSatkerCount} satker] {$item->item_name}");
            $updated++;
        }

        $this->command->info("Selesai. {$updated} item diperbarui dengan penyebut statistik spesifik.");
        $this->command->newLine();
        $this->command->comment('Item lainnya tetap NULL (laporan memakai total semua satker).');
    }
}
