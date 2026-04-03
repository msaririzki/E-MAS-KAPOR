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

class PersonnelImportUserConflictFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil', 'web');
    }

    public function test_import_still_creates_personnel_when_matching_login_is_already_bound_to_another_personnel(): void
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
            'nrp_nip' => '70121097',
            'satker_id' => $satkerAsal->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => 'DIFFERENT-NRP',
            'full_name' => 'PERSONEL LAMA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'satker_id' => $satkerAsal->id,
            'is_active' => true,
        ]);

        $import = new PersonnelImport($satkerTujuan->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '70121097',
                'full_name' => 'LALU PUNIA ASMARA',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'PS. KASI HARKAN',
                'bagian' => 'FASHARKAN',
                'golongan' => 'PAMA',
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

        $this->assertDatabaseHas('personnels', [
            'full_name' => 'LALU PUNIA ASMARA',
            'satker_id' => $satkerTujuan->id,
            'nrp' => '70121097',
            'user_id' => null,
        ]);
    }
}
