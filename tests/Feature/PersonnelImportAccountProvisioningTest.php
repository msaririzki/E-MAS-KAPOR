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
        $this->assertSame(User::IMPORT_PASSWORD_ROUNDS, password_get_info($user->password)['options']['cost'] ?? null);
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

    public function test_import_preserves_existing_user_phone_on_personnel_record(): void
    {
        $satker = $this->createSatker();
        $rank = $this->createRank();

        $user = User::create([
            'name' => 'Lalu Muhamad Zainul',
            'nrp_nip' => '198501012010011001',
            'phone' => '08123456789',
            'password' => bcrypt('198501012010011001'),
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '198501012010011001',
            'full_name' => 'Lalu Muhamad Zainul',
            'rank_id' => $rank->id,
            'satker_id' => $satker->id,
            'phone' => null,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'jabatan' => 'Ba Urmin',
            'bagian' => 'Logistik',
            'is_active' => true,
        ]);

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
        $this->assertSame('08123456789', $personnel->fresh()->phone);
        $this->assertSame('08123456789', $user->fresh()->phone);
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
