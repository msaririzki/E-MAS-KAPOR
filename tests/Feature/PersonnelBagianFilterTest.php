<?php

namespace Tests\Feature;

use App\Exports\PersonnelExport;
use App\Models\BagianOption;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelBagianFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_admin_satker_can_filter_personnel_index_by_bagian(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $otherSatker = Satker::create([
            'name' => 'POLRES DOMPU',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        BagianOption::create([
            'name' => 'SAT RESKRIM',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $this->createPersonnel($satker, $rank, '76100160', 'RESKRIM PERSON', 'Sat Reskrim');
        $this->createPersonnel($satker, $rank, '76100161', 'LANTAS PERSON', 'SAT LANTAS');
        $this->createPersonnel($otherSatker, $rank, '76100162', 'RESKRIM SATKER LAIN', 'SAT RESKRIM');

        $response = $this->actingAs($adminSatker)->get(route('admin.personnel.index', [
            'bagian' => 'SAT RESKRIM',
        ]));

        $response->assertOk();
        $response->assertSeeText('RESKRIM PERSON');
        $response->assertDontSeeText('LANTAS PERSON');
        $response->assertDontSeeText('RESKRIM SATKER LAIN');
    }

    public function test_admin_satker_can_filter_reports_by_bagian_and_incomplete_status(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        BagianOption::create([
            'name' => 'SAT RESKRIM',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $completeSizes = [
            'topi' => '56',
            'kemeja' => 'M',
            'celana' => '32',
            'olahraga' => 'M',
            'sepatu_dinas' => '42',
            'sepatu_olahraga' => '42',
            'jaket' => 'M',
            'sabuk' => '95',
        ];

        $this->createPersonnel($satker, $rank, '76100163', 'RESKRIM LENGKAP', 'Sat Reskrim', $completeSizes);
        $this->createPersonnel($satker, $rank, '76100164', 'RESKRIM BELUM LENGKAP', 'Sat Reskrim', [
            'topi' => '56',
            'kemeja' => 'M',
        ]);
        $this->createPersonnel($satker, $rank, '76100165', 'LANTAS BELUM LENGKAP', 'SAT LANTAS', [
            'topi' => '56',
        ]);

        $response = $this->actingAs($adminSatker)->get(route('admin-satker.reports', [
            'bagian' => 'SAT RESKRIM',
            'status' => 'pending',
        ]));

        $response->assertOk();
        $response->assertSeeText('RESKRIM BELUM LENGKAP');
        $response->assertDontSeeText('RESKRIM LENGKAP');
        $response->assertDontSeeText('LANTAS BELUM LENGKAP');
        $response->assertSeeText('Belum Lengkap');
    }

    public function test_admin_satker_reports_export_link_preserves_active_filters(): void
    {
        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $response = $this->actingAs($adminSatker)->get(route('admin-satker.reports', [
            'search' => 'EGAS',
            'status' => 'pending',
            'bagian' => 'SAT RESKRIM',
        ]));

        $response->assertOk();
        $response->assertSee('search=EGAS', false);
        $response->assertSee('status=pending', false);
        $response->assertSee('bagian=SAT%20RESKRIM', false);
    }

    public function test_admin_satker_export_personnel_applies_report_filters(): void
    {
        Excel::fake();

        $satker = Satker::create([
            'name' => 'POLRES BIMA',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $completeSizes = [
            'topi' => '56',
            'kemeja' => 'M',
            'celana' => '32',
            'olahraga' => 'M',
            'sepatu_dinas' => '42',
            'sepatu_olahraga' => '42',
            'jaket' => 'M',
            'sabuk' => '95',
        ];

        $this->createPersonnel($satker, $rank, '76100166', 'RESKRIM LENGKAP', 'Sat Reskrim', $completeSizes);
        $this->createPersonnel($satker, $rank, '76100167', 'RESKRIM BELUM LENGKAP', 'SAT RESKRIM', [
            'topi' => '56',
        ]);
        $this->createPersonnel($satker, $rank, '76100168', 'LANTAS BELUM LENGKAP', 'SAT LANTAS', [
            'topi' => '56',
        ]);

        $response = $this->actingAs($adminSatker)->get(route('admin.personnel.export-personnel', [
            'bagian' => 'SAT RESKRIM',
            'status' => 'pending',
        ]));

        $response->assertOk();

        Excel::assertDownloaded('Data_Personel_POLRES BIMA_'.date('Ymd').'.xlsx', function (PersonnelExport $export) {
            $sheets = collect($export->sheets());
            $rows = $sheets
                ->flatMap(fn ($sheet) => $sheet->collection()->all())
                ->values();

            $names = $rows->pluck(1);

            return $names->contains('RESKRIM BELUM LENGKAP')
                && ! $names->contains('RESKRIM LENGKAP')
                && ! $names->contains('LANTAS BELUM LENGKAP');
        });
    }

    private function createPersonnel(
        Satker $satker,
        Rank $rank,
        string $nrp,
        string $name,
        string $bagian,
        ?array $kaporSizes = null,
    ): Personnel {
        $user = User::factory()->create([
            'name' => $name,
            'nrp_nip' => $nrp,
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        return Personnel::create([
            'user_id' => $user->id,
            'nrp' => $nrp,
            'full_name' => $name,
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT',
            'bagian' => $bagian,
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'verification_status' => 'approved',
            'kapor_sizes' => $kaporSizes,
        ]);
    }
}
