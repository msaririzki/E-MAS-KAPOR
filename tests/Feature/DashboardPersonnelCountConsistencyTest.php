<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardPersonnelCountConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
    }

    public function test_dashboard_uses_real_personnel_counts_instead_of_stale_satker_totals(): void
    {
        $polda = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
            'polri_count' => 9000,
            'pns_count' => 500,
        ]);

        $ditlantas = Satker::create([
            'name' => 'Dit Lantas',
            'code' => 'DIT-LANTAS',
            'parent_id' => $polda->id,
            'sort_order' => 2,
            'polri_count' => 999,
            'pns_count' => 99,
        ]);

        $ditbinmas = Satker::create([
            'name' => 'Dit Binmas',
            'code' => 'DIT-BINMAS',
            'parent_id' => $polda->id,
            'sort_order' => 3,
            'polri_count' => 888,
            'pns_count' => 88,
        ]);

        $rank = Rank::create([
            'name' => 'AKP',
            'category' => 'PAMA',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $polda->id,
        ]);
        $superadmin->assignRole('superadmin');

        $this->createPersonnel($rank, $ditlantas, '1001', 'Polri');
        $this->createPersonnel($rank, $ditlantas, '1002', 'Polri');
        $this->createPersonnel($rank, $ditlantas, '2001', 'PNS');
        $this->createPersonnel($rank, $ditbinmas, '1003', 'Polri');

        $response = $this->actingAs($superadmin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            return $stats['total_polri'] === 3
                && $stats['total_pns'] === 1
                && $stats['total_personnel'] === 4;
        });
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
