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

    public function test_admin_satker_can_request_transfer_directly_without_creating_duplicate_personnel(): void
    {
        $personnel = $this->createInactiveSourcePersonnel('95050400');

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.request-transfer'), ['nrp' => '95050400'])
            ->assertRedirect(route('admin.personnel.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, Personnel::where('nrp', '95050400')->count());
        $this->assertSame($this->sourceSatker->id, $personnel->fresh()->satker_id);
        $this->assertFalse($personnel->fresh()->is_active);
        $this->assertFalse($personnel->user->fresh()->is_active);
        $this->assertDatabaseHas('personnel_transfer_requests', [
            'personnel_id' => $personnel->id,
            'from_satker_id' => $this->sourceSatker->id,
            'to_satker_id' => $this->targetSatker->id,
            'requested_by' => $this->adminSatker->id,
            'source_file' => 'Form Tambah Personel',
            'status' => 'pending',
        ]);
    }

    public function test_direct_transfer_request_cannot_be_submitted_twice(): void
    {
        $this->createInactiveSourcePersonnel('95050401');

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.request-transfer'), ['nrp' => '95050401']);

        $this->post(route('admin.personnel.request-transfer'), ['nrp' => '95050401'])
            ->assertSessionHas('info', 'Pengajuan mutasi personel ini sudah menunggu pemeriksaan superadmin.');

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
