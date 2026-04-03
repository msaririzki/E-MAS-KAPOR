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

    public function test_admin_satker_can_only_edit_field_owned_by_satker_workflow(): void
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
            'jabatan' => 'JABATAN BARU',
            'bagian' => 'BAGIAN BARU',
            'keterangan' => 'KET BARU',
            'keterangan_2' => 'KET2 BARU',
            'kapor_sizes' => ['topi' => '60'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'rank_id' => $rank->id,
            'satker_id' => $satker->id,
            'jabatan' => 'JABATAN BARU',
            'bagian' => 'BAGIAN BARU',
            'keterangan' => 'KET BARU',
            'keterangan_2' => 'KET2 LAMA',
        ]);

        $this->assertSame(['topi' => '57'], $personnel->fresh()->kapor_sizes);
        $this->assertSame('82051489', $user->fresh()->nrp_nip);
        $this->assertSame($satker->id, $user->fresh()->satker_id);
    }

    public function test_admin_satker_gets_info_feedback_when_only_non_editable_fields_change(): void
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
            'satker_id' => $otherSatker->id,
            'keterangan_2' => 'KET2 BARU',
            'keterangan' => 'KET LAMA',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info', 'Tidak ada perubahan pada field yang dapat Anda ubah.');

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
        $this->assertSame('15', $personnel->kapor_sizes['kemeja']);
        $this->assertSame('57', $personnel->kapor_sizes['topi']);
    }
}
