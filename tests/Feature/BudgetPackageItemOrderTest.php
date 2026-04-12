<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetPackageItemOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
    }

    public function test_preview_and_package_detail_follow_configured_package_item_sort_order(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Uji Urutan',
            'status' => 'draft',
            'total_budget' => 0,
        ]);

        $firstCreated = KaporItem::create([
            'category' => 'Tutup_Kepala',
            'item_name' => 'Urutan Item Ketiga',
            'price' => 100000,
            'unit' => 'PCS',
            'is_active' => true,
        ]);

        $secondCreated = KaporItem::create([
            'category' => 'Tutup_Badan',
            'item_name' => 'Urutan Item Pertama',
            'price' => 200000,
            'unit' => 'PCS',
            'is_active' => true,
        ]);

        $thirdCreated = KaporItem::create([
            'category' => 'Tutup_Kaki',
            'item_name' => 'Urutan Item Kedua',
            'price' => 300000,
            'unit' => 'PCS',
            'is_active' => true,
        ]);

        PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $firstCreated->id,
            'sort_order' => 3,
        ]);

        PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $secondCreated->id,
            'sort_order' => 1,
        ]);

        PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $thirdCreated->id,
            'sort_order' => 2,
        ]);

        $detailResponse = $this->actingAs($admin)
            ->get(route('admin.budget.show-package', $package));

        $detailResponse->assertOk();
        $detailResponse->assertSeeInOrder([
            'Urutan Item Pertama',
            'Urutan Item Kedua',
            'Urutan Item Ketiga',
        ]);

        $previewResponse = $this->actingAs($admin)
            ->get(route('admin.budget.wizard.step3', $package));

        $previewResponse->assertOk();
        $previewResponse->assertSeeInOrder([
            'Urutan Item Pertama',
            'Urutan Item Kedua',
            'Urutan Item Ketiga',
        ]);
    }
}
