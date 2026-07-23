<?php

namespace Tests\Feature;

use App\Exports\StudentBatchExport;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PackageItemRecipient;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\StudentBatch;
use App\Models\User;
use App\Services\BudgetPackageSppmAssignmentService;
use App\Services\PersonnelItemAllocationSnapshotService;
use App\Services\StudentBatchImportService;
use App\Services\StudentBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentBatchManagementTest extends TestCase
{
    use RefreshDatabase;

    private Rank $studentRank;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
        Satker::create([
            'name' => 'Polda NTB, SISWA, TA, BA, PAMA, PAMEN',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
        $this->studentRank = Rank::create([
            'name' => 'BA PTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
    }

    public function test_superadmin_can_generate_non_login_student_batch(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->post(route('admin.students.store'), [
            'name' => 'Siswa Diktukba Gelombang I',
            'fiscal_year' => 2027,
            'procurement_group' => 'BINTARA',
            'default_rank_id' => $this->studentRank->id,
            'default_jabatan' => 'SISWA DIKTUKBA',
            'default_bagian' => 'SISWA',
            'male_count' => 2,
            'female_count' => 1,
            'notes' => 'Data awal pengadaan.',
        ]);

        $batch = StudentBatch::firstOrFail();
        $response->assertRedirect(route('admin.students.show', $batch));
        $this->assertSame(3, $batch->students()->count());
        $this->assertSame(3, $batch->students()->whereNull('user_id')->count());
        $this->assertSame(3, $batch->students()->distinct()->count('student_code'));
        $this->assertSame(2, $batch->students()->where('gender', 'L')->count());
        $this->assertSame(1, $batch->students()->where('gender', 'P')->count());
        $this->assertDatabaseHas('personnels', [
            'student_batch_id' => $batch->id,
            'personnel_type' => 'Polri',
            'procurement_group' => 'BINTARA',
            'rank_id' => $this->studentRank->id,
            'jabatan' => 'SISWA DIKTUKBA',
            'bagian' => 'SISWA',
            'is_active' => true,
        ]);
    }

    public function test_student_excel_can_update_identity_and_sizes_without_creating_login(): void
    {
        $batch = $this->createBatch();
        $student = $batch->students()->firstOrFail();
        $path = tempnam(sys_get_temp_dir(), 'student-import-').'.xlsx';

        file_put_contents($path, Excel::raw(new StudentBatchExport($batch), ExcelFormat::XLSX));
        $workbook = IOFactory::load($path);
        $sheet = $workbook->getSheetByName('Data Siswa');
        $sheet->setCellValue('C5', 'I KADE SISWA BARU');
        $sheet->setCellValueExplicit('F5', '99112233', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('G5', 'SISWA OPERASIONAL');
        $sheet->setCellValue('H5', 'PENDIDIKAN');
        $sheet->setCellValue('J5', '56');
        $sheet->setCellValue('K5', 'L');
        $sheet->setCellValue('N5', '41');
        $sheet->setCellValue('S5', 'Hindu');
        IOFactory::createWriter($workbook, 'Xlsx')->save($path);

        $payload = app(StudentBatchImportService::class)->preview(
            $batch,
            new UploadedFile($path, 'data-siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        );

        $this->assertSame(1, $payload['stats']['update']);
        $this->assertSame(0, $payload['stats']['error']);

        app(StudentBatchImportService::class)->save($batch, $payload['rows']);
        $student->refresh();

        $this->assertSame('I KADE SISWA BARU', $student->full_name);
        $this->assertSame('99112233', $student->nrp);
        $this->assertSame($this->studentRank->id, $student->rank_id);
        $this->assertSame('SISWA OPERASIONAL', $student->jabatan);
        $this->assertSame('PENDIDIKAN', $student->bagian);
        $this->assertSame('Hindu', $student->religion);
        $this->assertSame('56', $student->kapor_sizes['topi']);
        $this->assertSame('B', $student->kapor_sizes['kemeja']);
        $this->assertSame('41', $student->kapor_sizes['sepatu_dinas']);
        $this->assertNull($student->user_id);

        @unlink($path);
    }

    public function test_superadmin_can_add_students_without_duplicate_codes(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $batch = $this->createBatch();

        $this->actingAs($superadmin)->post(route('admin.students.add-students', $batch), [
            'male_count' => 2,
            'female_count' => 2,
        ])->assertRedirect();

        $this->assertSame(5, $batch->students()->count());
        $this->assertSame(5, $batch->students()->distinct()->count('student_code'));
        $this->assertSame(3, $batch->fresh()->requested_male_count);
        $this->assertSame(2, $batch->fresh()->requested_female_count);
        $this->assertSame(5, $batch->students()->whereNull('user_id')->count());
    }

    public function test_superadmin_can_distribute_sizes_by_quota_without_overwriting_other_sizes(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $batch = app(StudentBatchService::class)->create([
            'name' => 'Siswa Kuota',
            'fiscal_year' => 2027,
            'procurement_group' => 'BINTARA',
            'default_rank_id' => $this->studentRank->id,
            'default_jabatan' => 'SISWA',
            'default_bagian' => 'SISWA',
            'male_count' => 3,
            'female_count' => 2,
            'notes' => null,
        ], null);

        $this->actingAs($superadmin)->post(route('admin.students.size-distribution', $batch), [
            'size_key' => 'topi',
            'gender' => '',
            'entries' => [
                ['size' => '55', 'count' => 2],
                ['size' => '56', 'count' => 3],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->actingAs($superadmin)->post(route('admin.students.size-distribution', $batch), [
            'size_key' => 'sepatu_dinas',
            'gender' => 'L',
            'entries' => [
                ['size' => '41', 'count' => 2],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $students = $batch->students()->orderBy('student_code')->get();
        $this->assertSame(2, $students->filter(fn (Personnel $student) => (string) data_get($student->kapor_sizes, 'topi') === '55')->count());
        $this->assertSame(3, $students->filter(fn (Personnel $student) => (string) data_get($student->kapor_sizes, 'topi') === '56')->count());
        $this->assertSame(2, $students->filter(fn (Personnel $student) => (string) data_get($student->kapor_sizes, 'sepatu_dinas') === '41')->count());
        $this->assertSame(5, $students->filter(fn (Personnel $student) => filled(data_get($student->kapor_sizes, 'topi')))->count());

        $this->actingAs($superadmin)->post(route('admin.students.size-distribution', $batch), [
            'size_key' => 'topi',
            'gender' => '',
            'entries' => [['size' => '57', 'count' => 6]],
        ])->assertRedirect()->assertSessionHasErrors('entries');
    }

    public function test_student_is_visible_in_personnel_and_edit_does_not_create_login(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $batch = $this->createBatch();
        $student = $batch->students()->firstOrFail();

        $this->actingAs($superadmin)
            ->get(route('admin.personnel.index', ['search' => $student->student_code]))
            ->assertOk()
            ->assertSeeText($student->full_name);

        $this->actingAs($superadmin)->put(route('admin.personnel.update', $student), [
            'nrp' => '99001122',
            'full_name' => 'SISWA TITIPAN UJI',
            'rank_id' => $this->studentRank->id,
            'satker_id' => Satker::where('code', 'POLDA-NTB')->value('id'),
            'jabatan' => 'SISWA TITIPAN',
            'bagian' => 'SISWA',
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'keterangan' => 'SISWA',
            'is_active' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $student->refresh();
        $this->assertSame('SISWA TITIPAN UJI', $student->full_name);
        $this->assertSame('99001122', $student->nrp);
        $this->assertNull($student->user_id);
        $this->assertDatabaseMissing('users', ['nrp_nip' => '99001122']);
    }

    public function test_archiving_student_batch_updates_student_active_status(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');
        $batch = $this->createBatch();

        $this->actingAs($superadmin)
            ->patch(route('admin.students.archive', $batch))
            ->assertRedirect();

        $this->assertSame(StudentBatch::STATUS_ARCHIVED, $batch->fresh()->status);
        $this->assertSame(0, $batch->students()->where('is_active', true)->count());

        $this->actingAs($superadmin)
            ->patch(route('admin.students.archive', $batch))
            ->assertRedirect();

        $this->assertSame(StudentBatch::STATUS_ACTIVE, $batch->fresh()->status);
        $this->assertSame(1, $batch->students()->where('is_active', true)->count());
    }

    public function test_student_without_user_is_included_in_final_package_snapshot(): void
    {
        $batch = $this->createBatch();
        $student = $batch->students()->firstOrFail();
        $budgetYear = BudgetYear::create(['year' => 2027, 'name' => 'T.A. 2027', 'is_active' => true]);
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
            'satker_id' => $batch->satker_id,
            'recipient_filters' => ['personnel_type' => ['polri'], 'rank_categories' => ['BINTARA']],
            'matched_count' => 1,
        ]);

        app(PersonnelItemAllocationSnapshotService::class)->regenerateForBudgetPackage($package);

        $allocation = PersonnelItemAllocation::firstOrFail();
        $this->assertSame($student->id, $allocation->personnel_id);
        $this->assertNull($allocation->user_id);
        $this->assertSame($student->student_code, $allocation->nrp_snapshot);

        $sppmRows = app(BudgetPackageSppmAssignmentService::class)
            ->eligibleRows($package->fresh(), $batch->satker);

        $this->assertTrue($sppmRows->contains('personnel_id', $student->id));
    }

    private function createBatch(): StudentBatch
    {
        return app(StudentBatchService::class)->create([
            'name' => 'Siswa Diktukba Uji',
            'fiscal_year' => 2027,
            'procurement_group' => 'BINTARA',
            'default_rank_id' => $this->studentRank->id,
            'default_jabatan' => 'SISWA',
            'default_bagian' => 'SISWA',
            'male_count' => 1,
            'female_count' => 0,
            'notes' => null,
        ], null);
    }
}
