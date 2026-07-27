<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelKeteranganImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }

        Storage::fake('local');
    }

    public function test_superadmin_can_preview_and_confirm_keterangan_import_without_overwriting_reference_fields(): void
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

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personnelUser = User::factory()->create([
            'name' => 'EKO SUTOMO',
            'nrp_nip' => '82051489',
            'satker_id' => $satker->id,
        ]);
        $personnelUser->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $personnelUser->id,
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'PAMEN',
            'jabatan' => 'KAPOLRES BIMA',
            'bagian' => 'KAPOLRES',
            'keterangan' => 'STAF',
            'keterangan_2' => 'LAMA 2',
            'keterangan_3' => 'LAMA 3',
            'keterangan_4' => null,
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $csv = implode("\n", [
            'id,no,nama,nrp_nip,satker,pangkat,golongan,jenis_kelamin,agama,jabatan,bag_fungsi,keterangan_1,keterangan_2,keterangan_3,keterangan_4',
            $personnel->id.',1,EKO SUTOMO,82051489,Polres Bima,AKBP,PAMEN,L,Islam,JABATAN FILE BERUBAH,BAG FILE BERUBAH,REFERENSI BERUBAH,BARU 2,,BARU 4',
            '999999,2,UNKNOWN,0000,Satker X,AKBP,PAMEN,L,Islam,JABATAN,,REFERENSI,X,Y,Z',
        ]);

        $file = UploadedFile::fake()->createWithContent('import_keterangan.csv', $csv);

        $previewResponse = $this->actingAs($superadmin)->post(route('admin.personnel.import-keterangan'), [
            'file' => $file,
        ]);

        $previewResponse->assertRedirect(route('admin.personnel.import-keterangan-preview'));
        $previewResponse->assertSessionHas('keterangan_import_stats', function (array $stats) {
            return $stats['update'] === 1 && $stats['error'] === 1 && $stats['total'] === 2;
        });
        $previewResponse->assertSessionHas('keterangan_import_preview_key');
        $previewResponse->assertSessionMissing('keterangan_import_preview');

        $previewPath = session('keterangan_import_preview_key');
        $this->assertIsString($previewPath);
        Storage::disk('local')->assertExists($previewPath);

        $this->actingAs($superadmin)
            ->get(route('admin.personnel.import-keterangan-preview'))
            ->assertOk()
            ->assertSeeText('Pratinjau Unggah Keterangan Referensi');

        $confirmResponse = $this->actingAs($superadmin)->post(route('admin.personnel.import-keterangan-confirm'));

        $confirmResponse->assertRedirect(route('admin.personnel.index'));
        $confirmResponse->assertSessionHas('warning');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'jabatan' => 'KAPOLRES BIMA',
            'bagian' => 'KAPOLRES',
            'keterangan' => 'STAF',
            'keterangan_2' => 'BARU 2',
            'keterangan_3' => null,
            'keterangan_4' => 'BARU 4',
        ]);

        Storage::disk('local')->assertMissing($previewPath);
    }

    public function test_non_superadmin_cannot_access_keterangan_import(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $admin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $admin->assignRole('admin');

        $file = UploadedFile::fake()->createWithContent('import_keterangan.csv', 'id,keterangan_2,keterangan_3,keterangan_4');

        $this->actingAs($admin)
            ->post(route('admin.personnel.import-keterangan'), ['file' => $file])
            ->assertForbidden();
    }

    public function test_preview_tidak_memberi_warning_nrp_jika_file_memakai_notasi_ilmiah_excel(): void
    {
        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'PPPK',
            'category' => 'PNS',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personnelUser = User::factory()->create([
            'name' => 'ADI PRATOYO',
            'nrp_nip' => '198112302025211005',
            'satker_id' => $satker->id,
        ]);
        $personnelUser->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $personnelUser->id,
            'nrp' => '198112302025211005',
            'full_name' => 'ADI PRATOYO',
            'gender' => 'L',
            'personnel_type' => 'PNS',
            'rank_id' => $rank->id,
            'golongan' => 'PNS',
            'jabatan' => 'PPPK POLDA NTB',
            'bagian' => null,
            'keterangan' => null,
            'keterangan_2' => null,
            'keterangan_3' => null,
            'keterangan_4' => null,
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $csv = implode("\n", [
            'id,no,nama,nrp_nip,satker,tipe_personel,pangkat,golongan,jenis_kelamin,agama,jabatan,bag_fungsi,keterangan_1,keterangan_2,keterangan_3,keterangan_4',
            $personnel->id.',1,ADI PRATOYO,1.98112302025211E+17,Polda NTB,PNS,PPPK,PNS,L,Islam,PPPK POLDA NTB,,,DALAM,,',
        ]);

        $file = UploadedFile::fake()->createWithContent('import_keterangan_scientific.csv', $csv);

        $this->actingAs($superadmin)->post(route('admin.personnel.import-keterangan'), [
            'file' => $file,
        ])->assertRedirect(route('admin.personnel.import-keterangan-preview'));

        $previewPath = session('keterangan_import_preview_key');
        $this->assertIsString($previewPath);

        $payload = json_decode(Storage::disk('local')->get($previewPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame([], $payload['preview'][0]['reference_warnings']);
        $this->assertSame('update', $payload['preview'][0]['status']);
    }
}
