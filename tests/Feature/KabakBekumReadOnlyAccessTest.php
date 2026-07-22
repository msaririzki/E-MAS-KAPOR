<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KabakBekumReadOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'kabak_bekum', 'admin_gudang', 'admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_kabak_bekum_can_view_user_management_but_cannot_create_user(): void
    {
        $satker = $this->createPoldaSatker();
        $kabakBekum = User::factory()->create([
            'email' => 'kabak.bekum.kapor@gmail.com',
            'nrp_nip' => null,
            'satker_id' => $satker->id,
        ]);
        $kabakBekum->assignRole('kabak_bekum');

        $this->actingAs($kabakBekum)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeText('Manajemen Pengguna')
            ->assertDontSeeText('Tambah Akun');

        $this->actingAs($kabakBekum)
            ->post(route('admin.users.store'), [
                'email' => 'admin.baru@gmail.com',
                'name' => 'Admin Baru',
                'password' => 'Q7@vLp2#',
                'role' => 'admin_gudang',
                'satker_id' => $satker->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', [
            'email' => 'admin.baru@gmail.com',
        ]);
    }

    public function test_superadmin_can_create_kabak_bekum_account_from_user_management(): void
    {
        $satker = $this->createPoldaSatker();
        $superadmin = User::factory()->create([
            'email' => 'superadmin.kapor@gmail.com',
            'nrp_nip' => null,
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'email' => 'kabak.bekum.baru@gmail.com',
            'name' => 'Kabak Bekum',
            'phone' => '081234567890',
            'password' => 'Q7@vLp2#',
            'role' => 'kabak_bekum',
            'satker_id' => $satker->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $createdUser = User::where('email', 'kabak.bekum.baru@gmail.com')->firstOrFail();

        $this->assertTrue($createdUser->hasRole('kabak_bekum'));
        $this->assertTrue($createdUser->isReadOnlyAdmin());
    }

    private function createPoldaSatker(): Satker
    {
        return Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
    }
}
