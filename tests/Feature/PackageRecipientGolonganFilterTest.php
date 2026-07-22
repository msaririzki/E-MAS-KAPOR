<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use App\Services\KaporRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PackageRecipientGolonganFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_and_recipient_filters_use_numeric_pns_golongan(): void
    {
        $satker = Satker::create([
            'name' => 'Satker Uji',
            'code' => 'SATKER-UJI',
            'sort_order' => 1,
        ]);

        $normalizedPersonnel = $this->createPersonnel($satker, 'PNS Normal', 'III/a');
        $legacyPersonnel = $this->createPersonnel($satker, 'PNS Lama', '2');
        $this->createPersonnel($satker, 'PNS Golongan Empat', '4');

        $this->assertSame('3', $normalizedPersonnel->fresh()->golongan);

        DB::table('personnels')
            ->where('id', $legacyPersonnel->id)
            ->update(['golongan' => 'III/A']);

        $service = app(KaporRequirementService::class);

        $numericFilter = Personnel::query()->where('satker_id', $satker->id);
        $service->applyRecipientFilters($numericFilter, ['golongan' => ['3']], $satker);
        $this->assertSame(2, $numericFilter->count());

        $legacyFilter = Personnel::query()->where('satker_id', $satker->id);
        $service->applyRecipientFilters($legacyFilter, ['rank_categories' => ['GOLONGAN III']], $satker);
        $this->assertSame(2, $legacyFilter->count());
    }

    public function test_non_pns_rank_category_is_not_changed(): void
    {
        $personnel = new Personnel;
        $personnel->golongan = 'BINTARA';

        $this->assertSame('BINTARA', $personnel->golongan);
    }

    public function test_polri_rank_category_filter_remains_unchanged(): void
    {
        $satker = Satker::create([
            'name' => 'Satker Polri',
            'code' => 'SATKER-POLRI',
            'sort_order' => 1,
        ]);
        $rank = Rank::create([
            'name' => 'KOMPOL Uji',
            'category' => 'PAMEN',
            'sort_order' => 1,
        ]);
        $user = User::factory()->create(['satker_id' => $satker->id]);

        Personnel::create([
            'user_id' => $user->id,
            'full_name' => 'Personel Polri',
            'nrp' => $user->nrp_nip,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'PAMEN',
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);

        $query = Personnel::query()->where('satker_id', $satker->id);
        app(KaporRequirementService::class)->applyRecipientFilters(
            $query,
            ['rank_categories' => ['PAMEN']],
            $satker,
        );

        $this->assertSame(1, $query->count());
    }

    private function createPersonnel(Satker $satker, string $name, string $golongan): Personnel
    {
        $user = User::factory()->create(['satker_id' => $satker->id]);

        return Personnel::create([
            'user_id' => $user->id,
            'full_name' => $name,
            'nrp' => $user->nrp_nip,
            'gender' => 'L',
            'personnel_type' => 'PNS',
            'golongan' => $golongan,
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
    }
}
