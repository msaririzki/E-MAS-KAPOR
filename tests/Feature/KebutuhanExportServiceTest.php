<?php

namespace Tests\Feature;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\KebutuhanItem;
use App\Models\Satker;
use App\Models\User;
use App\Services\KebutuhanExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KebutuhanExportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_items_are_sorted_by_highest_percentage_within_each_category(): void
    {
        $satkers = collect(range(1, 10))->map(fn (int $number) => Satker::create([
            'name' => 'SATKER '.$number,
            'code' => 'SATKER-'.$number,
        ]));

        $highPercentageItem = IdentifikasiItem::create([
            'item_name' => 'ITEM PERSENTASE TINGGI',
            'category' => 'Tutup_Badan',
            'eligible_satker_count' => 2,
            'is_active' => true,
        ]);

        $mediumPercentageItem = IdentifikasiItem::create([
            'item_name' => 'ITEM PERSENTASE SEDANG',
            'category' => 'Tutup_Badan',
            'eligible_satker_count' => 4,
            'is_active' => true,
        ]);

        $higherCountItem = IdentifikasiItem::create([
            'item_name' => 'ITEM JUMLAH LEBIH BANYAK',
            'category' => 'Tutup_Badan',
            'is_active' => true,
        ]);

        $this->submitItems($satkers[0], [$highPercentageItem, $mediumPercentageItem, $higherCountItem]);
        $this->submitItems($satkers[1], [$highPercentageItem, $mediumPercentageItem, $higherCountItem]);
        $this->submitItems($satkers[2], [$higherCountItem]);

        $data = app(KebutuhanExportService::class)->build(2027);
        $items = $data['categoryGroups']->first()['items'];

        $this->assertSame([
            'ITEM PERSENTASE TINGGI',
            'ITEM PERSENTASE SEDANG',
            'ITEM JUMLAH LEBIH BANYAK',
        ], $items->pluck('name')->all());

        $this->assertSame([100, 50, 30], $items->pluck('percentage')->all());
    }

    /**
     * @param  iterable<IdentifikasiItem>  $items
     */
    private function submitItems(Satker $satker, iterable $items): void
    {
        $user = User::factory()->create([
            'satker_id' => $satker->id,
        ]);

        $kebutuhan = Kebutuhan::create([
            'satker_id' => $satker->id,
            'user_id' => $user->id,
            'title' => 'Pengajuan Kebutuhan TA 2027',
            'fiscal_year' => '2027',
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        foreach ($items as $item) {
            KebutuhanItem::create([
                'kebutuhan_id' => $kebutuhan->id,
                'identifikasi_item_id' => $item->id,
                'quantity' => 1,
            ]);
        }
    }
}
