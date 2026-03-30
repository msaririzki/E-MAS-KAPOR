<?php

namespace Tests\Feature;

use App\Imports\PersonnelSdmImport;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\User;
use Database\Seeders\RankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportSdmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RankSeeder::class);

        Role::findOrCreate('superadmin');
        Role::findOrCreate('admin_satker');
        Role::findOrCreate('personil');
    }

    public function test_superadmin_can_access_sdm_import_route_without_satker_selection(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $file = UploadedFile::fake()->create('sdm_data.xlsx', 100);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.personnel.import-sdm'), [
                'files' => [$file],
            ]);

        $response->assertStatus(302);
    }

    public function test_non_superadmin_cannot_access_sdm_import(): void
    {
        $adminSatker = User::factory()->create();
        $adminSatker->assignRole('admin_satker');

        $file = UploadedFile::fake()->create('sdm_data.xlsx', 100);

        $response = $this->actingAs($adminSatker)
            ->post(route('admin.personnel.import-sdm'), [
                'files' => [$file],
            ]);

        $response->assertSessionHas('error');
    }

    public function test_sdm_import_can_resolve_satker_from_jabatan_and_persist_baseline_data(): void
    {
        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $satbrimob = Satker::create([
            'name' => 'SATBRIMOB',
            'code' => 'SATBRIMOB',
            'sort_order' => 2,
        ]);

        $import = new PersonnelSdmImport;

        $preview = $import->generatePreview(collect([
            [1, 'EGAS DOSANTOS', 'AIPDA', "'76100151", 'BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB', 'PRIA', 'KATOLIK'],
        ]), 'Worksheet');

        $this->assertCount(1, $preview);
        $this->assertSame('SATBRIMOB', $preview[0]['satker_name']);
        $this->assertSame($satbrimob->id, $preview[0]['satker_id']);
        $this->assertSame('L', $preview[0]['gender']);
        $this->assertSame('Katolik', $preview[0]['religion']);
        $this->assertSame('AIPDA', $preview[0]['rank_name']);
        $this->assertSame('BINTARA', $preview[0]['golongan']);

        $result = $import->saveFromPreviewData($preview);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(0, $result['error_count']);

        $this->assertDatabaseHas('personnels', [
            'full_name' => 'EGAS DOSANTOS',
            'nrp' => '76100151',
            'satker_id' => $satbrimob->id,
            'jabatan' => 'BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB',
            'gender' => 'L',
            'religion' => 'Katolik',
            'golongan' => 'BINTARA',
            'personnel_type' => 'Polri',
        ]);

        $personnel = Personnel::where('full_name', 'EGAS DOSANTOS')->firstOrFail();
        $this->assertSame($satbrimob->id, $personnel->user->satker_id);
        $this->assertTrue($personnel->user->hasRole('personil'));
    }
}
