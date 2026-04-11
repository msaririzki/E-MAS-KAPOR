<?php

namespace Tests\Feature;

use App\Exports\PersonnelSheetExport;
use App\Imports\PersonnelUpdateImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelSatkerTemplatePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin_satker');
        Role::findOrCreate('personil');
    }

    public function test_satker_export_template_only_contains_reference_fields_and_keterangan_satu(): void
    {
        $satker = Satker::create([
            'name' => 'Biro Logistik',
            'code' => 'ROLOG',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'IPDA',
            'category' => 'PAMA',
            'sort_order' => 1,
        ]);

        $personnel = Personnel::create([
            'satker_id' => $satker->id,
            'rank_id' => $rank->id,
            'full_name' => 'SOFIAN HADI',
            'nrp' => '78071191',
            'golongan' => 'PAMA',
            'jabatan' => 'PAMIN ROLOG',
            'bagian' => 'SUBBAGRENMIN',
            'gender' => 'L',
            'religion' => 'Islam',
            'keterangan' => 'STAF',
            'keterangan_2' => 'DALAM',
            'keterangan_3' => 'KOMANDO',
            'keterangan_4' => 'KHUSUS',
            'kapor_sizes' => ['kemeja' => '15', 'topi' => '57'],
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        $sheet = new PersonnelSheetExport(collect([$personnel->fresh('rank')]), $satker->name, 'Data Polri');
        $row = $sheet->collection()->first();

        $this->assertSame(10, count($row));
        $this->assertSame('SOFIAN HADI', $row[1]);
        $this->assertSame('Islam', $row[8]);
        $this->assertSame('STAF', $row[9]);

        $headers = $sheet->headings();
        $this->assertSame('AGAMA', $headers[7][8]);
        $this->assertSame('KETERANGAN', $headers[7][9]);
        $this->assertStringContainsString('TEMPLATE UPDATE JABATAN, BAG/FUNGSI, DAN KETERANGAN', $headers[5][0]);
    }

    public function test_satker_import_only_updates_jabatan_bagian_dan_keterangan(): void
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

        $user = User::create([
            'name' => 'EKO SUTOMO',
            'nrp_nip' => '82051489',
            'password' => bcrypt('82051489'),
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'satker_id' => $satker->id,
            'rank_id' => $rank->id,
            'full_name' => 'EKO SUTOMO',
            'nrp' => '82051489',
            'golongan' => 'PAMEN',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'gender' => 'L',
            'religion' => 'Islam',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'keterangan_3' => 'KET3 LAMA',
            'keterangan_4' => 'KET4 LAMA',
            'kapor_sizes' => ['topi' => '57'],
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        $import = new PersonnelUpdateImport($satker->id);
        $result = $import->saveUpdateFromPreview([
            [
                'row_num' => 11,
                'action' => 'update',
                'status' => 'update',
                'match_by' => 'nrp',
                'personnel_id' => $personnel->id,
                'full_name' => 'NAMA BARU TAPI HARUS DIABAIKAN',
                'rank_id' => $rank->id,
                'rank_name' => $rank->name,
                'golongan' => 'PATI',
                'nrp' => '82051489',
                'jabatan' => 'JABATAN BARU',
                'bagian' => 'BAGIAN BARU',
                'gender' => 'P',
                'religion' => 'Hindu',
                'keterangan' => 'KET BARU',
                'keterangan_2' => 'KET2 BARU',
                'keterangan_3' => 'KET3 BARU',
                'keterangan_4' => 'KET4 BARU',
                'sizes' => ['topi' => '60'],
                'duplicate_nrp' => false,
                'db_duplicate' => null,
            ],
        ]);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(0, $result['error_count']);

        $personnel->refresh();

        $this->assertSame('EKO SUTOMO', $personnel->full_name);
        $this->assertSame('PAMEN', $personnel->golongan);
        $this->assertSame('L', $personnel->gender);
        $this->assertSame('Islam', $personnel->religion);
        $this->assertSame('JABATAN BARU', $personnel->jabatan);
        $this->assertSame('BAGIAN BARU', $personnel->bagian);
        $this->assertSame('KET BARU', $personnel->keterangan);
        $this->assertSame('KET2 LAMA', $personnel->keterangan_2);
        $this->assertSame('KET3 LAMA', $personnel->keterangan_3);
        $this->assertSame('KET4 LAMA', $personnel->keterangan_4);
        $this->assertSame(['topi' => '57'], $personnel->kapor_sizes);
    }

    public function test_admin_satker_cannot_use_import_data_baru_route(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::create([
            'name' => 'Admin Satker',
            'email' => 'admin.satker@example.com',
            'password' => bcrypt('password'),
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $adminSatker->assignRole('admin_satker');

        $response = $this->actingAs($adminSatker)->post(route('admin.personnel.import'), [
            'satker_id' => $satker->id,
            'file' => UploadedFile::fake()->create('personel.xlsx'),
        ]);

        $response->assertForbidden();
    }

    public function test_satker_export_uses_latest_jabatan_and_bagian_filled_by_personil(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'password' => bcrypt('76100151'),
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'satker_id' => $satker->id,
            'rank_id' => $rank->id,
            'full_name' => 'EGAS DOSANTOS',
            'nrp' => '76100151',
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN AWAL',
            'bagian' => 'BAGIAN AWAL',
            'gender' => 'L',
            'religion' => 'Katolik',
            'kapor_sizes' => [],
            'personnel_type' => 'Polri',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('personil.kapor.store'), [
            'jabatan' => 'BANIT RESKRIM',
            'bagian' => 'SAT RESKRIM',
            'phone' => '08123456789',
            'kemeja' => '15',
            'celana' => '32',
            'olahraga' => 'B',
            'jaket' => 'B',
            'topi' => '57',
            'sabuk' => '42',
            'sepatu_dinas' => '41',
            'sepatu_olahraga' => '41',
        ]);

        $response->assertRedirect(route('dashboard'));

        $personnel->refresh();

        $sheet = new PersonnelSheetExport(collect([$personnel->fresh('rank')]), $satker->name, 'Data Polri');
        $row = $sheet->collection()->first();

        $this->assertSame('BANIT RESKRIM', $row[5]);
        $this->assertSame('SAT RESKRIM', $row[6]);
        $this->assertSame($satker->id, $personnel->satker_id);
        $this->assertSame('08123456789', $personnel->fresh()->phone);
        $this->assertSame('08123456789', $user->fresh()->phone);
    }
}
