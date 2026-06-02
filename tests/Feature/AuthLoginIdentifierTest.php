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

        foreach (['superadmin', 'admin_gudang', 'admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_superadmin_can_login_with_gmail(): void
    {
        $satker = $this->createSatker();
        $user = User::factory()->create([
            'email' => 'superadmin.kapor@gmail.com',
            'nrp_nip' => null,
            'password' => Hash::make('password123'),
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('superadmin');

        $response = $this->post(route('login'), [
            'login' => 'superadmin.kapor@gmail.com',
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
            'password' => Hash::make('198501012010011001'),
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $response = $this->post(route('login'), [
            'login' => '198501012010011001',
            'password' => '198501012010011001',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_personil_login_does_not_rehash_existing_import_password(): void
    {
        config(['hashing.bcrypt.rounds' => 12]);

        $satker = $this->createSatker();
        $passwordHash = password_hash('198501012010011002', PASSWORD_BCRYPT, ['cost' => User::IMPORT_PASSWORD_ROUNDS]);
        $user = User::factory()->create([
            'email' => null,
            'nrp_nip' => '198501012010011002',
            'password' => $passwordHash,
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $response = $this->post(route('login'), [
            'login' => '198501012010011002',
            'password' => '198501012010011002',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($passwordHash, $user->fresh()->password);
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $satker = $this->createSatker();
        $user = User::factory()->create([
            'email' => 'superadmin.locked@gmail.com',
            'nrp_nip' => null,
            'password' => Hash::make('StrongPass123!'),
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('superadmin');

        foreach (range(1, 5) as $attempt) {
            $response = $this->from(route('login'))->post(route('login'), [
                'login' => 'superadmin.locked@gmail.com',
                'password' => 'wrong-password',
            ]);

            $response->assertRedirect(route('login'));
            $response->assertSessionHasErrors('login');
        }

        $response = $this->from(route('login'))->post(route('login'), [
            'login' => 'superadmin.locked@gmail.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('login');
        $this->assertStringStartsWith(
            'Terlalu banyak percobaan login. Coba lagi dalam ',
            session('errors')->first('login')
        );
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
