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

class PersonnelImportSatkerScopedLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil', 'web');
    }

    public function test_regular_import_does_not_update_existing_personnel_from_other_satker(): void
    {
        $satkerAsal = Satker::create([
            'name' => 'Polres Sumbawa',
            'code' => 'POLRES-SUMBAWA',
            'sort_order' => 1,
        ]);

        $satkerTujuan = Satker::create([
            'name' => 'Dit Polairud',
            'code' => 'DIT-POLAIRUD',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'nrp_nip' => '85081669',
            'satker_id' => $satkerAsal->id,
        ]);
        $user->assignRole('personil');

        $existing = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '85081669',
            'full_name' => 'I NYOMAN NATA UTAMA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'satker_id' => $satkerAsal->id,
            'is_active' => true,
        ]);

        $import = new PersonnelImport($satkerTujuan->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '85081669',
                'full_name' => 'I NYOMAN NATA UTAMA',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'P.S PANIT 2 SI BINMAS AIR',
                'bagian' => 'OPSNAL',
                'golongan' => 'BINTARA',
                'keterangan' => 'STAF',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'status' => 'ok',
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
        ], $satkerTujuan->id);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(0, $result['error_count']);

        $existing->refresh();
        $this->assertSame($satkerAsal->id, $existing->satker_id);

        $this->assertDatabaseHas('personnels', [
            'satker_id' => $satkerTujuan->id,
            'nrp' => '85081669',
            'full_name' => 'I NYOMAN NATA UTAMA',
        ]);
    }
}
