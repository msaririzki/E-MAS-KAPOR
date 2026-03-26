<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthLoginIdentifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'admin_gudang', 'admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_admin_can_login_with_gmail(): void
    {
        $satker = $this->createSatker();
        $user = User::factory()->create([
            'email' => 'admin.kapor@gmail.com',
            'nrp_nip' => null,
            'password' => Hash::make('password123'),
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('admin');

        $response = $this->post(route('login'), [
            'login' => 'admin.kapor@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_with_registered_gmail_cannot_login_using_nrp_nip(): void
    {
        $satker = $this->createSatker();
        $user = User::factory()->create([
            'email' => 'admin.satker@gmail.com',
            'nrp_nip' => 'ADM001',
            'password' => Hash::make('password123'),
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('admin_satker');

        $response = $this->from(route('login'))->post(route('login'), [
            'login' => 'ADM001',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_personil_can_login_with_nrp_nip(): void
    {
        $satker = $this->createSatker();
        $user = User::factory()->create([
            'email' => null,
            'nrp_nip' => '198501012010011001',
            'password' => Hash::make('password123'),
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $response = $this->post(route('login'), [
            'login' => '198501012010011001',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    private function createSatker(): Satker
    {
        return Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
    }
}
