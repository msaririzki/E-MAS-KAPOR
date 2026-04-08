<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelBulkDeleteAllTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_superadmin_can_bulk_delete_all_personnel_with_correct_confirmation()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $satker = Satker::create(['name' => 'Satker Test', 'code' => 'SATKER-TEST', 'sort_order' => 1]);
        $rank = Rank::create(['name' => 'Brigadir', 'sort_order' => 1, 'category' => 'BINTARA']);

        // Create some normal users which represent personnel
        $user1 = User::factory()->create();
        $user1->assignRole('personil');

        $user2 = User::factory()->create();
        $user2->assignRole('personil');

        Personnel::create([
            'user_id' => $user1->id,
            'satker_id' => $satker->id,
            'nrp' => '12345678',
            'full_name' => 'Test 1',
            'rank_id' => $rank->id,
            'personnel_type' => 'Polri',
            'gender' => 'L',
        ]);

        Personnel::create([
            'user_id' => $user2->id,
            'satker_id' => $satker->id,
            'nrp' => '87654321',
            'full_name' => 'Test 2',
            'rank_id' => $rank->id,
            'personnel_type' => 'Polri',
            'gender' => 'P',
        ]);

        $this->assertEquals(2, Personnel::count());
        $this->assertEquals(3, User::count()); // including superadmin

        $response = $this->actingAs($superadmin)->delete(route('admin.personnel.bulk-delete-all'), [
            'confirm_text' => 'KOSONGKAN',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(0, Personnel::count());
        // $user1 and $user2 should be deleted
        $this->assertDatabaseMissing('users', ['id' => $user1->id]);
        $this->assertDatabaseMissing('users', ['id' => $user2->id]);
        // Superadmin should still exist
        $this->assertDatabaseHas('users', ['id' => $superadmin->id]);
    }

    public function test_it_fails_when_confirmation_text_is_incorrect()
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $satker = Satker::create(['name' => 'Satker Test', 'code' => 'SATKER-TEST', 'sort_order' => 1]);
        $rank = Rank::create(['name' => 'Brigadir', 'sort_order' => 1, 'category' => 'BINTARA']);

        $user1 = User::factory()->create();
        $user1->assignRole('personil');

        Personnel::create([
            'user_id' => $user1->id,
            'satker_id' => $satker->id,
            'nrp' => '12345678',
            'full_name' => 'Test 1',
            'rank_id' => $rank->id,
            'personnel_type' => 'Polri',
            'gender' => 'L',
        ]);

        $this->assertEquals(1, Personnel::count());

        $response = $this->actingAs($superadmin)->delete(route('admin.personnel.bulk-delete-all'), [
            'confirm_text' => 'SALAH',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertEquals(1, Personnel::count());
    }
}
