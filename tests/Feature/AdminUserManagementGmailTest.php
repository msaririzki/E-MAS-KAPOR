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

        foreach (['superadmin', 'admin', 'admin_gudang', 'admin_satker', 'personil'] as $roleName) {
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
            'email' => 'admin.baru@gmail.com',
            'name' => 'Admin Baru',
            'phone' => '081234567890',
            'password' => 'password123',
            'role' => 'admin',
            'satker_id' => $satker->id,
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'admin.baru@gmail.com',
            'name' => 'Admin Baru',
            'nrp_nip' => null,
        ]);

        $createdUser = User::where('email', 'admin.baru@gmail.com')->firstOrFail();

        $this->assertTrue($createdUser->hasRole('admin'));
        $this->assertTrue($createdUser->usesEmailLogin());
    }
}
