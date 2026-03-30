<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelImportSaveFromPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil');
    }

    public function test_save_from_preview_data_can_persist_keterangan_fields(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AKBP',
            'category' => 'PAMEN',
            'sort_order' => 1,
        ]);

        $import = new PersonnelImport($satker->id);

        $result = $import->saveFromPreviewData([
            [
                'nrp' => '82051489',
                'full_name' => 'EKO SUTOMO',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'KAPOLRES BIMA',
                'bagian' => 'KAPOLRES',
                'golongan' => 'PAMEN',
                'keterangan' => ' STAF ',
                'keterangan_2' => ' ',
                'keterangan_3' => null,
                'keterangan_4' => 'KOMANDO',
                'sizes' => [],
                'status' => 'ok',
                'duplicate_nrp' => false,
                'db_duplicate' => null,
                'personnel_type' => 'Polri',
            ],
        ], $satker->id);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(0, $result['error_count']);

        $this->assertDatabaseHas('personnels', [
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'keterangan' => 'STAF',
            'keterangan_2' => null,
            'keterangan_3' => null,
            'keterangan_4' => 'KOMANDO',
            'satker_id' => $satker->id,
        ]);

        $personnel = Personnel::where('nrp', '82051489')->firstOrFail();

        $this->assertSame($satker->id, $personnel->user->satker_id);
        $this->assertTrue($personnel->user->hasRole('personil'));
    }
}
