<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelImportAccountProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('personil', 'web');
    }

    public function test_import_creates_personil_account_with_nrp_as_login_and_password(): void
    {
        $satker = $this->createSatker();
        $rank = $this->createRank();

        $import = new PersonnelImport($satker->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '198501012010011001',
                'full_name' => 'Lalu Muhamad Zainul',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'Ba Urmin',
                'bagian' => 'Logistik',
                'golongan' => '',
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
        $this->assertSame(0, $result['error_count']);

        $user = User::where('nrp_nip', '198501012010011001')->firstOrFail();
        $personnel = Personnel::where('nrp', '198501012010011001')->firstOrFail();

        $this->assertSame($user->id, $personnel->user_id);
        $this->assertTrue($user->hasRole('personil'));
        $this->assertTrue(Hash::check('198501012010011001', $user->password));
    }

    public function test_import_skips_login_account_creation_when_nrp_is_missing(): void
    {
        $satker = $this->createSatker();
        $rank = $this->createRank();

        $import = new PersonnelImport($satker->id);
        $result = $import->saveFromPreviewData([
            [
                'nrp' => '',
                'full_name' => 'Ahmad Fauzi',
                'rank_id' => $rank->id,
                'gender' => 'L',
                'jabatan' => 'Ba Sium',
                'bagian' => 'Renmin',
                'golongan' => '',
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
        $this->assertSame(0, $result['error_count']);
        $this->assertDatabaseCount('users', 0);

        $personnel = Personnel::where('full_name', 'Ahmad Fauzi')->firstOrFail();

        $this->assertNull($personnel->user_id);
        $this->assertNull($personnel->nrp);
    }

    private function createSatker(): Satker
    {
        return Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
    }

    private function createRank(): Rank
    {
        return Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
    }
}
