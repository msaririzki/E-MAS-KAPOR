<?php

namespace Tests\Feature;

use App\Imports\PersonnelSdmImport;
use App\Jobs\ProcessSdmImportPreview;
use App\Models\Personnel;
use App\Models\Satker;
use App\Models\SdmImportRun;
use App\Models\User;
use Database\Seeders\RankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ImportSdmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['queue.default' => 'sync']);

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

    public function test_sdm_import_preserves_manual_field_owned_by_operational_workflow_for_existing_personnel(): void
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

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satbrimob->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => \App\Models\Rank::where('name', 'AIPDA')->value('id'),
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN MANUAL SATKER',
            'bagian' => 'BAGIAN MANUAL',
            'keterangan' => 'KET MANUAL',
            'satker_id' => $satbrimob->id,
            'religion' => 'Katolik',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57', 'kemeja' => '15'],
        ]);

        $import = new PersonnelSdmImport;
        $preview = $import->generatePreview(collect([
            [1, 'EGAS DOSANTOS', 'AIPDA', "'76100151", 'BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB', 'PRIA', 'KATOLIK'],
        ]), 'Worksheet');

        $result = $import->saveFromPreviewData($preview);

        $this->assertSame(1, $result['success_count']);
        $this->assertSame(0, $result['error_count']);

        $personnel->refresh();

        $this->assertSame('JABATAN MANUAL SATKER', $personnel->jabatan);
        $this->assertSame('BAGIAN MANUAL', $personnel->bagian);
        $this->assertSame('KET MANUAL', $personnel->keterangan);
        $this->assertSame(['topi' => '57', 'kemeja' => '15'], $personnel->kapor_sizes);
        $this->assertSame('Katolik', $personnel->religion);
    }

    public function test_sdm_import_creates_persistent_run_log_and_error_report(): void
    {
        Storage::fake('local');

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

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $csv = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
            "2,NAMA ERROR,AIPDA,'76100152,JABATAN TANPA SATKER,PRIA,ISLAM",
        ]);

        $file = UploadedFile::fake()->createWithContent('sdm_import.csv', $csv);

        $response = $this->actingAs($superAdmin)->post(route('admin.personnel.import-sdm'), [
            'files' => [$file],
        ]);

        $response->assertRedirect(route('admin.personnel.import-sdm-preview'));

        $run = SdmImportRun::firstOrFail();
        $this->assertSame('preview_ready', $run->status);
        $this->assertSame(2, $run->summary['total']);
        $this->assertSame(1, $run->summary['error']);
        $this->assertSame(1, $run->summary['unresolved_satker_count']);

        Storage::disk('local')->assertExists($run->preview_payload_path);
        Storage::disk('local')->assertExists($run->error_report_path);
    }

    public function test_sdm_import_confirm_can_continue_after_manual_preview_correction(): void
    {
        Storage::fake('local');

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

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $csv = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
            "2,NAMA ERROR,AIPDA,'76100152,JABATAN TANPA SATKER,PRIA,ISLAM",
        ]);

        $file = UploadedFile::fake()->createWithContent('sdm_import.csv', $csv);

        $this->actingAs($superAdmin)->post(route('admin.personnel.import-sdm'), [
            'files' => [$file],
        ])->assertRedirect(route('admin.personnel.import-sdm-preview'));

        $run = SdmImportRun::firstOrFail();

        $this->actingAs($superAdmin)->post(route('admin.personnel.import-sdm-confirm'), [
            'satker_overrides' => [
                1 => $satbrimob->id,
            ],
        ])
            ->assertRedirect(route('admin.personnel.index'));

        $run->refresh();

        $this->assertSame('completed', $run->status);
        $this->assertSame(2, $run->summary['success_count']);
        $this->assertSame(0, $run->summary['error_count']);
        $this->assertNull($run->error_report_path);
    }

    public function test_sdm_import_confirm_shows_failed_name_when_runtime_conflict_occurs(): void
    {
        Storage::fake('local');

        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $satker = Satker::create([
            'name' => 'BIRO LOGISTIK',
            'code' => 'BIRO-LOG',
            'sort_order' => 2,
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $importedUser = User::factory()->create([
            'name' => 'JEFTHA ANDHARA WONGSO, S.H.',
            'nrp_nip' => '99110739',
            'satker_id' => $satker->id,
        ]);
        $importedUser->assignRole('personil');

        Personnel::create([
            'user_id' => $importedUser->id,
            'nrp' => '77770001',
            'full_name' => 'PEMILIK USER KONFLIK',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => \App\Models\Rank::where('name', 'AIPDA')->value('id'),
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT TEST',
            'bagian' => null,
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $import = new PersonnelSdmImport;
        $preview = $import->generatePreview(collect([
            [1, 'JEFTHA ANDHARA WONGSO, S.H.', 'AIPDA', "'99110739", 'BAMIN ROLOG POLDA NTB', 'PRIA', 'ISLAM'],
        ]), 'Sheet 1');

        $this->assertSame('ok', $preview[0]['status']);

        $run = SdmImportRun::create([
            'initiated_by' => $superAdmin->id,
            'status' => 'preview_ready',
            'processing_mode' => 'sync',
            'source_files' => [],
            'summary' => ['error' => 0, 'ok' => 1, 'total' => 1],
            'preview_payload_path' => 'import-previews/sdm/run-1.json',
            'started_at' => now(),
        ]);

        Storage::disk('local')->put($run->preview_payload_path, json_encode([
            'preview' => $preview,
            'stats' => ['error' => 0, 'ok' => 1, 'total' => 1],
        ], JSON_UNESCAPED_UNICODE));

        $response = $this->actingAs($superAdmin)
            ->withSession([
                'sdm_import_run_id' => $run->id,
                'sdm_import_preview_key' => $run->preview_payload_path,
                'sdm_import_stats' => ['error' => 0, 'ok' => 1, 'total' => 1],
            ])
            ->post(route('admin.personnel.import-sdm-confirm'));

        $response->assertRedirect(route('admin.personnel.index'));
        $response->assertSessionHas('warning');

        $run->refresh();
        $this->assertSame('completed_with_errors', $run->status);
        $this->assertSame(['JEFTHA ANDHARA WONGSO, S.H.'], $run->summary['failed_names']);
    }

    public function test_sdm_import_can_queue_preview_processing_when_queue_driver_is_async(): void
    {
        Storage::fake('local');
        Queue::fake();
        config(['queue.default' => 'database']);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $csv = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
        ]);

        $file = UploadedFile::fake()->createWithContent('sdm_import.csv', $csv);

        $response = $this->actingAs($superAdmin)
            ->withHeaders(['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])
            ->post(route('admin.personnel.import-sdm'), [
                'files' => [$file],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'status_url', 'run_id']);

        $run = SdmImportRun::firstOrFail();
        $this->assertSame('queued', $run->status);

        Queue::assertPushed(ProcessSdmImportPreview::class, function (ProcessSdmImportPreview $job) use ($run) {
            return $job->runId === $run->id;
        });
    }

    public function test_sdm_preview_marks_cross_file_duplicate_nrps_as_errors(): void
    {
        Storage::fake('local');

        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        Satker::create([
            'name' => 'SATBRIMOB',
            'code' => 'SATBRIMOB',
            'sort_order' => 2,
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $csvOne = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
        ]);
        $csvTwo = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.personnel.import-sdm'), [
            'files' => [
                UploadedFile::fake()->createWithContent('sdm_satu.csv', $csvOne),
                UploadedFile::fake()->createWithContent('sdm_dua.csv', $csvTwo),
            ],
        ]);

        $response->assertRedirect(route('admin.personnel.import-sdm-preview'));

        $run = SdmImportRun::firstOrFail();
        $run->refresh();

        $this->assertSame('preview_ready', $run->status);
        $this->assertSame(2, $run->summary['total']);
        $this->assertSame(2, $run->summary['error']);
        $this->assertSame(2, $run->summary['duplicate_count']);
        $this->assertSame(1, $run->summary['duplicate_group_count']);
        $this->assertSame(1, $run->summary['unique_personnel_estimate']);

        $payload = json_decode(Storage::disk('local')->get($run->preview_payload_path), true, 512, JSON_THROW_ON_ERROR);
        $preview = $payload['preview'] ?? [];

        $this->assertCount(2, $preview);
        $this->assertTrue($preview[0]['duplicate_nrp']);
        $this->assertTrue($preview[1]['duplicate_nrp']);
        $this->assertSame('error', $preview[0]['status']);
        $this->assertSame('error', $preview[1]['status']);
    }

    public function test_sdm_confirm_is_blocked_when_preview_contains_duplicate_nrp_errors(): void
    {
        Storage::fake('local');

        Satker::create([
            'name' => 'POLDA NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        Satker::create([
            'name' => 'SATBRIMOB',
            'code' => 'SATBRIMOB',
            'sort_order' => 2,
        ]);

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('superadmin');

        $csvOne = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
        ]);
        $csvTwo = implode("\n", [
            'HEADER 1',
            'HEADER 2',
            "1,EGAS DOSANTOS,AIPDA,'76100151,BANIT II SUBDEN IV DENGEGANA SATBRIMOB POLDA NTB,PRIA,KATOLIK",
        ]);

        $this->actingAs($superAdmin)->post(route('admin.personnel.import-sdm'), [
            'files' => [
                UploadedFile::fake()->createWithContent('sdm_satu.csv', $csvOne),
                UploadedFile::fake()->createWithContent('sdm_dua.csv', $csvTwo),
            ],
        ])->assertRedirect(route('admin.personnel.import-sdm-preview'));

        $response = $this->actingAs($superAdmin)->post(route('admin.personnel.import-sdm-confirm'));

        $response->assertRedirect(route('admin.personnel.import-sdm-preview'));
        $response->assertSessionHas('error');
        $this->assertSame(0, Personnel::count());

        $run = SdmImportRun::firstOrFail();
        $run->refresh();

        $this->assertSame('preview_ready', $run->status);
        $this->assertSame(2, $run->summary['error']);
    }
}
