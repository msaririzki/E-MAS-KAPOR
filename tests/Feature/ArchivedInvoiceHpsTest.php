<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use App\Services\PersonnelItemAllocationSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ArchivedInvoiceHpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('personil');
    }

    public function test_archived_invoice_uses_allocation_snapshot_after_personnel_reset(): void
    {
        Setting::setValue('fiscal_year', '2027');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $satker = Satker::create([
            'name' => 'POLRES BIMA',
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
            'is_active' => false,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'PAKET I',
            'status' => 'finalized',
            'total_budget' => 200000,
        ]);

        $kaporItem = KaporItem::create([
            'category' => 'Tutup_Kepala',
            'invoice_group' => 'TOPI LAPANGAN',
            'item_name' => 'BARET LAPANGAN',
            'price' => 100000,
            'unit' => 'PCS',
            'is_active' => true,
            'for_identifikasi' => true,
        ]);

        $packageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $kaporItem->id,
            'calculated_qty' => 2,
            'calculated_total' => 200000,
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $satker->id,
            'recipient_filters' => null,
            'matched_count' => 2,
        ]);

        foreach ([
            ['76110001', 'Budi Santoso'],
            ['76110002', 'Andi Saputra'],
        ] as [$nrp, $name]) {
            $user = User::factory()->create([
                'name' => $name,
                'nrp_nip' => $nrp,
                'satker_id' => $satker->id,
            ]);
            $user->assignRole('personil');

            Personnel::create([
                'user_id' => $user->id,
                'nrp' => $nrp,
                'full_name' => $name,
                'satker_id' => $satker->id,
                'rank_id' => $rank->id,
                'gender' => 'L',
                'personnel_type' => 'Polri',
                'jabatan' => 'Banit',
                'bagian' => 'Logistik',
                'kapor_sizes' => ['topi' => '58'],
                'is_active' => true,
            ]);
        }

        app(PersonnelItemAllocationSnapshotService::class)->regenerateForBudgetPackage($package->fresh());

        Personnel::query()->delete();
        $package->update(['status' => 'archived']);

        $response = $this->actingAs($admin)->get(route('admin.budget.invoice', $package));

        $response->assertOk();
        $response->assertSeeText('BARET LAPANGAN');
        $response->assertSeeText('2');
        $response->assertSeeText('200.000');
    }
}
