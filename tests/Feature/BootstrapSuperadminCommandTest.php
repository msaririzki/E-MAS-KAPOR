<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BootstrapSuperadminCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin', 'web');
        Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
    }

    public function test_it_creates_bootstrap_superadmin_from_command(): void
    {
        Artisan::call('app:bootstrap-superadmin', [
            'email' => 'bootstrap@example.com',
            '--name' => 'Bootstrap Admin',
            '--password' => 'S7!qLm2#Vx9@Rt',
        ]);

        $user = User::where('email', 'bootstrap@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Bootstrap Admin', $user->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('superadmin'));
        $this->assertTrue(Hash::check('S7!qLm2#Vx9@Rt', $user->password));
    }

    public function test_only_if_missing_does_not_rotate_existing_password(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'bootstrap@example.com',
            'nrp_nip' => 'BOOTSTRAP-ABC123',
            'password' => Hash::make('OldPass1!XyZ#'),
            'satker_id' => Satker::where('code', 'POLDA-NTB')->value('id'),
            'is_active' => true,
        ]);
        $existingUser->assignRole('superadmin');

        Artisan::call('app:bootstrap-superadmin', [
            'email' => 'bootstrap@example.com',
            '--password' => 'NewPass9!AbCd#',
            '--only-if-missing' => true,
        ]);

        $existingUser->refresh();

        $this->assertTrue(Hash::check('OldPass1!XyZ#', $existingUser->password));
        $this->assertFalse(Hash::check('NewPass9!AbCd#', $existingUser->password));
    }
}
