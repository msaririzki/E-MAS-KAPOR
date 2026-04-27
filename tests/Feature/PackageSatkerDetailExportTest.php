<?php

namespace Tests\Feature;

use App\Exports\PackageSatkerDetailExport;
use App\Exports\PackageSatkerDetailSheet;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageSatkerDetailExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('admin_satker');
    }

    public function test_admin_can_download_nominatif_per_satker_export(): void
    {
        [$package] = $this->createPackageWithRecipient();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Excel::fake();

        $this->actingAs($admin)
            ->get(route('admin.budget.export-detail-satker', $package))
            ->assertOk();

        Excel::assertDownloaded(
            'Nominatif_Per_Satker_Paket_Seragam_2026.xlsx',
            fn ($export) => $export instanceof PackageSatkerDetailExport
        );
    }

    public function test_satker_detail_sheet_lists_personnel_and_received_items(): void
    {
        [$package, $satker] = $this->createPackageWithRecipient();
        $admin = User::factory()->create(['satker_id' => $satker->id]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $sheet = new PackageSatkerDetailSheet($package->fresh('budgetYear'), $satker, 'Polres Bima');
        $rows = $sheet->array();

        $this->assertContains('SATKER: POLRES BIMA', array_column($rows, 0));
        $this->assertContains('Budi Santoso', array_column($rows, 1));
        $this->assertContains("BARET LAPANGAN\nSEPATU PDL", array_column($rows, 8));
        $this->assertContains("58\n42", array_column($rows, 10));
        $this->assertNotContains('HARGA SATUAN', $rows[8]);

        $personnelRows = array_filter($rows, fn (array $row) => ($row[1] ?? null) === 'Budi Santoso');
        $this->assertCount(1, $personnelRows);
    }

    public function test_admin_satker_can_monitor_allocations_for_own_satker(): void
    {
        [$package, $satker] = $this->createPackageWithRecipient();
        $adminSatker = User::factory()->create(['satker_id' => $satker->id]);
        $adminSatker->assignRole('admin_satker');

        $response = $this->actingAs($adminSatker)
            ->get(route('admin-satker.allocations', ['package_id' => $package->id]));

        $response->assertOk();
        $response->assertSeeText('Penerima Barang Satker');
        $response->assertSeeText('Budi Santoso');
        $response->assertSeeText('BARET LAPANGAN');
        $response->assertSeeText('SEPATU PDL');
        $response->assertSeeText('42');
    }

    private function createPackageWithRecipient(): array
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Seragam',
            'status' => 'draft',
            'total_budget' => 0,
        ]);

        $kaporItem = KaporItem::create([
            'category' => 'Tutup_Kepala',
            'item_name' => 'BARET LAPANGAN',
            'price' => 100000,
            'unit' => 'PCS',
            'is_active' => true,
            'for_identifikasi' => true,
        ]);

        $packageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $kaporItem->id,
            'calculated_qty' => 1,
            'calculated_total' => 100000,
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $satker->id,
            'recipient_filters' => null,
            'matched_count' => 1,
        ]);

        $secondKaporItem = KaporItem::create([
            'category' => 'Tutup_Kaki',
            'item_name' => 'SEPATU PDL',
            'price' => 250000,
            'unit' => 'PASANG',
            'is_active' => true,
            'for_identifikasi' => true,
        ]);

        $secondPackageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $secondKaporItem->id,
            'calculated_qty' => 1,
            'calculated_total' => 250000,
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $secondPackageItem->id,
            'satker_id' => $satker->id,
            'recipient_filters' => null,
            'matched_count' => 1,
        ]);

        Personnel::create([
            'nrp' => '76110001',
            'full_name' => 'Budi Santoso',
            'satker_id' => $satker->id,
            'rank_id' => $rank->id,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'jabatan' => 'Banit',
            'bagian' => 'Logistik',
            'kapor_sizes' => ['topi' => '58', 'sepatu_dinas' => '42'],
            'is_active' => true,
        ]);

        return [$package, $satker];
    }
}
