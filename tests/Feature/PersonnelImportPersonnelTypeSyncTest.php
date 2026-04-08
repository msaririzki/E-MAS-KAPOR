<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Imports\PersonnelUpdateImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelImportPersonnelTypeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil', 'web');
    }

    public function test_import_updates_existing_personnel_type_when_rank_category_changes(): void
    {
        $satker = $this->createSatker();
        $rankPns = $this->createRank('Penata', 'PNS', 1);
        $rankPolri = $this->createRank('AIPDA', 'BINTARA', 2);

        $user = User::factory()->create([
            'nrp_nip' => '77010101',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '77010101',
            'full_name' => 'NAMA LAMA',
            'gender' => 'L',
            'personnel_type' => 'PNS',
            'rank_id' => $rankPns->id,
            'satker_id' => $satker->id,
            'golongan' => 'IIIA',
            'is_active' => true,
        ]);

        $import = new PersonnelImport($satker->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '77010101',
                'full_name' => 'NAMA BARU',
                'rank_id' => $rankPolri->id,
                'gender' => 'L',
                'jabatan' => 'BANIT',
                'bagian' => 'OPERASI',
                'golongan' => 'BINTARA',
                'keterangan' => '',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
        ], $satker->id);

        $this->assertSame(1, $result['success_count']);

        $personnel->refresh();
        $this->assertSame('Polri', $personnel->personnel_type);
        $this->assertSame($rankPolri->id, $personnel->rank_id);
    }

    public function test_update_import_sets_personnel_type_for_new_and_updated_rows(): void
    {
        $satker = $this->createSatker();
        $rankPns = $this->createRank('Penata', 'PNS', 1);
        $rankPolri = $this->createRank('BRIPKA', 'BINTARA', 2);

        $user = User::factory()->create([
            'nrp_nip' => '88020202',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $existing = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '88020202',
            'full_name' => 'PERSONEL UPDATE',
            'gender' => 'L',
            'personnel_type' => 'PNS',
            'rank_id' => $rankPns->id,
            'satker_id' => $satker->id,
            'golongan' => 'IIIA',
            'is_active' => true,
        ]);

        $import = new PersonnelUpdateImport($satker->id);
        $result = $import->saveUpdateFromPreview([
            [
                'row_num' => 11,
                'action' => 'update',
                'personnel_id' => $existing->id,
                'match_by' => 'nrp',
                'status' => 'update',
                'nrp' => '88020202',
                'full_name' => 'PERSONEL UPDATE',
                'rank_id' => $rankPolri->id,
                'golongan' => 'BINTARA',
                'jabatan' => 'BANIT',
                'bagian' => 'RENMIN',
                'gender' => 'L',
                'keterangan' => '',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
            [
                'row_num' => 12,
                'action' => 'new',
                'status' => 'new',
                'nrp' => '',
                'full_name' => 'PERSONEL BARU',
                'rank_id' => $rankPns->id,
                'golongan' => 'IIIA',
                'jabatan' => 'ANALIS',
                'bagian' => 'RENMIN',
                'gender' => 'P',
                'keterangan' => '',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
        ]);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(1, $result['new_count']);

        $existing->refresh();
        $this->assertSame('Polri', $existing->personnel_type);

        $this->assertDatabaseHas('personnels', [
            'full_name' => 'PERSONEL BARU',
            'satker_id' => $satker->id,
            'personnel_type' => 'PNS',
        ]);
    }

    private function createSatker(): Satker
    {
        return Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
    }

    private function createRank(string $name, string $category, int $sortOrder): Rank
    {
        return Rank::create([
            'name' => $name,
            'category' => $category,
            'sort_order' => $sortOrder,
        ]);
    }
}
