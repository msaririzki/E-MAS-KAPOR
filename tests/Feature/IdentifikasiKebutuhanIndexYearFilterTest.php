<?php

namespace Tests\Feature;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IdentifikasiKebutuhanIndexYearFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
    }

    public function test_superadmin_index_defaults_to_identification_target_year(): void
    {
        Setting::setValue('fiscal_year', '2026');
        Setting::setValue('kebutuhan_target_year', '2027');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $targetSatker = Satker::create([
            'name' => 'POLRES TARGET',
            'code' => 'POLRES-TARGET',
            'sort_order' => 1,
        ]);
        $archiveSatker = Satker::create([
            'name' => 'POLRES ARSIP',
            'code' => 'POLRES-ARSIP',
            'sort_order' => 2,
        ]);

        $item = IdentifikasiItem::create([
            'item_name' => 'SEPATU LAPANGAN',
            'category' => 'Tutup_Kaki',
            'is_active' => true,
        ]);

        $targetKebutuhan = Kebutuhan::create([
            'satker_id' => $targetSatker->id,
            'user_id' => $superadmin->id,
            'title' => 'Pengajuan Kebutuhan TA 2027',
            'fiscal_year' => 2027,
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);
        $targetKebutuhan->items()->create([
            'identifikasi_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $archiveKebutuhan = Kebutuhan::create([
            'satker_id' => $archiveSatker->id,
            'user_id' => $superadmin->id,
            'title' => 'Pengajuan Kebutuhan TA 2026',
            'fiscal_year' => 2026,
            'status' => 'diajukan',
            'submitted_at' => now()->subYear(),
        ]);
        $archiveKebutuhan->items()->create([
            'identifikasi_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($superadmin)
            ->get(route('admin.identifikasi-kebutuhan.index'));

        $response->assertOk();
        $response->assertViewHas('selectedYear', 2027);
        $response->assertViewHas('targetFiscalYear', 2027);
        $response->assertViewHas('activeFiscalYear', 2026);
        $response->assertViewHas('kebutuhans', function ($paginator) use ($targetKebutuhan, $archiveKebutuhan): bool {
            $ids = $paginator->getCollection()->pluck('id');

            return $ids->contains($targetKebutuhan->id)
                && ! $ids->contains($archiveKebutuhan->id);
        });
        $response->assertSee('export-pdf?year=2027', false);
        $response->assertSee('data-auto-submit', false);
        $response->assertSeeText('Tahun');
        $response->assertSeeText('Cari');
        $response->assertSeeText('TA 2027');
        $response->assertSeeText('Berjalan');
    }

    public function test_superadmin_can_filter_identification_archive_year(): void
    {
        Setting::setValue('fiscal_year', '2026');
        Setting::setValue('kebutuhan_target_year', '2027');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $targetSatker = Satker::create([
            'name' => 'POLRES TARGET',
            'code' => 'POLRES-TARGET',
            'sort_order' => 1,
        ]);
        $archiveSatker = Satker::create([
            'name' => 'POLRES ARSIP',
            'code' => 'POLRES-ARSIP',
            'sort_order' => 2,
        ]);

        Kebutuhan::create([
            'satker_id' => $targetSatker->id,
            'user_id' => $superadmin->id,
            'title' => 'Pengajuan Kebutuhan TA 2027',
            'fiscal_year' => 2027,
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        $archiveKebutuhan = Kebutuhan::create([
            'satker_id' => $archiveSatker->id,
            'user_id' => $superadmin->id,
            'title' => 'Pengajuan Kebutuhan TA 2026',
            'fiscal_year' => 2026,
            'status' => 'disetujui',
            'submitted_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($superadmin)
            ->get(route('admin.identifikasi-kebutuhan.index', ['year' => 2026]));

        $response->assertOk();
        $response->assertViewHas('selectedYear', 2026);
        $response->assertViewHas('kebutuhans', function ($paginator) use ($archiveKebutuhan): bool {
            return $paginator->getCollection()->pluck('id')->contains($archiveKebutuhan->id);
        });
        $response->assertSee('export-pdf?year=2026', false);
        $response->assertSeeText('Arsip');
    }
}
