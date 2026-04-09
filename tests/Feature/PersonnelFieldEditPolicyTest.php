<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelFieldEditPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_admin_satker_can_edit_full_personnel_profile_within_own_satker_scope(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $otherSatker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AKBP',
            'category' => 'PAMEN',
            'sort_order' => 1,
        ]);

        $otherRank = Rank::create([
            'name' => 'KOMPOL',
            'category' => 'PAMEN',
            'sort_order' => 2,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $user = User::factory()->create([
            'name' => 'EKO SUTOMO',
            'nrp_nip' => '82051489',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'PAMEN',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($adminSatker)->put(route('admin.personnel.update', $personnel), [
            'nrp' => '99999999',
            'full_name' => 'NAMA BARU',
            'rank_id' => $otherRank->id,
            'satker_id' => $otherSatker->id,
            'personnel_type' => 'PNS',
            'gender' => 'P',
            'jabatan' => 'JABATAN BARU',
            'bagian' => 'BAGIAN BARU',
            'golongan' => 'III/A',
            'religion' => 'Hindu',
            'phone' => '08123456789',
            'keterangan' => 'KET BARU',
            'keterangan_2' => 'KET2 BARU',
            'keterangan_3' => 'KET3 BARU',
            'keterangan_4' => 'KET4 BARU',
            'kapor_sizes' => ['topi' => '60'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'nrp' => '99999999',
            'full_name' => 'NAMA BARU',
            'rank_id' => $otherRank->id,
            'satker_id' => $satker->id,
            'jabatan' => 'JABATAN BARU',
            'bagian' => 'BAGIAN BARU',
            'keterangan' => 'KET BARU',
            'keterangan_2' => 'KET2 BARU',
            'keterangan_3' => 'KET3 BARU',
            'keterangan_4' => 'KET4 BARU',
            'personnel_type' => 'PNS',
            'gender' => 'P',
            'golongan' => 'III/A',
            'religion' => 'Hindu',
            'phone' => '08123456789',
        ]);

        $this->assertSame(['topi' => '60'], $personnel->fresh()->kapor_sizes);
        $this->assertSame('99999999', $user->fresh()->nrp_nip);
        $this->assertSame('NAMA BARU', $user->fresh()->name);
        $this->assertSame($satker->id, $user->fresh()->satker_id);
    }

    public function test_admin_satker_gets_info_feedback_when_no_personnel_data_changes(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $otherSatker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AKBP',
            'category' => 'PAMEN',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $user = User::factory()->create([
            'name' => 'EKO SUTOMO',
            'nrp_nip' => '82051489',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'PAMEN',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($adminSatker)->put(route('admin.personnel.update', $personnel), [
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'rank_id' => $rank->id,
            'satker_id' => $otherSatker->id,
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'golongan' => 'PAMEN',
            'religion' => 'Islam',
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info', 'Tidak ada perubahan pada data personel.');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'satker_id' => $satker->id,
            'keterangan_2' => 'KET2 LAMA',
        ]);
    }

    public function test_personil_can_update_jabatan_bagian_and_sizes_from_personil_form(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
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
            'jabatan' => 'JABATAN AWAL',
            'bagian' => 'BAGIAN AWAL',
            'satker_id' => $satker->id,
            'religion' => 'Katolik',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $response = $this->actingAs($user)->post(route('personil.kapor.store'), [
            'jabatan' => 'BANIT RESKRIM',
            'bagian' => 'SAT RESKRIM',
            'kemeja' => '15',
            'celana' => '32',
            'olahraga' => 'B',
            'jaket' => 'B',
            'topi' => '57',
            'sabuk' => '42',
            'sepatu_dinas' => '41',
            'sepatu_olahraga' => '41',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $personnel->refresh();

        $this->assertSame('BANIT RESKRIM', $personnel->jabatan);
        $this->assertSame('SAT RESKRIM', $personnel->bagian);
        $this->assertSame($satker->id, $personnel->satker_id);
        $this->assertSame('15', $personnel->kapor_sizes['kemeja']);
        $this->assertSame('57', $personnel->kapor_sizes['topi']);
    }

    public function test_personil_can_save_identity_first_and_generate_audit_log(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Dompu',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'RIZAL ARDIANSYAH',
            'nrp_nip' => '76100161',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100161',
            'full_name' => 'RIZAL ARDIANSYAH',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN SDM',
            'bagian' => null,
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $response = $this->actingAs($user)->post(route('personil.kapor.store'), [
            'mode' => 'identity',
            'jabatan' => 'BANIT SAMAPTA',
            'bagian' => 'SIAGA',
        ]);

        $response->assertRedirect(route('dashboard').'#ukuran-form');
        $response->assertSessionHas('success');

        $personnel->refresh();

        $this->assertSame('BANIT SAMAPTA', $personnel->jabatan);
        $this->assertSame('SIAGA', $personnel->bagian);

        $auditLog = AuditLog::query()->latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertSame('Edit Referensi SDM Personil', $auditLog->action);
        $this->assertSame('Data Personil', $auditLog->category);
        $this->assertSame($personnel->id, $auditLog->auditable_id);
        $this->assertSame(['jabatan' => 'JABATAN SDM', 'bagian' => null], $auditLog->old_values);
        $this->assertSame(['jabatan' => 'BANIT SAMAPTA', 'bagian' => 'SIAGA'], $auditLog->new_values);
    }

    public function test_personil_non_polres_can_update_jabatan_and_sizes_without_bagian(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $satker = Satker::create([
            'name' => 'DIT LANTAS',
            'code' => 'DITLANTAS',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'AYUB',
            'nrp_nip' => '197001012014121003',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '197001012014121003',
            'full_name' => 'AYUB',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN AWAL',
            'bagian' => 'BAGIAN SDM',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $response = $this->actingAs($user)->post(route('personil.kapor.store'), [
            'jabatan' => 'BANUM URMINTU SUBBAGRENMIN DITLANTAS',
            'kemeja' => '15',
            'celana' => '32',
            'olahraga' => 'B',
            'jaket' => 'B',
            'topi' => '57',
            'sabuk' => '42',
            'sepatu_dinas' => '41',
            'sepatu_olahraga' => '41',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $personnel->refresh();

        $this->assertSame('BANUM URMINTU SUBBAGRENMIN DITLANTAS', $personnel->jabatan);
        $this->assertSame('BAGIAN SDM', $personnel->bagian);
        $this->assertSame('15', $personnel->kapor_sizes['kemeja']);
        $this->assertSame('57', $personnel->kapor_sizes['topi']);
    }

    public function test_personil_dashboard_hides_bagian_field_for_non_polres_satker(): void
    {
        $satker = Satker::create([
            'name' => 'DIT LANTAS',
            'code' => 'DITLANTAS',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'AYUB',
            'nrp_nip' => '197001012014121003',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '197001012014121003',
            'full_name' => 'AYUB',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANUM DITLANTAS',
            'bagian' => 'BAGIAN SDM',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('1. Jabatan');
        $response->assertDontSee('name="bagian"', false);
        $response->assertDontSeeText('Bag/Fungsi');
    }

    public function test_personil_write_routes_are_blocked_when_system_is_locked_but_history_remains_read_only(): void
    {
        Setting::setValue('is_system_locked', 'true');

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
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
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $this->actingAs($user)
            ->get(route('personil.kapor.history'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('personil.kapor.store'), [
                'jabatan' => 'BANIT RESKRIM',
                'bagian' => 'SAT RESKRIM',
                'kemeja' => '15',
                'celana' => '32',
                'olahraga' => 'B',
                'jaket' => 'B',
                'topi' => '57',
                'sabuk' => '42',
                'sepatu_dinas' => '41',
                'sepatu_olahraga' => '41',
            ])
            ->assertForbidden();
    }

    public function test_admin_satker_write_routes_are_blocked_outside_input_period_but_index_remains_read_only(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subMonths(3)->toDateString());
        Setting::setValue('input_end_date', now()->subDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $this->actingAs($adminSatker)
            ->get(route('admin-satker.kebutuhan.index'))
            ->assertOk();

        $this->actingAs($adminSatker)
            ->post(route('admin-satker.kebutuhan.store'), [
                'items' => [],
            ])
            ->assertForbidden();
    }

    public function test_personil_dashboard_renders_mobile_first_form_flow(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100171',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100171',
            'full_name' => 'BAYU SAPUTRA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT OPERASI',
            'bagian' => 'SIAGA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Data Kaporlap Personil');
        $response->assertSeeText('Data tugas');
        $response->assertSeeText('Ukuran kaporlap');
    }
}
