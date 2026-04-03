<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\Personnel;
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
        Role::findOrCreate('personil');
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

        $personnel->refresh();

        $this->assertSame('EGAS DOSANTOS', $personnel->full_name);
        $this->assertSame('76100151', $personnel->nrp);
        $this->assertNull($personnel->jabatan);
        $this->assertNull($personnel->bagian);
        $this->assertNull($personnel->keterangan);
        $this->assertNull($personnel->keterangan_2);
        $this->assertNull($personnel->keterangan_3);
        $this->assertNull($personnel->keterangan_4);
        $this->assertSame([], $personnel->kapor_sizes);
        $this->assertNull($personnel->nrp_issue_note);
        $this->assertNull($personnel->nrp_issue_resolved_at);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nrp_nip' => '76100151',
            'is_active' => true,
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
        $response->assertSeeText('Tahun Anggaran Aktif');
        $response->assertSeeText('TA 2026');
        $response->assertSeeText('Siapkan Anggaran Berikutnya');
    }
}
