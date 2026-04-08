<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelImportDuplicateNrpPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil', 'web');
    }

    public function test_import_keeps_existing_personnel_and_creates_new_row_when_nrp_already_exists_in_database(): void
    {
        $satkerAsal = Satker::create([
            'name' => 'Polres Sumbawa',
            'code' => 'POLRES-SUMBAWA',
            'sort_order' => 1,
        ]);

        $satkerTujuan = Satker::create([
            'name' => 'Polres Bima Kota',
            'code' => 'POLRES-BIMA-KOTA',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $existingUser = User::factory()->create([
            'nrp_nip' => '85090525',
            'satker_id' => $satkerAsal->id,
        ]);
        $existingUser->assignRole('personil');

        $existingPersonnel = Personnel::create([
            'user_id' => $existingUser->id,
            'nrp' => '85090525',
            'full_name' => 'DODI LAMA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'satker_id' => $satkerAsal->id,
            'is_active' => true,
        ]);

        PersonnelImport::recalculateSatkerCount($satkerAsal->id);

        $import = new PersonnelImport($satkerTujuan->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '85090525',
                'full_name' => 'DODI RAHMAN',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'BANIT SAMAPTA',
                'bagian' => 'SUBSEKTOR RABA',
                'golongan' => 'BINTARA',
                'keterangan' => 'STAF',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'status' => 'corrected',
                'duplicate_nrp' => false,
                'db_duplicate' => [
                    'personnel_id' => $existingPersonnel->id,
                    'full_name' => $existingPersonnel->full_name,
                    'satker_name' => $satkerAsal->name,
                    'satker_id' => $satkerAsal->id,
                    'same_satker' => false,
                ],
            ],
        ], $satkerTujuan->id);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(0, $result['error_count']);

        $this->assertDatabaseHas('personnels', [
            'id' => $existingPersonnel->id,
            'satker_id' => $satkerAsal->id,
            'full_name' => 'DODI LAMA',
            'nrp' => '85090525',
        ]);

        $this->assertSame(2, Personnel::where('nrp', '85090525')->count());
        $this->assertDatabaseHas('personnels', [
            'satker_id' => $satkerTujuan->id,
            'full_name' => 'DODI RAHMAN',
            'nrp' => '85090525',
            'user_id' => null,
        ]);

        $this->assertDatabaseHas('satkers', [
            'id' => $satkerAsal->id,
            'polri_count' => 1,
            'pns_count' => 0,
        ]);
        $this->assertDatabaseHas('satkers', [
            'id' => $satkerTujuan->id,
            'polri_count' => 1,
            'pns_count' => 0,
        ]);
    }
}
