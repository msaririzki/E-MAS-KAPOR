<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetPackageReviewSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_finalizing_budget_package_generates_personnel_item_allocations(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Seragam Lapangan',
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
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $satker->id,
            'recipient_filters' => null,
            'matched_count' => 0,
        ]);

        $userA = User::factory()->create([
            'name' => 'Briptu Andi',
            'nrp_nip' => '76110001',
            'satker_id' => $satker->id,
        ]);
        $userA->assignRole('personil');

        $userB = User::factory()->create([
            'name' => 'Bripda Siska',
            'nrp_nip' => '76110002',
            'satker_id' => $satker->id,
        ]);
        $userB->assignRole('personil');

        Personnel::create([
            'user_id' => $userA->id,
            'nrp' => '76110001',
            'full_name' => 'Briptu Andi',
            'satker_id' => $satker->id,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        Personnel::create([
            'user_id' => $userB->id,
            'nrp' => '76110002',
            'full_name' => 'Bripda Siska',
            'satker_id' => $satker->id,
            'gender' => 'P',
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        Personnel::create([
            'user_id' => null,
            'nrp' => '76110003',
            'full_name' => 'Tanpa Akun',
            'satker_id' => $satker->id,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.budget.update-package', $package), [
            'name' => $package->name,
            'description' => '',
            'status' => 'finalized',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('budget_packages', [
            'id' => $package->id,
            'status' => 'finalized',
        ]);

        $this->assertSame(2, PersonnelItemAllocation::count());
        $this->assertDatabaseHas('personnel_item_allocations', [
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'user_id' => $userA->id,
            'kapor_item_id' => $kaporItem->id,
            'fiscal_year' => 2026,
            'kapor_item_name_snapshot' => 'BARET LAPANGAN',
        ]);
    }

    public function test_moving_package_back_to_draft_removes_existing_allocations_for_that_package(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Seragam Lapangan',
            'status' => 'finalized',
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
        ]);

        $user = User::factory()->create([
            'satker_id' => $satker->id,
        ]);

        PersonnelItemAllocation::create([
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'kapor_item_id' => $kaporItem->id,
            'user_id' => $user->id,
            'satker_id' => $satker->id,
            'fiscal_year' => 2026,
            'allocation_status' => 'eligible',
            'allocated_at' => now(),
            'nrp_snapshot' => $user->nrp_nip,
            'full_name_snapshot' => $user->name,
            'satker_name_snapshot' => $satker->name,
            'kapor_item_name_snapshot' => 'BARET LAPANGAN',
            'item_category_snapshot' => 'Tutup Kepala',
            'budget_package_name_snapshot' => $package->name,
        ]);

        $response = $this->actingAs($superadmin)->put(route('admin.budget.update-package', $package), [
            'name' => $package->name,
            'description' => '',
            'status' => 'draft',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('personnel_item_allocations', 0);
    }

    public function test_updating_recipients_on_finalized_package_refreshes_review_allocations(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satkerA = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $satkerB = Satker::create([
            'name' => 'Polres Dompu',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 2,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satkerA->id,
        ]);
        $superadmin->assignRole('superadmin');

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Seragam Lapangan',
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
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $satkerA->id,
            'recipient_filters' => null,
            'matched_count' => 0,
        ]);

        $userA = User::factory()->create([
            'name' => 'Briptu Andi',
            'nrp_nip' => '76110001',
            'satker_id' => $satkerA->id,
        ]);
        $userA->assignRole('personil');

        $userB = User::factory()->create([
            'name' => 'Bripda Siska',
            'nrp_nip' => '76110002',
            'satker_id' => $satkerB->id,
        ]);
        $userB->assignRole('personil');

        Personnel::create([
            'user_id' => $userA->id,
            'nrp' => '76110001',
            'full_name' => 'Briptu Andi',
            'satker_id' => $satkerA->id,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        Personnel::create([
            'user_id' => $userB->id,
            'nrp' => '76110002',
            'full_name' => 'Bripda Siska',
            'satker_id' => $satkerB->id,
            'gender' => 'P',
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)->put(route('admin.budget.update-package', $package), [
            'name' => $package->name,
            'description' => '',
            'status' => 'finalized',
        ])->assertRedirect();

        $this->assertDatabaseHas('personnel_item_allocations', [
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'user_id' => $userA->id,
        ]);

        $response = $this->actingAs($superadmin)->postJson(
            route('admin.budget.wizard.save-recipients', $packageItem),
            [
                'satker_ids' => [$satkerB->id],
                'filters' => [],
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total_recipients', 1);

        $this->assertDatabaseMissing('personnel_item_allocations', [
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'user_id' => $userA->id,
        ]);

        $this->assertDatabaseHas('personnel_item_allocations', [
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'user_id' => $userB->id,
            'satker_id' => $satkerB->id,
        ]);
    }

    public function test_removing_item_from_finalized_package_removes_related_review_allocations(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personilUser = User::factory()->create([
            'name' => 'Briptu Andi',
            'nrp_nip' => '76110001',
            'satker_id' => $satker->id,
        ]);
        $personilUser->assignRole('personil');

        Personnel::create([
            'user_id' => $personilUser->id,
            'nrp' => '76110001',
            'full_name' => 'Briptu Andi',
            'satker_id' => $satker->id,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Seragam Lapangan',
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
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $satker->id,
            'recipient_filters' => null,
            'matched_count' => 0,
        ]);

        $this->actingAs($superadmin)->put(route('admin.budget.update-package', $package), [
            'name' => $package->name,
            'description' => '',
            'status' => 'finalized',
        ])->assertRedirect();

        $this->assertDatabaseHas('personnel_item_allocations', [
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'user_id' => $personilUser->id,
        ]);

        $response = $this->actingAs($superadmin)->delete(route('admin.budget.wizard.remove-item', $packageItem));

        $response->assertRedirect();

        $this->assertDatabaseMissing('package_items', [
            'id' => $packageItem->id,
        ]);

        $this->assertDatabaseMissing('personnel_item_allocations', [
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'user_id' => $personilUser->id,
        ]);
    }
}
