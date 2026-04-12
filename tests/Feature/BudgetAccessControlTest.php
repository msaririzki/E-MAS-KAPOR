<?php

namespace Tests\Feature;

use App\Models\BudgetYear;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BudgetAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('admin_satker');
        Role::findOrCreate('superadmin');
    }

    public function test_admin_satker_cannot_see_budget_management_controls(): void
    {
        $adminSatker = User::factory()->create();
        $adminSatker->assignRole('admin_satker');

        BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $response = $this->actingAs($adminSatker)->get(route('admin.budget.index'));

        $response->assertOk();
        $response->assertDontSeeText('Tambah Tahun Anggaran');
        $response->assertDontSeeText('Siapkan Anggaran Berikutnya');
    }

    public function test_admin_satker_cannot_create_budget_year(): void
    {
        $adminSatker = User::factory()->create();
        $adminSatker->assignRole('admin_satker');

        $this->actingAs($adminSatker)
            ->post(route('admin.budget.store-year'), [
                'year' => 2027,
                'name' => 'Tahun Anggaran 2027',
            ])
            ->assertForbidden();
    }

    public function test_historical_budget_year_is_shown_as_archive_even_if_flagged_active(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        BudgetYear::create([
            'year' => 2025,
            'name' => 'Tahun Anggaran 2025',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.budget.index'));

        $response->assertOk();
        $response->assertSeeText('Riwayat / Arsip');
        $response->assertDontSeeText('Aktif di Modul Budget');
    }

    public function test_admin_cannot_create_package_for_historical_budget_year(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $budgetYear = BudgetYear::create([
            'year' => 2025,
            'name' => 'Tahun Anggaran 2025',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.budget.store-package', $budgetYear), [
                'name' => 'Paket Arsip',
            ])
            ->assertForbidden();
    }
}
