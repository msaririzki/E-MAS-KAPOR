<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\IdentifikasiItem;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PrepareNextYearTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
        Role::findOrCreate('admin_satker');
        Role::findOrCreate('personil');
    }

    public function test_superadmin_can_open_identification_cycle_without_resetting_personnel(): void
    {
        Setting::setValue('fiscal_year', '2026');
        Setting::setValue('is_system_locked', 'true');
        Setting::setValue('is_satker_locked', 'true');
        Setting::setValue('is_review_locked', 'false');
        Setting::setValue('input_start_date', '2026-02-01');
        Setting::setValue('input_end_date', '2026-08-31');
        Setting::setValue('review_start_date', '2026-10-01');
        Setting::setValue('review_end_date', '2026-12-31');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $currentBudgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $currentBudgetYear->id,
            'name' => 'Paket Berjalan',
            'status' => 'draft',
            'total_budget' => 100000,
        ]);

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT RESKRIM',
            'bagian' => 'SAT RESKRIM',
            'satker_id' => $satker->id,
            'religion' => 'Katolik',
            'is_active' => true,
            'verification_status' => 'approved',
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($superadmin)
            ->post(route('superadmin.settings.open-identification-cycle'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('2026', Setting::getValue('fiscal_year'));
        $this->assertSame('2027', Setting::getValue('kebutuhan_target_year'));
        $this->assertSame('true', Setting::getValue('is_system_locked'));
        $this->assertSame('false', Setting::getValue('is_satker_locked'));
        $this->assertSame('true', Setting::getValue('is_review_locked'));
        $this->assertSame('2026-02-01', Setting::getValue('input_start_date'));
        $this->assertSame('2026-08-31', Setting::getValue('input_end_date'));
        $this->assertSame('2026-10-01', Setting::getValue('review_start_date'));
        $this->assertSame('2026-12-31', Setting::getValue('review_end_date'));

        $this->assertDatabaseHas('budget_years', [
            'year' => 2026,
            'is_active' => true,
        ]);

        $this->assertDatabaseMissing('budget_years', [
            'year' => 2027,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'satker_id' => $satker->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
            'satker_id' => $satker->id,
        ]);

        $this->assertDatabaseHas('budget_packages', [
            'id' => $package->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseMissing('annual_archives', [
            'fiscal_year' => 2026,
        ]);

        $item = IdentifikasiItem::create([
            'item_name' => 'SEPATU LAPANGAN',
            'category' => 'Tutup_Kaki',
            'description' => 'Item kebutuhan tahunan',
            'is_active' => true,
        ]);

        $satkerAdmin = User::factory()->create([
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $satkerAdmin->assignRole('admin_satker');

        $this->actingAs($satkerAdmin)
            ->post(route('admin-satker.kebutuhan.store'), [
                'items' => [$item->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kebutuhans', [
            'satker_id' => $satker->id,
            'fiscal_year' => 2027,
            'status' => 'diajukan',
        ]);
    }

    public function test_superadmin_can_prepare_next_year_without_deleting_user_accounts(): void
    {
        Setting::setValue('fiscal_year', '2026');
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', '2026-02-01');
        Setting::setValue('input_end_date', '2026-08-31');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $currentBudgetYear = BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $draftPackage = BudgetPackage::create([
            'budget_year_id' => $currentBudgetYear->id,
            'name' => 'Paket Draft',
            'status' => 'draft',
            'total_budget' => 100000,
        ]);

        $finalPackage = BudgetPackage::create([
            'budget_year_id' => $currentBudgetYear->id,
            'name' => 'Paket Final',
            'status' => 'finalized',
            'total_budget' => 200000,
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
            'budget_package_id' => $finalPackage->id,
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

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT RESKRIM',
            'bagian' => 'SAT RESKRIM',
            'keterangan' => 'STAF',
            'keterangan_2' => 'KET 2',
            'keterangan_3' => 'KET 3',
            'keterangan_4' => 'KET 4',
            'satker_id' => $satker->id,
            'religion' => 'Katolik',
            'is_active' => true,
            'verification_status' => 'approved',
            'kapor_sizes' => ['topi' => '57', 'kemeja' => '15'],
            'nrp_issue_note' => 'catatan lama',
            'nrp_issue_resolved_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->post(route('superadmin.settings.next-year'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('2027', Setting::getValue('fiscal_year'));
        $this->assertSame('true', Setting::getValue('is_system_locked'));
        $this->assertSame('true', Setting::getValue('is_satker_locked'));
        $this->assertSame('true', Setting::getValue('is_review_locked'));
        $this->assertSame('2028', Setting::getValue('kebutuhan_target_year'));
        $this->assertSame('2027-02-01', Setting::getValue('input_start_date'));
        $this->assertSame('2027-08-31', Setting::getValue('input_end_date'));

        $this->assertDatabaseHas('budget_years', [
            'year' => 2026,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('budget_years', [
            'year' => 2027,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('budget_packages', [
            'id' => $draftPackage->id,
            'status' => 'archived',
        ]);

        $this->assertDatabaseHas('budget_packages', [
            'id' => $finalPackage->id,
            'status' => 'archived',
        ]);

        $this->assertDatabaseMissing('personnels', [
            'id' => $personnel->id,
        ]);

        $allocation = PersonnelItemAllocation::query()
            ->where('budget_package_id', $finalPackage->id)
            ->where('package_item_id', $packageItem->id)
            ->firstOrFail();

        $this->assertSame('EGAS DOSANTOS', $allocation->full_name_snapshot);
        $this->assertSame('AIPDA', $allocation->rank_snapshot);
        $this->assertSame('BANIT RESKRIM', $allocation->jabatan_snapshot);
        $this->assertSame('SAT RESKRIM', $allocation->bagian_snapshot);
        $this->assertSame('L', $allocation->gender_snapshot);
        $this->assertSame('Polri', $allocation->personnel_type_snapshot);
        $this->assertSame('57', $allocation->kapor_sizes_snapshot['topi']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nrp_nip' => '76100151',
            'is_active' => false,
            'satker_id' => null,
        ]);
    }

    public function test_budget_page_shows_active_year_preparation_card_for_superadmin(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.budget.index'));

        $response->assertOk();
        $response->assertViewHas('activeFiscalYear', 2026);
        $response->assertViewHas('activeBudgetYear', fn ($budgetYear) => $budgetYear?->year === 2026);
    }

    public function test_settings_history_marks_only_fiscal_year_as_active(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        BudgetYear::create([
            'year' => 2025,
            'name' => 'Tahun Anggaran 2025',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.settings.index'));

        $response->assertOk();
        $response->assertSeeText('Buka Masa Identifikasi Kebutuhan TA 2027');
        $response->assertSeeText('Finalisasi');
        $response->assertSeeText('Reset ke TA 2027');
        $response->assertViewHas('yearlyStats', function (array $stats) {
            $currentYear = collect($stats)->firstWhere('fiscal_year', 2026);
            $historicalYear = collect($stats)->firstWhere('fiscal_year', 2025);

            return $currentYear !== null
                && $historicalYear !== null
                && $currentYear->is_active === true
                && $currentYear->status === 'Berjalan'
                && $historicalYear->is_active === false
                && $historicalYear->status === 'Belum Diarsipkan'
                && $historicalYear->has_budget_active_flag === true;
        });
    }
}
