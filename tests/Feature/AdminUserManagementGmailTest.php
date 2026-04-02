<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserManagementGmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin_gudang', 'admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_superadmin_can_create_admin_account_with_gmail_identifier(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'email' => 'superadmin.kapor@gmail.com',
            'nrp_nip' => null,
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->post(route('admin.users.store'), [
            'email' => 'admin.gudang.baru@gmail.com',
            'name' => 'Admin Gudang Baru',
            'phone' => '081234567890',
            'password' => 'Q7@vLp2#',
            'role' => 'admin_gudang',
            'satker_id' => $satker->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'admin.gudang.baru@gmail.com',
            'name' => 'Admin Gudang Baru',
            'nrp_nip' => null,
        ]);

        $createdUser = User::where('email', 'admin.gudang.baru@gmail.com')->firstOrFail();

        $this->assertTrue($createdUser->hasRole('admin_gudang'));
        $this->assertTrue($createdUser->usesEmailLogin());
    }
}
