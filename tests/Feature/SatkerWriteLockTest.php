<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SatkerWriteLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_admin_satker_can_view_settings_but_cannot_save_when_satker_lock_is_active(): void
    {
        $satker = $this->createSatker('RES-A', 'Polres A', 1);
        $adminSatker = User::factory()->create(['satker_id' => $satker->id]);
        $adminSatker->assignRole('admin_satker');

        Setting::setValue('is_satker_locked', 'true');

        $this->actingAs($adminSatker)
            ->get(route('admin-satker.settings'))
            ->assertOk();

        $response = $this->actingAs($adminSatker)
            ->from(route('admin-satker.settings'))
            ->put(route('admin-satker.settings.signatory.update'), [
                'signatory_name' => 'AKBP SATKER A',
                'signatory_title' => 'KABAG LOG POLRES A',
                'signatory_rank' => 'AKBP',
                'signatory_nrp' => '99887766',
                'location' => 'Lombok Barat',
                'organization_name' => 'KAPOLRES A',
            ]);

        $response->assertRedirect(route('admin-satker.settings'));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('settings', [
            'key' => "export_signatory.satker.{$satker->id}.signatory_name",
            'value' => 'AKBP SATKER A',
        ]);

        $this->actingAs($adminSatker)
            ->from(route('profile'))
            ->post(route('profile.updateTheme'), [
                'theme' => 'theme-ocean',
            ])
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertSame('theme-ocean', $adminSatker->fresh()->theme);
    }

    public function test_personil_can_update_own_data_when_satker_lock_is_active(): void
    {
        $satker = $this->createSatker('RES-B', 'Polres B', 2);
        $rank = Rank::create([
            'name' => 'BRIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
        $personil = User::factory()->create([
            'name' => 'PERSONEL UJI',
            'nrp_nip' => '99112233',
            'satker_id' => $satker->id,
            'theme' => 'theme-default',
        ]);
        $personil->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $personil->id,
            'nrp' => '99112233',
            'full_name' => 'PERSONEL UJI',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
        ]);

        Setting::setValue('is_satker_locked', 'true');
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $this->actingAs($personil)
            ->get(route('dashboard'))
            ->assertOk();

        $response = $this->actingAs($personil)
            ->from(route('dashboard'))
            ->post(route('profile.updateTheme'), [
                'theme' => 'theme-ocean',
            ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertSame('theme-ocean', $personil->fresh()->theme);

        $response = $this->actingAs($personil)
            ->post(route('personil.kapor.store'), [
                'mode' => 'identity',
                'jabatan' => 'JABATAN BARU',
                'bagian' => 'BAGIAN BARU',
                'phone' => '081234567890',
            ]);

        $response->assertRedirect(route('dashboard').'#ukuran-form');
        $response->assertSessionHas('success');

        $this->assertSame('JABATAN BARU', $personnel->fresh()->jabatan);
        $this->assertSame('BAGIAN BARU', $personnel->fresh()->bagian);
        $this->assertSame('081234567890', $personnel->fresh()->phone);
    }

    public function test_superadmin_can_update_global_settings_even_when_satker_lock_is_active(): void
    {
        $satker = $this->createSatker('POLDA-NTB', 'Polda NTB', 1);
        $superadmin = User::factory()->create(['satker_id' => $satker->id]);
        $superadmin->assignRole('superadmin');

        Setting::setValue('is_satker_locked', 'true');

        $response = $this->actingAs($superadmin)->put(route('superadmin.settings.update'), [
            'app_name' => 'E-MAS KAPOR Uji',
            'fiscal_year' => 2026,
            'is_system_locked' => 0,
            'is_satker_locked' => 1,
            'is_review_locked' => 0,
            'input_start_date' => now()->subMonth()->toDateString(),
            'input_end_date' => now()->addMonth()->toDateString(),
            'review_start_date' => now()->addMonth()->toDateString(),
            'review_end_date' => now()->addMonths(2)->toDateString(),
            'personnel_request_mode' => 'auto',
        ]);

        $response->assertRedirect();
        $this->assertSame('E-MAS KAPOR Uji', Setting::getValue('app_name'));
        $this->assertSame('true', Setting::getValue('is_satker_locked'));
    }

    private function createSatker(string $code, string $name, int $sortOrder): Satker
    {
        return Satker::create([
            'code' => $code,
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);
    }
}
