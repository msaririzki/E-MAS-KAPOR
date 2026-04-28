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

    public function test_superadmin_can_bulk_create_admin_satker_accounts_and_skip_existing_satkers(): void
    {
        $existingSatker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $newSatker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 2,
        ]);

        $superadmin = User::factory()->create([
            'email' => 'superadmin.kapor@gmail.com',
            'nrp_nip' => null,
            'satker_id' => $existingSatker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $existingAdminSatker = User::factory()->create([
            'name' => 'POLDA NTB - ADMIN SATKER',
            'email' => 'admin.satker.polda.ntb@gmail.com',
            'nrp_nip' => null,
            'satker_id' => $existingSatker->id,
        ]);
        $existingAdminSatker->assignRole('admin_satker');

        $response = $this->actingAs($superadmin)->post(route('admin.users.bulk-admin-satker'));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('bulk_admin_satker_credentials');
        $response->assertSessionHas('bulk_admin_satker_skipped', [$existingSatker->name]);

        $createdUser = User::query()
            ->where('satker_id', $newSatker->id)
            ->where('name', 'POLRES BIMA - ADMIN SATKER')
            ->first();

        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasRole('admin_satker'));
        $this->assertTrue($createdUser->usesEmailLogin());
        $this->assertNotNull($createdUser->email);
        $this->assertStringEndsWith('@gmail.com', $createdUser->email);
        $this->assertMatchesRegularExpression('/Polres/i', $response->getSession()->get('bulk_admin_satker_credentials')[0]['password']);

        $this->assertSame(2, User::role('admin_satker')->count());
    }
}
