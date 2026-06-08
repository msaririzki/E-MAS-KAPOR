<?php

namespace Tests\Feature;

use App\Models\IdentifikasiItem;
use App\Models\Satker;
use App\Services\KebutuhanEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KebutuhanEligibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_active_identifikasi_items_are_visible_to_any_satker(): void
    {
        $satker = Satker::create([
            'name' => 'BID DOKKES POLDA NTB',
            'code' => 'DOKKES',
        ]);

        $lantasItem = IdentifikasiItem::create([
            'item_name' => 'ROMPI LANTAS',
            'category' => 'Tutup_Badan',
            'is_active' => true,
            'eligible_satker_count' => 11,
        ]);

        $brimobItem = IdentifikasiItem::create([
            'item_name' => 'PRIMOD BRIMOB',
            'category' => 'Tutup_Badan',
            'is_active' => true,
            'eligible_satker_count' => 1,
        ]);

        IdentifikasiItem::create([
            'item_name' => 'ITEM NONAKTIF',
            'category' => 'Tutup_Badan',
            'is_active' => false,
        ]);

        $visibleItemIds = app(KebutuhanEligibilityService::class)
            ->eligibleItemsForSatker($satker)
            ->pluck('id');

        $this->assertTrue($visibleItemIds->contains($lantasItem->id));
        $this->assertTrue($visibleItemIds->contains($brimobItem->id));
        $this->assertCount(2, $visibleItemIds);
    }

    public function test_specific_statistic_denominators_are_still_available(): void
    {
        $service = app(KebutuhanEligibilityService::class);

        $lantasItem = IdentifikasiItem::make(['item_name' => 'HELM POLANTAS']);
        $reskrimItem = IdentifikasiItem::make(['item_name' => 'TACTICAL RESINTEL']);
        $generalItem = IdentifikasiItem::make(['item_name' => 'KAOS OLAHRAGA']);

        $this->assertSame(11, $service->eligibleSatkerCountForItem($lantasItem));
        $this->assertSame(17, $service->eligibleSatkerCountForItem($reskrimItem));
        $this->assertNull($service->eligibleSatkerCountForItem($generalItem));
    }
}
