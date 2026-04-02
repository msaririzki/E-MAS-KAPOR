<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\User;
use App\Models\WarehouseItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseIndexPaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Role::where('name', 'admin_gudang')->exists()) {
            Role::create(['name' => 'admin_gudang']);
        }
    }

    public function test_admin_gudang_can_render_warehouse_index_with_paginated_items(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $adminGudang = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminGudang->assignRole('admin_gudang');

        foreach (range(1, 11) as $number) {
            $item = WarehouseItem::create([
                'name' => 'Barang Gudang '.$number,
                'unit' => 'PCS',
                'price' => 10000,
            ]);

            $item->sizes()->create([
                'size_label' => 'ALL',
                'stock' => $number,
            ]);
        }

        $response = $this->actingAs($adminGudang)->get(route('admin.warehouse-items.index'));

        $response->assertOk();
        $response->assertSeeText('Data Gudang');
        $response->assertSeeText('Menampilkan 1 sampai 10 dari 11 entri');
        $response->assertSeeText('Halaman 1 dari 2');
        $response->assertSee('ajax-link', false);
    }
}
