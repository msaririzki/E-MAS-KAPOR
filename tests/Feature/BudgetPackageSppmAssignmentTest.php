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
use App\Models\User;
use App\Services\BudgetPackageSppmAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetPackageSppmAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_assign_selected_student_personnel_to_sppm_satker(): void
    {
        Role::findOrCreate('superadmin');
        Role::findOrCreate('personil');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $sourceSatker = Satker::create([
            'name' => 'SISWA DIKTUKBA',
            'code' => 'SISWA',
            'sort_order' => 1,
        ]);
        $targetSatker = Satker::create([
            'name' => 'SPN POLDA NTB',
            'code' => 'SPN',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $budgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'T.A. 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'PAKET SISWA',
            'status' => 'finalized',
        ]);

        $kaporItem = KaporItem::create([
            'item_name' => 'PAKAIAN DINAS',
            'category' => 'Tutup_Badan',
            'price' => 100000,
            'unit' => 'STEL',
            'is_active' => true,
        ]);

        $packageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $kaporItem->id,
            'custom_price' => 100000,
        ]);

        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $sourceSatker->id,
            'recipient_filters' => null,
            'matched_count' => 2,
        ]);

        $studentA = $this->createPersonnel($sourceSatker, $rank, '990001', 'SISWA SATU');
        $studentB = $this->createPersonnel($sourceSatker, $rank, '990002', 'SISWA DUA');

        $response = $this->actingAs($superadmin)->post(route('admin.budget.sppm-assignments.store', $package), [
            'source_satker_id' => $sourceSatker->id,
            'sppm_satker_id' => $targetSatker->id,
            'personnel_ids' => [$studentA->id],
            'notes' => 'Titipan pengadaan siswa',
        ]);

        $response->assertRedirect(route('admin.budget.sppm-assignments.index', [
            'budgetPackage' => $package,
            'source_satker_id' => $sourceSatker->id,
        ]));

        $this->assertDatabaseHas('budget_package_sppm_assignments', [
            'budget_package_id' => $package->id,
            'personnel_id' => $studentA->id,
            'original_satker_id' => $sourceSatker->id,
            'sppm_satker_id' => $targetSatker->id,
        ]);
        $this->assertDatabaseMissing('budget_package_sppm_assignments', [
            'budget_package_id' => $package->id,
            'personnel_id' => $studentB->id,
        ]);

        $satkerData = app(BudgetPackageSppmAssignmentService::class)->buildSppmSatkerData($package->fresh());

        $this->assertSame(1, $satkerData[$sourceSatker->id]['items'][0]['qty']);
        $this->assertSame(1, $satkerData[$targetSatker->id]['items'][0]['qty']);
        $this->assertSame('SISWA DIKTUKBA', $satkerData[$sourceSatker->id]['satker']->name);
        $this->assertSame('SPN POLDA NTB', $satkerData[$targetSatker->id]['satker']->name);
    }

    private function createPersonnel(Satker $satker, Rank $rank, string $nrp, string $name): Personnel
    {
        $user = User::factory()->create([
            'name' => $name,
            'nrp_nip' => $nrp,
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        return Personnel::create([
            'user_id' => $user->id,
            'nrp' => $nrp,
            'full_name' => $name,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'SISWA',
            'bagian' => 'DIKTUKBA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'verification_status' => 'approved',
        ]);
    }
}
