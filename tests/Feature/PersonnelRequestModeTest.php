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
        $response->assertSessionHasErrors([
            'nrp' => 'NRP/NIP 76100153 sudah terdaftar atas nama PERSONIL LAMA pada satker POLRES DOMPU. Silakan koordinasikan dengan superadmin.',
        ]);

        $this->assertDatabaseMissing('personnels', [
            'full_name' => 'PERSONIL BARU',
        ]);
    }

    public function test_admin_satker_cannot_add_duplicate_nrp_when_login_user_already_exists_without_personnel_record(): void
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

        $existingUser = User::factory()->create([
            'name' => 'AKUN PERSONIL LAMA',
            'nrp_nip' => '76100156',
            'satker_id' => $satker->id,
        ]);
        $existingUser->assignRole('personil');

        $response = $this->actingAs($adminSatker)
            ->from(route('admin.personnel.index'))
            ->post(route('admin.personnel.store'), [
                'nrp' => '76100156',
                'full_name' => 'PERSONIL BARU',
                'rank_id' => $rank->id,
                'satker_id' => $satker->id,
                'personnel_type' => 'Polri',
                'gender' => 'L',
                'religion' => 'Islam',
                'golongan' => 'BINTARA',
            ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHasErrors([
            'nrp' => 'NRP/NIP 76100156 sudah terdaftar atas nama AKUN PERSONIL LAMA pada satker POLRES BIMA.',
        ]);

        $this->assertDatabaseMissing('personnels', [
            'full_name' => 'PERSONIL BARU',
        ]);
    }

    public function test_admin_satker_cannot_update_personnel_to_duplicate_nrp_from_other_satker(): void
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

        $currentUser = User::factory()->create([
            'name' => 'PERSONIL SATKER A',
            'nrp_nip' => '76100157',
            'satker_id' => $satkerA->id,
        ]);
        $currentUser->assignRole('personil');

        $personnelToEdit = Personnel::create([
            'user_id' => $currentUser->id,
            'nrp' => '76100157',
            'full_name' => 'PERSONIL SATKER A',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satkerA->id,
            'religion' => 'Islam',
            'is_active' => true,
            'verification_status' => 'approved',
        ]);

        $otherUser = User::factory()->create([
            'name' => 'PERSONIL SATKER B',
            'nrp_nip' => '76100158',
            'satker_id' => $satkerB->id,
        ]);
        $otherUser->assignRole('personil');

        Personnel::create([
            'user_id' => $otherUser->id,
            'nrp' => '76100158',
            'full_name' => 'PERSONIL SATKER B',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satkerB->id,
            'religion' => 'Islam',
            'is_active' => true,
            'verification_status' => 'approved',
        ]);

        $response = $this->actingAs($adminSatker)
            ->from(route('admin.personnel.index'))
            ->put(route('admin.personnel.update', $personnelToEdit), [
                'nrp' => '76100158',
                'full_name' => 'PERSONIL SATKER A',
                'rank_id' => $rank->id,
                'satker_id' => $satkerA->id,
                'personnel_type' => 'Polri',
                'gender' => 'L',
                'religion' => 'Islam',
                'golongan' => 'BINTARA',
            ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHasErrors([
            'nrp' => 'NRP/NIP 76100158 sudah terdaftar atas nama PERSONIL SATKER B pada satker POLRES DOMPU. Silakan koordinasikan dengan superadmin.',
        ]);

        $this->assertDatabaseHas('personnels', [
            'id' => $personnelToEdit->id,
            'nrp' => '76100157',
        ]);
    }

    public function test_superadmin_cannot_update_personnel_to_duplicate_nrp_when_login_user_already_exists(): void
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

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $currentUser = User::factory()->create([
            'name' => 'PERSONIL EDIT',
            'nrp_nip' => '76100159',
            'satker_id' => $satker->id,
        ]);
        $currentUser->assignRole('personil');

        $personnelToEdit = Personnel::create([
            'user_id' => $currentUser->id,
            'nrp' => '76100159',
            'full_name' => 'PERSONIL EDIT',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'verification_status' => 'approved',
        ]);

        $existingUser = User::factory()->create([
            'name' => 'AKUN CADANGAN',
            'nrp_nip' => '76100160',
            'satker_id' => $satker->id,
        ]);
        $existingUser->assignRole('personil');

        $response = $this->actingAs($superadmin)
            ->from(route('admin.personnel.index'))
            ->put(route('admin.personnel.update', $personnelToEdit), [
                'nrp' => '76100160',
                'full_name' => 'PERSONIL EDIT',
                'rank_id' => $rank->id,
                'satker_id' => $satker->id,
                'personnel_type' => 'Polri',
                'gender' => 'L',
                'religion' => 'Islam',
                'golongan' => 'BINTARA',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHasErrors([
            'nrp' => 'NRP/NIP 76100160 sudah terdaftar atas nama AKUN CADANGAN pada satker POLRES BIMA.',
        ]);

        $this->assertDatabaseHas('personnels', [
            'id' => $personnelToEdit->id,
            'nrp' => '76100159',
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
