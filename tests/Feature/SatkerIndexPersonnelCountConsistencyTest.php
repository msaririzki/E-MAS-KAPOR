<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SatkerIndexPersonnelCountConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
    }

    public function test_satker_index_displays_and_syncs_counts_from_personnel_table(): void
    {
        $polda = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
            'polri_count' => 7000,
            'pns_count' => 300,
        ]);

        $ditlantas = Satker::create([
            'name' => 'Dit Lantas',
            'code' => 'DIT-LANTAS',
            'parent_id' => $polda->id,
            'sort_order' => 2,
            'polri_count' => 999,
            'pns_count' => 99,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $polda->id,
        ]);
        $superadmin->assignRole('superadmin');

        $this->createPersonnel($rank, $ditlantas, '3001', 'Polri');
        $this->createPersonnel($rank, $ditlantas, '3002', 'Polri');
        $this->createPersonnel($rank, $ditlantas, '4001', 'PNS');

        $response = $this->actingAs($superadmin)->get(route('superadmin.satkers.index'));

        $response->assertOk();
        $response->assertViewHas('satkers', function ($satkers) use ($ditlantas): bool {
            $satker = $satkers->firstWhere('id', $ditlantas->id);

            return $satker !== null
                && $satker->display_polri_count === 2
                && $satker->display_pns_count === 1
                && $satker->display_total_personnel === 3;
        });

        $this->assertDatabaseHas('satkers', [
            'id' => $ditlantas->id,
            'polri_count' => 2,
            'pns_count' => 1,
        ]);
    }

    private function createPersonnel(Rank $rank, Satker $satker, string $nrp, string $personnelType): void
    {
        $user = User::factory()->create([
            'nrp_nip' => $nrp,
            'satker_id' => $satker->id,
        ]);

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => $nrp,
            'full_name' => 'Personel '.$nrp,
            'gender' => 'L',
            'personnel_type' => $personnelType,
            'rank_id' => $rank->id,
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
    }
}
