<?php

namespace Tests\Feature;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\KebutuhanItem;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSatkerKebutuhanRowClickTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin_satker');
    }

    public function test_index_rows_link_directly_to_detail_page(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $item = IdentifikasiItem::create([
            'item_name' => 'SEPATU OLAHRAGA',
            'category' => 'Tutup_Kaki',
            'eligible_satker_count' => 1,
            'is_active' => true,
        ]);

        $kebutuhan = Kebutuhan::create([
            'satker_id' => $satker->id,
            'user_id' => $adminSatker->id,
            'title' => 'Pengajuan Kebutuhan TA 2027',
            'fiscal_year' => '2027',
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        KebutuhanItem::create([
            'kebutuhan_id' => $kebutuhan->id,
            'identifikasi_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($adminSatker)->get(route('admin-satker.kebutuhan.index'));

        $response->assertOk();
        $response->assertSee('data-url="'.route('admin-satker.kebutuhan.show', $kebutuhan).'"', false);
        $response->assertSee('class="kebutuhan-actions"', false);
    }
}
