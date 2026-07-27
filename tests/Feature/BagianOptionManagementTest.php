<?php

namespace Tests\Feature;

use App\Models\BagianOption;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\BagianOptionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BagianOptionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_bagian_option_seeder_populates_initial_master_data(): void
    {
        $this->seed(BagianOptionSeeder::class);

        $this->assertDatabaseHas('bagian_options', [
            'name' => 'SAT RESKRIM',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('bagian_options', [
            'name' => 'BAG SDM',
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_manage_bagian_options_from_settings_page(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $createResponse = $this->actingAs($superadmin)->post(route('superadmin.bagian-options.store'), [
            'name' => 'sat reskrim',
        ]);

        $createResponse->assertRedirect(route('superadmin.settings.index'));
        $this->assertDatabaseHas('bagian_options', [
            'name' => 'SAT RESKRIM',
            'is_active' => true,
        ]);

        $option = BagianOption::where('name', 'SAT RESKRIM')->firstOrFail();

        $updateResponse = $this->actingAs($superadmin)->put(route('superadmin.bagian-options.update', $option), [
            'name' => 'SAT RESKRIM KHUSUS',
        ]);

        $updateResponse->assertRedirect(route('superadmin.settings.index'));
        $this->assertDatabaseHas('bagian_options', [
            'id' => $option->id,
            'name' => 'SAT RESKRIM KHUSUS',
            'is_active' => false,
        ]);

        $deleteResponse = $this->actingAs($superadmin)->delete(route('superadmin.bagian-options.destroy', $option));

        $deleteResponse->assertRedirect(route('superadmin.settings.index'));
        $this->assertDatabaseMissing('bagian_options', [
            'id' => $option->id,
        ]);
    }

    public function test_personnel_index_uses_master_bagian_options(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $admin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $admin->assignRole('admin');

        BagianOption::create([
            'name' => 'SAT RESKRIM',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        BagianOption::create([
            'name' => 'OPS NONAKTIF',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.personnel.index'));

        $response->assertOk();
        $response->assertSee('SAT RESKRIM');
        $response->assertDontSee('OPS NONAKTIF');
    }

    public function test_personnel_index_honors_supported_page_size_and_renders_working_options(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES LOMBOK TENGAH',
            'code' => 'POLRES-LOTENG',
            'sort_order' => 1,
        ]);
        $rank = Rank::create([
            'name' => 'BRIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
        $admin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $admin->assignRole('admin');

        Personnel::create([
            'nrp' => '99000001',
            'full_name' => 'PERSONEL UJI PAGINASI',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.personnel.index', [
            'per_page' => 100,
        ]));

        $response->assertOk();
        $response->assertViewHas('perPage', 100);
        $response->assertViewHas('personnels', fn ($personnels) => $personnels->perPage() === 100);
        $response->assertSee('per_page=100', false);
        $response->assertSee('window.spaNavigate(this.href)', false);
    }
}
