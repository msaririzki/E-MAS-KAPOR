<?php

namespace Database\Seeders;

use App\Models\IdentifikasiItem;
use App\Services\KebutuhanEligibilityService;
use Illuminate\Database\Seeder;

/**
 * Mengisi eligible_satker_count berdasarkan aturan eligibility item kebutuhan.
 *
 * - Lantas: 11 satker (DIT LANTAS + 10 Polres)
 * - Polair: 11 satker (DIT POLAIRUD + 10 Polres)
 * - Reskrim: 17 satker (Intel/Intelkam sampai Bitkum + Polres)
 * - TIK: 11 satker (BID TIK + 10 Polres)
 * - Humas: 11 satker (BID HUMAS + 10 Polres)
 * - Primod/khusus polda: 1 satker
 * - NULL: item umum, semua satker bisa memilih
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
            $this->command->line("  <fg=green>✓</> [{$eligibleSatkerCount} satker] {$item->item_name}");
            $updated++;
        }

        $this->command->info("Selesai. {$updated} item diperbarui dengan eligible satker spesifik.");
        $this->command->newLine();
        $this->command->comment('Item lainnya tetap NULL (berlaku untuk semua satker).');
    }
}
