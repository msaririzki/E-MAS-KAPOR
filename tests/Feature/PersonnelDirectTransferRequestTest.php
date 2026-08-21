<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelDirectTransferRequestTest extends TestCase
{
    use RefreshDatabase;

    private Satker $targetSatker;

    private Satker $sourceSatker;

    private Rank $rank;

    private User $adminSatker;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'admin_satker', 'personil'] as $role) {
            Role::findOrCreate($role);
        }

        $this->targetSatker = Satker::create([
            'name' => 'POLRES LOMBOK TENGAH',
            'code' => 'POLRES-LOTENG',
            'sort_order' => 1,
        ]);
        $this->sourceSatker = Satker::create([
            'name' => 'POLRES SUMBAWA BARAT',
            'code' => 'POLRES-KSB',
            'sort_order' => 2,
        ]);
        $this->rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
        $this->adminSatker = User::factory()->create([
            'satker_id' => $this->targetSatker->id,
            'is_active' => true,
        ]);
        $this->adminSatker->assignRole('admin_satker');
    }

    public function test_admin_satker_transfer_is_approved_automatically_without_creating_duplicate_personnel(): void
    {
        $personnel = $this->createInactiveSourcePersonnel('95050400');

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.request-transfer'), ['nrp' => '95050400'])
            ->assertRedirect(route('admin.personnel.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, Personnel::where('nrp', '95050400')->count());
        $this->assertSame($this->targetSatker->id, $personnel->fresh()->satker_id);
        $this->assertTrue($personnel->fresh()->is_active);
        $this->assertTrue($personnel->fresh()->user->is_active);
        $this->assertDatabaseHas('personnel_transfer_requests', [
            'personnel_id' => $personnel->id,
            'from_satker_id' => $this->sourceSatker->id,
            'to_satker_id' => $this->targetSatker->id,
            'requested_by' => $this->adminSatker->id,
            'source_file' => 'Form Tambah Personel',
            'status' => 'approved',
        ]);
    }

    public function test_second_direct_transfer_request_is_stopped_after_personnel_has_moved(): void
    {
        $this->createInactiveSourcePersonnel('95050401');

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.request-transfer'), ['nrp' => '95050401']);

        $this->post(route('admin.personnel.request-transfer'), ['nrp' => '95050401'])
            ->assertSessionHas('info', 'Personel tersebut sudah tercatat pada satker Anda.');

        $this->assertDatabaseCount('personnel_transfer_requests', 1);
    }

    public function test_duplicate_nrp_in_add_form_offers_direct_transfer_to_admin_satker(): void
    {
        $this->createInactiveSourcePersonnel('95050403');

        $this->actingAs($this->adminSatker)
            ->from(route('admin.personnel.index'))
            ->post(route('admin.personnel.store'), [
                'modal_type' => 'add',
                'nrp' => '95050403',
                'full_name' => 'SHINTA KARTIKA DEWI, S.H.',
                'rank_id' => $this->rank->id,
                'jabatan' => 'BA BAG SDM',
                'bagian' => 'SAT SAMAPTA',
                'personnel_type' => 'Polri',
                'gender' => 'P',
            ])
            ->assertRedirect(route('admin.personnel.index'))
            ->assertSessionHasErrors('nrp')
            ->assertSessionHas('personnel_transfer_candidate', [
                'nrp' => '95050403',
                'name' => 'SHINTA KARTIKA DEWI, S.H.',
                'from_satker_name' => 'POLRES SUMBAWA BARAT',
            ]);
    }

    public function test_only_admin_satker_can_request_direct_transfer(): void
    {
        $this->createInactiveSourcePersonnel('95050402');
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.personnel.request-transfer'), ['nrp' => '95050402'])
            ->assertForbidden();

        $this->assertDatabaseCount('personnel_transfer_requests', 0);
    }

    public function test_superadmin_can_toggle_approval_mode(): void
    {
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('admin.personnel.transfer-requests.set-mode'), ['mode' => 'manual'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('manual', \App\Models\Setting::getValue('personnel_transfer_mode'));

        $this->post(route('admin.personnel.transfer-requests.set-mode'), ['mode' => 'auto'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('auto', \App\Models\Setting::getValue('personnel_transfer_mode'));
    }

    public function test_admin_satker_transfer_becomes_pending_in_manual_mode(): void
    {
        \App\Models\Setting::setValue('personnel_transfer_mode', 'manual');
        $personnel = $this->createInactiveSourcePersonnel('95050409');

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.request-transfer'), ['nrp' => '95050409'])
            ->assertRedirect(route('admin.personnel.index'))
            ->assertSessionHas('success', 'Pengajuan mutasi berhasil dikirim dan menunggu persetujuan Superadmin.');

        $this->assertSame(1, Personnel::where('nrp', '95050409')->count());
        $this->assertSame($this->sourceSatker->id, $personnel->fresh()->satker_id);
        $this->assertFalse($personnel->fresh()->is_active);
        $this->assertDatabaseHas('personnel_transfer_requests', [
            'personnel_id' => $personnel->id,
            'from_satker_id' => $this->sourceSatker->id,
            'to_satker_id' => $this->targetSatker->id,
            'requested_by' => $this->adminSatker->id,
            'source_file' => 'Form Tambah Personel',
            'status' => 'pending',
        ]);
    }

    public function test_superadmin_can_approve_all_pending_transfer_requests_at_once(): void
    {
        $first = $this->createInactiveSourcePersonnel('95050404');
        $second = $this->createInactiveSourcePersonnel('95050405');

        \App\Models\PersonnelTransferRequest::create([
            'personnel_id' => $first->id,
            'from_satker_id' => $this->sourceSatker->id,
            'to_satker_id' => $this->targetSatker->id,
            'requested_by' => $this->adminSatker->id,
            'source_file' => 'Data Lama',
            'source_row' => 1,
            'payload' => ['jabatan' => 'BA', 'bagian' => 'SAMAPTA'],
            'status' => 'pending',
        ]);
        \App\Models\PersonnelTransferRequest::create([
            'personnel_id' => $second->id,
            'from_satker_id' => $this->sourceSatker->id,
            'to_satker_id' => $this->targetSatker->id,
            'requested_by' => $this->adminSatker->id,
            'source_file' => 'Data Lama',
            'source_row' => 2,
            'payload' => ['jabatan' => 'BA', 'bagian' => 'LANTAS'],
            'status' => 'pending',
        ]);

        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('admin.personnel.transfer-requests.approve-all'))
            ->assertSessionHas('success', '2 pengajuan mutasi berhasil disetujui.');

        $this->assertSame($this->targetSatker->id, $first->fresh()->satker_id);
        $this->assertSame($this->targetSatker->id, $first->fresh()->user->satker_id);
        $this->assertTrue($first->fresh()->is_active);
        $this->assertSame($this->targetSatker->id, $second->fresh()->satker_id);
        $this->assertTrue($second->fresh()->is_active);
        $this->assertSame(2, \App\Models\PersonnelTransferRequest::where('status', 'approved')->count());
    }

    private function createInactiveSourcePersonnel(string $nrp): Personnel
    {
        $user = User::factory()->create([
            'nrp_nip' => $nrp,
            'name' => 'SHINTA KARTIKA DEWI, S.H.',
            'satker_id' => $this->sourceSatker->id,
            'is_active' => false,
        ]);
        $user->assignRole('personil');

        return Personnel::create([
            'user_id' => $user->id,
            'satker_id' => $this->sourceSatker->id,
            'nrp' => $nrp,
            'full_name' => 'SHINTA KARTIKA DEWI, S.H.',
            'rank_id' => $this->rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BA BAG SDM',
            'bagian' => 'SAT SAMAPTA',
            'gender' => 'P',
            'religion' => 'Islam',
            'keterangan' => 'STAF',
            'personnel_type' => 'Polri',
            'kapor_sizes' => ['topi' => '57'],
            'is_active' => false,
            'verification_status' => 'approved',
        ]);
    }
}
