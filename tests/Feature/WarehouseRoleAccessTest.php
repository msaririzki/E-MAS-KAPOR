<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WarehouseRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin_satker', 'admin_gudang', 'superadmin'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_superadmin_can_access_warehouse_page(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('superadmin');

        $response = $this->actingAs($user)->get(route('admin.warehouse-items.index'));

        $response->assertOk();
        $response->assertSeeText('Data Gudang');
    }

    public function test_admin_gudang_can_access_warehouse_page(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('admin_gudang');

        $response = $this->actingAs($user)->get(route('admin.warehouse-items.index'));

        $response->assertOk();
        $response->assertSeeText('Data Gudang');
    }

    public function test_admin_satker_cannot_access_warehouse_page(): void
    {
        $satker = Satker::create([
            'name' => 'Polresta Mataram',
            'code' => 'POLRESTA-MATARAM',
            'sort_order' => 2,
        ]);

        $user = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('admin_satker');

        $response = $this->actingAs($user)->get(route('admin.warehouse-items.index'));

        $response->assertForbidden();
    }

    public function test_admin_gudang_cannot_access_user_management_page(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('admin_gudang');

        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertForbidden();
    }
}
