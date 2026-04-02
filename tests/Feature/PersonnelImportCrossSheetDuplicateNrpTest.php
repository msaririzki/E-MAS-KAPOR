<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelImportCrossSheetDuplicateNrpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil', 'web');
    }

    public function test_cross_sheet_duplicate_nrps_are_marked_in_merged_preview(): void
    {
        $preview = PersonnelImport::markCrossSheetDuplicateNrps([
            [
                'row_num' => 11,
                'nrp' => '12345',
                'status' => 'ok',
                'duplicate_nrp' => false,
            ],
            [
                'row_num' => 21,
                'nrp' => '67890',
                'status' => 'ok',
                'duplicate_nrp' => false,
            ],
            [
                'row_num' => 11,
                'nrp' => '12345',
                'status' => 'ok',
                'duplicate_nrp' => false,
            ],
        ]);

        $this->assertTrue($preview[0]['duplicate_nrp']);
        $this->assertTrue($preview[2]['duplicate_nrp']);
        $this->assertSame('corrected', $preview[0]['status']);
        $this->assertSame('corrected', $preview[2]['status']);
    }

    public function test_save_from_preview_data_does_not_merge_hidden_batch_duplicates(): void
    {
        $satker = Satker::create([
            'name' => 'Dit Polairud',
            'code' => 'DIT-POLAIRUD',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $import = new PersonnelImport($satker->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '12345',
                'full_name' => 'PERSONEL SATU',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'JABATAN 1',
                'bagian' => 'OPSNAL',
                'golongan' => 'BINTARA',
                'keterangan' => '',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'status' => 'ok',
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
            [
                'nrp' => '12345',
                'full_name' => 'PERSONEL DUA',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'JABATAN 2',
                'bagian' => 'OPSNAL',
                'golongan' => 'BINTARA',
                'keterangan' => '',
                'keterangan_2' => '',
                'keterangan_3' => '',
                'keterangan_4' => '',
                'sizes' => [],
                'status' => 'ok',
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
        ], $satker->id);

        $this->assertSame(2, $result['success_count']);
        $this->assertSame(2, Personnel::count());
        $this->assertDatabaseHas('satkers', [
            'id' => $satker->id,
            'polri_count' => 2,
            'pns_count' => 0,
        ]);
    }
}
