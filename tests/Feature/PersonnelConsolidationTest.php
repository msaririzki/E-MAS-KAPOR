<?php

namespace Tests\Feature;

use App\Exports\PersonnelConsolidationExport;
use App\Models\Personnel;
use App\Models\PersonnelTransferRequest;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use App\Services\PersonnelConsolidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private Satker $satker;

    private Satker $otherSatker;

    private Rank $rank;

    private User $adminSatker;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'admin', 'admin_satker', 'personil'] as $role) {
            Role::findOrCreate($role);
        }

        $this->satker = Satker::create([
            'name' => 'Polresta Mataram',
            'code' => 'POLRESTA-MATARAM',
            'sort_order' => 1,
        ]);
        $this->otherSatker = Satker::create([
            'name' => 'Polres Lombok Barat',
            'code' => 'POLRES-LOBAR',
            'sort_order' => 2,
        ]);
        $this->rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 10,
        ]);
        $this->adminSatker = User::factory()->create([
            'satker_id' => $this->satker->id,
            'is_active' => true,
        ]);
        $this->adminSatker->assignRole('admin_satker');
    }

    public function test_export_uses_simple_personnel_columns_with_stable_system_code(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');

        $binary = Excel::raw(new PersonnelConsolidationExport($this->satker), ExcelFormat::XLSX);
        $path = storage_path('framework/testing/consolidation-export.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, $binary);

        try {
            $sheet = IOFactory::load($path)->getSheetByName('Data Polri');
            $this->assertSame('KETERANGAN', $sheet->getCell('J8')->getValue());
            $this->assertSame('KODE DATA (JANGAN DIUBAH)', $sheet->getCell('K8')->getValue());
            $this->assertSame($personnel->sync_token, $sheet->getCell('K9')->getValue());
            $this->assertSame('90010001', $sheet->getCell('E9')->getValue());
            $this->assertSame('K', $sheet->getHighestColumn());
            $this->assertNotNull($sheet->getComment('K8')->getText()->getPlainText());
            $this->assertSame('A8:K9', $sheet->getAutoFilter()->getRange());
            $this->assertFalse((bool) $sheet->getProtection()->getSheet());
            $this->assertNotNull(IOFactory::load($path)->getSheetByName('Petunjuk'));
        } finally {
            @unlink($path);
        }
    }

    public function test_round_trip_simple_export_omits_sizes_without_false_updates(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $personnel->update(['kapor_sizes' => ['topi' => '57', 'kemeja' => '15.5']]);
        $binary = Excel::raw(new PersonnelConsolidationExport($this->satker), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'kapor-roundtrip-').'.xlsx';
        file_put_contents($path, $binary);

        try {
            $preview = app(PersonnelConsolidationService::class)
                ->buildPreview($path, $this->satker, 'roundtrip.xlsx');
        } finally {
            @unlink($path);
        }

        $this->assertSame(0, $preview['stats']['update']);
        $this->assertSame(1, $preview['stats']['no_change']);
        $this->assertFalse($preview['rows'][0]['data']['has_sizes']);
        $this->assertSame('', $preview['rows'][0]['data']['sizes']['kemeja']);
        $this->assertSame('15.5', $personnel->fresh()->kapor_sizes['kemeja']);
    }

    public function test_simple_export_update_preserves_existing_sizes(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $personnel->update([
            'keterangan' => 'STAF LAMA',
            'kapor_sizes' => ['topi' => '57', 'kemeja' => '15.5'],
        ]);
        $binary = Excel::raw(new PersonnelConsolidationExport($this->satker), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'kapor-simple-update-').'.xlsx';
        file_put_contents($path, $binary);

        try {
            $spreadsheet = IOFactory::load($path);
            $spreadsheet->getSheetByName('Data Polri')->setCellValue('J9', 'STAF BARU');
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
            $preview = app(PersonnelConsolidationService::class)
                ->buildPreview($path, $this->satker, 'simple-update.xlsx');
        } finally {
            @unlink($path);
        }

        $this->assertSame(1, $preview['stats']['update']);
        app(PersonnelConsolidationService::class)->applyPreview($preview, [], $this->adminSatker);

        $personnel->refresh();
        $this->assertSame('STAF BARU', $personnel->keterangan);
        $this->assertSame('57', $personnel->kapor_sizes['topi']);
        $this->assertSame('15.5', $personnel->kapor_sizes['kemeja']);
    }

    public function test_round_trip_export_preserves_long_nip_as_text(): void
    {
        $pnsRank = Rank::create([
            'name' => 'Penata',
            'category' => 'PNS',
            'sort_order' => 20,
        ]);
        $personnel = $this->createPersonnel(
            $this->satker,
            '197004041992032005',
            'NI NYOMAN SANTRINI',
        );
        $personnel->update([
            'rank_id' => $pnsRank->id,
            'personnel_type' => 'PNS',
            'gender' => 'P',
        ]);

        $binary = Excel::raw(new PersonnelConsolidationExport($this->satker), ExcelFormat::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'kapor-long-nip-').'.xlsx';
        file_put_contents($path, $binary);

        try {
            $preview = app(PersonnelConsolidationService::class)
                ->buildPreview($path, $this->satker, 'long-nip.xlsx');
        } finally {
            @unlink($path);
        }

        $this->assertSame(0, $preview['stats']['error']);
        $this->assertSame(1, $preview['stats']['no_change']);
        $this->assertSame('197004041992032005', $preview['rows'][0]['nrp']);
    }

    public function test_missing_system_codes_are_filled_before_download(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $personnel->updateQuietly(['sync_token' => null]);

        app(PersonnelConsolidationService::class)->ensureSyncTokens($this->satker->id);

        $this->assertNotNull($personnel->fresh()->sync_token);
    }

    public function test_admin_satker_sees_only_simple_download_and_upload_entry_points(): void
    {
        $response = $this->actingAs($this->adminSatker)
            ->get(route('admin.personnel.index'));

        $response->assertOk();
        $response->assertSee('Unduh Data Personel');
        $response->assertSee('Unggah Pembaruan Data');
        $response->assertSee(route('admin.personnel.consolidation.download'), false);
        $response->assertSee(route('admin.personnel.consolidation.import'), false);
        $response->assertDontSee('Konsolidasi Personel Satker');
        $response->assertDontSee(route('admin.personnel.import-update'), false);
    }

    public function test_reordered_legacy_file_without_system_code_updates_by_nrp(): void
    {
        $first = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $second = $this->createPersonnel($this->satker, '90010002', 'PERSONEL DUA');
        $file = $this->makeWorkbook([
            $this->legacyRow($second, bagian: 'SAT RESKRIM BARU'),
            $this->legacyRow($first, bagian: 'SAT LANTAS BARU'),
        ], false);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), [
                'satker_id' => $this->satker->id,
                'file' => $file,
            ])
            ->assertRedirect(route('admin.personnel.consolidation.preview'))
            ->assertSessionHas('personnel_consolidation_preview.stats.update', 2);

        $this->post(route('admin.personnel.consolidation.confirm'))
            ->assertRedirect(route('admin.personnel.index'));

        $this->assertSame('SAT LANTAS BARU', $first->fresh()->bagian);
        $this->assertSame('SAT RESKRIM BARU', $second->fresh()->bagian);
    }

    public function test_identical_copied_row_is_ignored_without_creating_duplicate(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $row = $this->fullRow($personnel);
        $file = $this->makeWorkbook([$row, $row], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), [
                'file' => $file,
            ])
            ->assertSessionHas('personnel_consolidation_preview.stats.duplicate_ignored', 1)
            ->assertSessionHas('personnel_consolidation_preview.stats.error', 0);

        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertSame(1, Personnel::where('nrp', '90010001')->count());
    }

    public function test_duplicate_nrp_with_different_content_is_blocked_but_other_rows_can_continue(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $safe = $this->createPersonnel($this->satker, '90010002', 'PERSONEL AMAN');
        $firstDuplicate = $this->fullRow($personnel);
        $secondDuplicate = $this->fullRow($personnel);
        $secondDuplicate[6] = 'SAT RESKRIM KONFLIK';
        $safeRow = $this->fullRow($safe);
        $safeRow[6] = 'SAT LANTAS BARU';
        $file = $this->makeWorkbook([$firstDuplicate, $secondDuplicate, $safeRow], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.error', 2)
            ->assertSessionHas('personnel_consolidation_preview.stats.update', 1);

        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertSame('BAGIAN LAMA', $personnel->fresh()->bagian);
        $this->assertSame('SAT LANTAS BARU', $safe->fresh()->bagian);
        $this->assertSame(1, Personnel::where('nrp', '90010001')->count());
    }

    public function test_cross_satker_nrp_becomes_pending_transfer_without_duplicate_account(): void
    {
        $personnel = $this->createPersonnel($this->otherSatker, '90010009', 'PERSONEL MUTASI');
        $row = $this->fullRow($personnel);
        $row[6] = 'SAT RESKRIM';
        $file = $this->makeWorkbook([$row], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.transfer', 1);

        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertSame($this->otherSatker->id, $personnel->fresh()->satker_id);
        $this->assertSame(1, Personnel::where('nrp', '90010009')->count());
        $this->assertDatabaseHas('personnel_transfer_requests', [
            'personnel_id' => $personnel->id,
            'from_satker_id' => $this->otherSatker->id,
            'to_satker_id' => $this->satker->id,
            'status' => 'pending',
        ]);
    }

    public function test_missing_personnel_stays_active_unless_explicitly_selected(): void
    {
        $included = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $missing = $this->createPersonnel($this->satker, '90010002', 'PERSONEL HILANG');
        $file = $this->makeWorkbook([$this->fullRow($included)], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.missing', 1);
        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertTrue($missing->fresh()->is_active);
        $this->assertTrue($missing->user->fresh()->is_active);

        $file = $this->makeWorkbook([$this->fullRow($included)], true);
        $this->post(route('admin.personnel.consolidation.import'), ['file' => $file]);
        $this->post(route('admin.personnel.consolidation.confirm'), [
            'deactivate_ids' => [$missing->id],
            'confirm_deactivation' => '1',
        ]);

        $this->assertFalse($missing->fresh()->is_active);
        $this->assertFalse($missing->user->fresh()->is_active);
    }

    public function test_system_code_allows_safe_nrp_correction_and_updates_same_login(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $row = $this->fullRow($personnel);
        $row[4] = '90010077';
        $file = $this->makeWorkbook([$row], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.update', 1);
        $this->post(route('admin.personnel.consolidation.confirm'));

        $personnel->refresh();
        $this->assertSame('90010077', $personnel->nrp);
        $this->assertSame('90010077', $personnel->user->nrp_nip);
        $this->assertSame(1, Personnel::where('nrp', '90010077')->count());
    }

    public function test_superadmin_can_bulk_approve_pending_transfer_and_move_same_personnel(): void
    {
        $personnel = $this->createPersonnel($this->otherSatker, '90010009', 'PERSONEL MUTASI');
        $transfer = PersonnelTransferRequest::create([
            'personnel_id' => $personnel->id,
            'from_satker_id' => $this->otherSatker->id,
            'to_satker_id' => $this->satker->id,
            'requested_by' => $this->adminSatker->id,
            'payload' => [
                'full_name' => $personnel->full_name,
                'nrp' => $personnel->nrp,
                'rank_id' => $this->rank->id,
                'rank_name' => $this->rank->name,
                'golongan' => 'BINTARA',
                'jabatan' => 'BANIT BARU',
                'bagian' => 'SAT RESKRIM',
                'gender' => 'L',
                'religion' => 'Islam',
                'keterangan' => 'STAF',
                'personnel_type' => 'Polri',
                'sizes' => ['topi' => '58'],
                'has_sizes' => true,
                'system_code' => $personnel->sync_token,
            ],
            'status' => 'pending',
        ]);
        $superadmin = User::factory()->create(['is_active' => true]);
        $superadmin->assignRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('admin.personnel.transfer-requests.review'), [
                'request_ids' => [$transfer->id],
                'action' => 'approve',
            ])
            ->assertSessionHas('success');

        $personnel->refresh();
        $this->assertSame($this->satker->id, $personnel->satker_id);
        $this->assertSame($this->satker->id, $personnel->user->satker_id);
        $this->assertSame('SAT RESKRIM', $personnel->bagian);
        $this->assertSame('approved', $transfer->fresh()->status);
        $this->assertSame(1, Personnel::where('nrp', '90010009')->count());
    }

    public function test_old_file_cannot_silently_change_nrp_when_system_code_is_missing(): void
    {
        $personnel = $this->createPersonnel($this->satker, '90010001', 'PERSONEL SATU');
        $row = $this->legacyRow($personnel);
        $row[4] = '90019999';
        $file = $this->makeWorkbook([$row], false);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.error', 1);
        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertSame('90010001', $personnel->fresh()->nrp);
        $this->assertDatabaseMissing('users', ['nrp_nip' => '90019999']);
    }

    public function test_scientific_notation_nip_is_rejected_instead_of_creating_wrong_account(): void
    {
        $row = $this->newFullRow('1.99212082019021E+17', 'PNS FORMAT RUSAK');
        $file = $this->makeWorkbook([$row], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.error', 1);
        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertDatabaseMissing('personnels', ['full_name' => 'PNS FORMAT RUSAK']);
    }

    public function test_inactive_unassigned_account_is_reused_for_new_personnel(): void
    {
        $oldAccount = User::factory()->create([
            'name' => 'AKUN TAHUN LALU',
            'nrp_nip' => '90018888',
            'satker_id' => null,
            'is_active' => false,
        ]);
        $oldAccount->assignRole('personil');
        $file = $this->makeWorkbook([
            $this->newFullRow('90018888', 'PERSONEL AKTIF KEMBALI'),
        ], true);

        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.new', 1);
        $this->post(route('admin.personnel.consolidation.confirm'));

        $personnel = Personnel::where('nrp', '90018888')->firstOrFail();
        $this->assertSame($oldAccount->id, $personnel->user_id);
        $this->assertTrue($oldAccount->fresh()->is_active);
        $this->assertSame($this->satker->id, $oldAccount->fresh()->satker_id);
    }

    public function test_large_mixed_file_keeps_safe_rows_moving_while_isolating_conflicts(): void
    {
        $rows = [];
        $targetPersonnel = collect();
        for ($index = 1; $index <= 60; $index++) {
            $personnel = $this->createPersonnel(
                $this->satker,
                '91'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'PERSONEL TARGET '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
            );
            $targetPersonnel->push($personnel);
            $row = $this->fullRow($personnel);
            if ($index <= 20) {
                $row[6] = 'BAGIAN BARU '.$index;
            }
            $rows[] = $row;
        }

        for ($index = 0; $index < 5; $index++) {
            $rows[] = $rows[$index];
        }
        for ($index = 30; $index < 33; $index++) {
            $conflict = $rows[$index];
            $conflict[6] = 'BAGIAN KONFLIK';
            $rows[] = $conflict;
        }
        for ($index = 1; $index <= 10; $index++) {
            $rows[] = $this->newFullRow(
                '92'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'PERSONEL BARU '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            );
        }
        for ($index = 1; $index <= 5; $index++) {
            $other = $this->createPersonnel(
                $this->otherSatker,
                '93'.str_pad((string) $index, 6, '0', STR_PAD_LEFT),
                'PERSONEL MUTASI '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            );
            $rows[] = $this->fullRow($other);
        }

        $file = $this->makeWorkbook($rows, true);
        $this->actingAs($this->adminSatker)
            ->post(route('admin.personnel.consolidation.import'), ['file' => $file])
            ->assertSessionHas('personnel_consolidation_preview.stats.update', 20)
            ->assertSessionHas('personnel_consolidation_preview.stats.new', 10)
            ->assertSessionHas('personnel_consolidation_preview.stats.transfer', 5)
            ->assertSessionHas('personnel_consolidation_preview.stats.duplicate_ignored', 5)
            ->assertSessionHas('personnel_consolidation_preview.stats.error', 6)
            ->assertSessionHas('personnel_consolidation_preview.stats.missing', 0);

        $this->post(route('admin.personnel.consolidation.confirm'));

        $this->assertSame('BAGIAN BARU 1', $targetPersonnel->first()->fresh()->bagian);
        $this->assertSame('BAGIAN LAMA', $targetPersonnel[30]->fresh()->bagian);
        $this->assertSame(10, Personnel::where('full_name', 'like', 'PERSONEL BARU %')->count());
        $this->assertSame(5, PersonnelTransferRequest::where('status', 'pending')->count());
    }

    private function createPersonnel(Satker $satker, string $nrp, string $name): Personnel
    {
        $user = User::factory()->create([
            'name' => $name,
            'nrp_nip' => $nrp,
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);
        $user->assignRole('personil');

        return Personnel::create([
            'user_id' => $user->id,
            'satker_id' => $satker->id,
            'rank_id' => $this->rank->id,
            'nrp' => $nrp,
            'full_name' => $name,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT',
            'bagian' => 'BAGIAN LAMA',
            'gender' => 'L',
            'religion' => 'Islam',
            'keterangan' => 'STAF',
            'personnel_type' => 'Polri',
            'kapor_sizes' => ['topi' => '57'],
            'is_active' => true,
            'verification_status' => 'approved',
        ]);
    }

    private function legacyRow(Personnel $personnel, ?string $bagian = null): array
    {
        return [
            1,
            $personnel->full_name,
            $personnel->rank->name,
            $personnel->golongan,
            $personnel->nrp,
            $personnel->jabatan,
            $bagian ?? $personnel->bagian,
            'P',
            $personnel->religion,
            $personnel->keterangan,
        ];
    }

    private function fullRow(Personnel $personnel): array
    {
        $sizes = $personnel->kapor_sizes ?? [];

        return [
            1,
            $personnel->full_name,
            $personnel->rank->name,
            $personnel->golongan,
            $personnel->nrp,
            $personnel->jabatan,
            $personnel->bagian,
            $personnel->gender === 'P' ? 'W' : 'P',
            $personnel->religion,
            $sizes['topi'] ?? '',
            $sizes['kemeja'] ?? '',
            $sizes['celana'] ?? '',
            $sizes['olahraga'] ?? '',
            $sizes['sepatu_dinas'] ?? '',
            $sizes['sepatu_olahraga'] ?? '',
            $sizes['jaket'] ?? '',
            $sizes['sabuk'] ?? '',
            $sizes['jilbab'] ?? '',
            $personnel->keterangan,
            $personnel->sync_token,
        ];
    }

    private function newFullRow(string $nrp, string $name): array
    {
        return [
            1,
            $name,
            $this->rank->name,
            'BINTARA',
            $nrp,
            'BANIT',
            'BAGIAN BARU',
            'P',
            'Islam',
            '57',
            '15',
            '32',
            'B',
            '41',
            '41',
            'B',
            '42',
            '',
            'STAF',
            '',
        ];
    }

    private function makeWorkbook(array $rows, bool $full): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $headers = $full
            ? ['NO', 'NAMA', 'PANGKAT', 'GOLONGAN', 'NRP/NIP', 'JABATAN', 'BAG/FUNGSI', 'JENIS KELAMIN P/W', 'AGAMA', 'TUTUP KEPALA', 'KEMEJA', 'CELANA/ROK', 'T.SHIRT/OLAHRAGA', 'SEPATU DINAS', 'SEPATU OLAHRAGA', 'JAKET', 'SABUK', 'JILBAB', 'KETERANGAN', 'KODE DATA (JANGAN DIUBAH)']
            : ['NO', 'NAMA', 'PANGKAT', 'GOLONGAN', 'NRP/NIP', 'JABATAN', 'BAG/FUNGSI', 'JENIS KELAMIN P/W', 'AGAMA', 'KETERANGAN'];

        $sheet->fromArray($headers, null, 'A8');
        $sheet->fromArray($rows, null, 'A9');

        $path = tempnam(sys_get_temp_dir(), 'kapor-consolidation-').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile(
            $path,
            'konsolidasi-personel.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }
}
