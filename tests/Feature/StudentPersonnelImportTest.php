<?php

namespace Tests\Feature;

use App\Exports\StudentPersonnelTemplateExport;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use App\Services\PersonnelItemAllocationSnapshotService;
use App\Services\StudentPersonnelImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentPersonnelImportTest extends TestCase
{
    use RefreshDatabase;

    private Satker $satker;

    private Rank $polriRank;

    private Rank $pnsRank;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
        Role::findOrCreate('admin');
        Setting::setValue('fiscal_year', '2026');
        Setting::setValue('is_system_locked', 'false');

        $this->satker = Satker::create([
            'name' => 'Polda NTB, SISWA, TA, BA, PAMA, PAMEN',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
        $this->polriRank = Rank::create([
            'name' => 'BRIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
        $this->pnsRank = Rank::create([
            'name' => 'Penata Muda',
            'category' => 'PNS',
            'sort_order' => 2,
        ]);
    }

    public function test_superadmin_can_download_complete_student_template(): void
    {
        $superadmin = $this->superadmin();

        $this->actingAs($superadmin)
            ->get(route('admin.personnel.student-template'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $path = $this->templatePath();
        $workbook = IOFactory::load($path);

        $this->assertSame(
            ['Data Siswa', 'Petunjuk', 'Referensi Pangkat', 'Referensi Ukuran'],
            $workbook->getSheetNames(),
        );
        $sheet = $workbook->getSheetByName('Data Siswa');
        $this->assertSame('NAMA', $sheet->getCell('B8')->getValue());
        $this->assertSame('AGAMA', $sheet->getCell('I8')->getValue());
        $this->assertSame('JILBAB', $sheet->getCell('V9')->getValue());
        $this->assertSame(1000, $sheet->getCell('A1010')->getValue());

        @unlink($path);
    }

    public function test_complete_excel_creates_polri_and_pns_students_without_login_accounts(): void
    {
        $path = $this->filledTemplate([
            11 => [
                'B' => 'I KADE SISWA POLRI',
                'C' => 'BRIPDA',
                'D' => 'BINTARA',
                'E' => '99000001',
                'F' => 'SISWA DIKTUKBA',
                'G' => 'PENDIDIKAN',
                'H' => 'P',
                'I' => 'Hindu',
                'J' => 'SISWA',
                'K' => 'GELOMBANG I',
                'N' => '57',
                'O' => '16',
                'P' => '34',
                'Q' => 'B',
                'R' => '41',
                'S' => '42',
                'T' => 'B',
                'U' => '42',
            ],
            12 => [
                'B' => 'NI LUH SISWA PNS',
                'C' => 'Penata Muda',
                'D' => '3',
                'E' => '199212082019021004',
                'F' => 'SISWA ADMINISTRASI',
                'G' => 'PENDIDIKAN',
                'H' => 'W',
                'I' => 'Hindu',
                'J' => 'SISWA PNS',
                'N' => '56',
                'O' => 'B',
                'P' => 'B',
                'Q' => 'B',
                'R' => '39',
                'S' => '39',
                'T' => 'B',
                'U' => '40',
                'V' => 'B',
            ],
        ]);

        $payload = app(StudentPersonnelImportService::class)->preview(
            $this->uploadedFile($path),
            $this->satker->id,
        );

        $this->assertSame(2, $payload['stats']['create']);
        $this->assertSame(0, $payload['stats']['error']);

        $result = app(StudentPersonnelImportService::class)->save(
            $payload['rows'],
            $this->satker->id,
            $this->superadmin()->id,
            'siswa-lengkap.xlsx',
        );

        $this->assertSame(2, $result['created']);
        $this->assertDatabaseCount('student_batches', 1);
        $this->assertDatabaseCount('personnels', 2);
        $this->assertDatabaseCount('users', 1);

        $polri = Personnel::where('nrp', '99000001')->firstOrFail();
        $pns = Personnel::where('nrp', '199212082019021004')->firstOrFail();

        $this->assertNull($polri->user_id);
        $this->assertNotNull($polri->student_batch_id);
        $this->assertSame('BINTARA', $polri->procurement_group);
        $this->assertSame('16', $polri->kapor_sizes['kemeja']);
        $this->assertSame('41', $polri->kapor_sizes['sepatu_dinas']);
        $this->assertSame('PNS', $pns->personnel_type);
        $this->assertSame('3', $pns->golongan);
        $this->assertSame('B', $pns->kapor_sizes['jilbab']);
        $this->assertDatabaseMissing('users', ['nrp_nip' => '99000001']);
        $this->assertDatabaseMissing('users', ['nrp_nip' => '199212082019021004']);

        @unlink($path);
    }

    public function test_route_flow_previews_and_confirms_student_import(): void
    {
        $superadmin = $this->superadmin();
        $path = $this->filledTemplate([
            11 => $this->validPolriRow('99000002', 'SISWA ROUTE UJI'),
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.personnel.student-import'), [
                'satker_id' => $this->satker->id,
                'file' => $this->uploadedFile($path),
            ])
            ->assertRedirect(route('admin.personnel.student-import-preview'));

        $this->actingAs($superadmin)
            ->get(route('admin.personnel.student-import-preview'))
            ->assertOk()
            ->assertSeeText('SISWA ROUTE UJI')
            ->assertSeeText('Data Baru');

        $this->actingAs($superadmin)
            ->post(route('admin.personnel.student-import-confirm'))
            ->assertRedirect(route('admin.personnel.index', ['satker_id' => $this->satker->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('personnels', [
            'nrp' => '99000002',
            'full_name' => 'SISWA ROUTE UJI',
            'user_id' => null,
            'satker_id' => $this->satker->id,
        ]);

        @unlink($path);
    }

    public function test_reupload_updates_student_but_never_creates_login(): void
    {
        $firstPath = $this->filledTemplate([
            11 => $this->validPolriRow('99000003', 'SISWA LAMA'),
        ]);
        $firstPayload = app(StudentPersonnelImportService::class)->preview($this->uploadedFile($firstPath), $this->satker->id);
        app(StudentPersonnelImportService::class)->save($firstPayload['rows'], $this->satker->id, null);

        $secondRow = $this->validPolriRow('99000003', 'SISWA DIPERBARUI');
        $secondRow['F'] = 'SISWA TITIPAN';
        $secondRow['N'] = '58';
        $secondPath = $this->filledTemplate([11 => $secondRow]);
        $secondPayload = app(StudentPersonnelImportService::class)->preview($this->uploadedFile($secondPath), $this->satker->id);

        $this->assertSame(1, $secondPayload['stats']['update']);
        app(StudentPersonnelImportService::class)->save($secondPayload['rows'], $this->satker->id, null);

        $student = Personnel::where('nrp', '99000003')->firstOrFail();
        $this->assertSame('SISWA DIPERBARUI', $student->full_name);
        $this->assertSame('SISWA TITIPAN', $student->jabatan);
        $this->assertSame('58', $student->kapor_sizes['topi']);
        $this->assertNull($student->user_id);
        $this->assertDatabaseMissing('users', ['nrp_nip' => '99000003']);

        @unlink($firstPath);
        @unlink($secondPath);
    }

    public function test_import_rejects_duplicate_nrp_and_existing_regular_personnel(): void
    {
        $regularUser = User::factory()->create(['nrp_nip' => '77000001']);
        Personnel::create([
            'user_id' => $regularUser->id,
            'nrp' => '77000001',
            'full_name' => 'PERSONEL ASLI',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $this->polriRank->id,
            'satker_id' => $this->satker->id,
            'jabatan' => 'ANGGOTA',
            'bagian' => 'OPERASIONAL',
            'is_active' => true,
        ]);

        $path = $this->filledTemplate([
            11 => $this->validPolriRow('77000001', 'MENCOBA TIMPA PERSONEL'),
            12 => $this->validPolriRow('99000004', 'DUPLIKAT SATU'),
            13 => $this->validPolriRow('99000004', 'DUPLIKAT DUA'),
        ]);
        $payload = app(StudentPersonnelImportService::class)->preview($this->uploadedFile($path), $this->satker->id);

        $this->assertSame(3, $payload['stats']['error']);
        $this->assertStringContainsString('personel biasa', implode(' ', $payload['rows'][0]['errors']));
        $this->assertStringContainsString('lebih dari sekali', implode(' ', $payload['rows'][1]['errors']));
        $this->assertSame('PERSONEL ASLI', Personnel::where('nrp', '77000001')->value('full_name'));

        @unlink($path);
    }

    public function test_imported_student_is_included_in_final_package_snapshot(): void
    {
        $path = $this->filledTemplate([
            11 => $this->validPolriRow('99000005', 'SISWA NOMINATIF'),
        ]);
        $payload = app(StudentPersonnelImportService::class)->preview($this->uploadedFile($path), $this->satker->id);
        app(StudentPersonnelImportService::class)->save($payload['rows'], $this->satker->id, null);
        $student = Personnel::where('nrp', '99000005')->firstOrFail();

        $budgetYear = BudgetYear::create(['year' => 2026, 'name' => 'T.A. 2026', 'is_active' => true]);
        $package = BudgetPackage::create(['budget_year_id' => $budgetYear->id, 'name' => 'PAKET SISWA', 'status' => 'finalized']);
        $kaporItem = KaporItem::create([
            'item_name' => 'PAKAIAN DINAS SISWA',
            'category' => 'Tutup_Badan',
            'price' => 100000,
            'unit' => 'STEL',
            'is_active' => true,
        ]);
        $packageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $kaporItem->id,
            'custom_price' => 100000,
        ]);
        PackageItemRecipient::create([
            'package_item_id' => $packageItem->id,
            'satker_id' => $this->satker->id,
            'recipient_filters' => ['personnel_type' => ['polri'], 'rank_categories' => ['BINTARA']],
            'matched_count' => 1,
        ]);

        app(PersonnelItemAllocationSnapshotService::class)->regenerateForBudgetPackage($package);

        $allocation = PersonnelItemAllocation::firstOrFail();
        $this->assertSame($student->id, $allocation->personnel_id);
        $this->assertNull($allocation->user_id);
        $this->assertSame('99000005', $allocation->nrp_snapshot);

        @unlink($path);
    }

    public function test_non_superadmin_cannot_access_student_import_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.personnel.student-template'))
            ->assertForbidden();
    }

    private function superadmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    private function templatePath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'student-template-').'.xlsx';
        file_put_contents($path, Excel::raw(new StudentPersonnelTemplateExport, ExcelFormat::XLSX));

        return $path;
    }

    private function filledTemplate(array $rows): string
    {
        $path = $this->templatePath();
        $workbook = IOFactory::load($path);
        $sheet = $workbook->getSheetByName('Data Siswa');

        foreach ($rows as $rowNumber => $columns) {
            foreach ($columns as $column => $value) {
                if ($column === 'E') {
                    $sheet->setCellValueExplicit($column.$rowNumber, (string) $value, DataType::TYPE_STRING);
                } else {
                    $sheet->setCellValue($column.$rowNumber, $value);
                }
            }
        }

        IOFactory::createWriter($workbook, 'Xlsx')->save($path);

        return $path;
    }

    private function uploadedFile(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            'siswa-lengkap.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    private function validPolriRow(string $nrp, string $name): array
    {
        return [
            'B' => $name,
            'C' => 'BRIPDA',
            'D' => 'BINTARA',
            'E' => $nrp,
            'F' => 'SISWA',
            'G' => 'PENDIDIKAN',
            'H' => 'P',
            'I' => 'Islam',
            'J' => 'SISWA',
            'N' => '57',
            'O' => '16',
            'P' => '34',
            'Q' => 'B',
            'R' => '41',
            'S' => '41',
            'T' => 'B',
            'U' => '42',
        ];
    }
}
