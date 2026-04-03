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

class PersonnelRequestModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_admin_satker_creates_pending_personnel_when_verification_mode_is_pending(): void
    {
        Setting::setValue('personnel_request_mode', 'pending_verification');

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

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $response = $this->actingAs($adminSatker)->post(route('admin.personnel.store'), [
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'rank_id' => $rank->id,
            'satker_id' => 999,
            'jabatan' => 'BANIT',
            'bagian' => 'SAT RESKRIM',
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'religion' => 'Katolik',
            'golongan' => 'BINTARA',
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('personnels', [
            'nrp' => '76100151',
            'satker_id' => $satker->id,
            'verification_status' => 'pending_verification',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_satker_creates_approved_personnel_when_verification_mode_is_auto(): void
    {
        Setting::setValue('personnel_request_mode', 'auto');

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

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $response = $this->actingAs($adminSatker)->post(route('admin.personnel.store'), [
            'nrp' => '76100152',
            'full_name' => 'BUDI SANTOSO',
            'rank_id' => $rank->id,
            'satker_id' => $satker->id,
            'jabatan' => 'BANIT',
            'bagian' => 'SAT RESKRIM',
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'religion' => 'Islam',
            'golongan' => 'BINTARA',
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personnels', [
            'nrp' => '76100152',
            'verification_status' => 'approved',
            'is_active' => true,
        ]);
    }

    public function test_admin_satker_cannot_add_duplicate_nrp_from_other_satker(): void
    {
        Setting::setValue('personnel_request_mode', 'auto');

        $satkerA = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $satkerB = Satker::create([
            'name' => 'POLRES DOMPU',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satkerA->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $user = User::factory()->create([
            'name' => 'PERSONIL LAMA',
            'nrp_nip' => '76100153',
            'satker_id' => $satkerB->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100153',
            'full_name' => 'PERSONIL LAMA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satkerB->id,
            'religion' => 'Islam',
            'is_active' => true,
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($adminSatker)->from(route('admin.personnel.index'))->post(route('admin.personnel.store'), [
            'nrp' => '76100153',
            'full_name' => 'PERSONIL BARU',
            'rank_id' => $rank->id,
            'satker_id' => $satkerA->id,
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'religion' => 'Islam',
            'golongan' => 'BINTARA',
        ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHas('error', 'NRP/NIP tersebut sudah terdaftar pada satker lain. Silakan koordinasikan dengan superadmin.');

        $this->assertDatabaseMissing('personnels', [
            'full_name' => 'PERSONIL BARU',
        ]);
    }

    public function test_superadmin_can_approve_pending_personnel_request(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $user = User::factory()->create([
            'nrp_nip' => '76100154',
            'satker_id' => $satker->id,
            'is_active' => false,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100154',
            'full_name' => 'USULAN BARU',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => Rank::create([
                'name' => 'AIPTU',
                'category' => 'BINTARA',
                'sort_order' => 3,
            ])->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => false,
            'verification_status' => 'pending_verification',
        ]);

        $response = $this->actingAs($superadmin)
            ->from(route('admin.personnel.index', ['status' => 'pending_verification']))
            ->post(route('admin.personnel.approve-verification', $personnel));

        $response->assertRedirect(route('admin.personnel.index', ['status' => 'pending_verification']));
        $response->assertSessionHas('success', 'Usulan personel berhasil disetujui dan diaktifkan.');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'verification_status' => 'approved',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_reject_pending_personnel_request(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES DOMPU',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $user = User::factory()->create([
            'nrp_nip' => '76100155',
            'satker_id' => $satker->id,
            'is_active' => false,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100155',
            'full_name' => 'USULAN DITOLAK',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => Rank::create([
                'name' => 'BRIPKA',
                'category' => 'BINTARA',
                'sort_order' => 4,
            ])->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => false,
            'verification_status' => 'pending_verification',
        ]);

        $response = $this->actingAs($superadmin)
            ->from(route('admin.personnel.index', ['status' => 'pending_verification']))
            ->post(route('admin.personnel.reject-verification', $personnel));

        $response->assertRedirect(route('admin.personnel.index', ['status' => 'pending_verification']));
        $response->assertSessionHas('warning', 'Usulan personel ditolak dan tetap nonaktif.');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'verification_status' => 'rejected',
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }
}
