<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\ItemReview;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\PersonnelItemAllocation;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SuperadminStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['superadmin', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_superadmin_can_view_testimonial_statistics(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Biro SDM',
            'code' => 'BIRO-SDM',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personilA = User::factory()->create([
            'name' => 'Briptu Andi',
            'satker_id' => $satker->id,
        ]);
        $personilA->assignRole('personil');

        $personilB = User::factory()->create([
            'name' => 'Aipda Sinta',
            'satker_id' => $satker->id,
        ]);
        $personilB->assignRole('personil');

        $allocationA = $this->createAllocation($personilA, $satker, 'BARET LAPANGAN');
        $allocationB = $this->createAllocation($personilB, $satker, 'SEPATU DINAS');

        ItemReview::create([
            'personnel_item_allocation_id' => $allocationA->id,
            'user_id' => $personilA->id,
            'kapor_item_id' => $allocationA->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Aplikasi sangat membantu rekap kapor menjadi cepat dan rapi.',
            'rating' => 5,
            'submitted_at' => now(),
        ]);

        ItemReview::create([
            'personnel_item_allocation_id' => $allocationB->id,
            'user_id' => $personilB->id,
            'kapor_item_id' => $allocationB->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Tampilan sudah bagus, namun respons form masih bisa dipercepat.',
            'rating' => 3,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.statistics'));

        $response->assertOk();
        $response->assertViewHas('totalTestimonials', 2);
        $response->assertViewHas('averageRating', 4.0);
        $response->assertSeeText('Statistik Testimoni');
        $response->assertSeeText('Biro SDM');
        $response->assertSeeText('Aplikasi sangat membantu rekap kapor menjadi cepat dan rapi.');
    }

    public function test_statistics_page_shows_empty_state_when_no_testimonials_exist(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->get(route('superadmin.statistics'));

        $response->assertOk();
        $response->assertViewHas('totalTestimonials', 0);
        $response->assertSeeText('Belum ada testimoni masuk');
    }

    public function test_statistics_page_groups_reviews_into_four_fixed_buckets_and_applies_distribution_filters(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $kepalaUser = User::factory()->create(['satker_id' => $satker->id, 'name' => 'User Kepala']);
        $kepalaUser->assignRole('personil');
        $badanUser = User::factory()->create(['satker_id' => $satker->id, 'name' => 'User Badan']);
        $badanUser->assignRole('personil');
        $kakiUser = User::factory()->create(['satker_id' => $satker->id, 'name' => 'User Kaki']);
        $kakiUser->assignRole('personil');
        $reportUser = User::factory()->create(['satker_id' => $satker->id, 'name' => 'User Report']);
        $reportUser->assignRole('personil');

        $kepalaAllocation = $this->createAllocation($kepalaUser, $satker, 'TOPI LAPANGAN', 'Tutup Kepala', 'Tutup_Kepala');
        $badanAllocation = $this->createAllocation($badanUser, $satker, 'KEMEJA PDH', 'Tutup Badan', 'Tutup_Badan');
        $kakiAllocation = $this->createAllocation($kakiUser, $satker, 'SEPATU DINAS', 'Tutup Kaki', 'Tutup_Kaki');
        $reportAllocation = $this->createAllocation($reportUser, $satker, 'SEPATU OLAHRAGA', 'Tutup Kaki', 'Tutup_Kaki');

        ItemReview::create([
            'personnel_item_allocation_id' => $kepalaAllocation->id,
            'user_id' => $kepalaUser->id,
            'kapor_item_id' => $kepalaAllocation->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Mantap.',
            'rating' => 5,
            'submitted_at' => now(),
        ]);
        ItemReview::create([
            'personnel_item_allocation_id' => $badanAllocation->id,
            'user_id' => $badanUser->id,
            'kapor_item_id' => $badanAllocation->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Cukup.',
            'rating' => 3,
            'submitted_at' => now(),
        ]);
        ItemReview::create([
            'personnel_item_allocation_id' => $kakiAllocation->id,
            'user_id' => $kakiUser->id,
            'kapor_item_id' => $kakiAllocation->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Kurang.',
            'rating' => 2,
            'submitted_at' => now(),
        ]);
        ItemReview::create([
            'personnel_item_allocation_id' => $reportAllocation->id,
            'user_id' => $reportUser->id,
            'kapor_item_id' => $reportAllocation->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_NOT_RECEIVED,
            'comment' => 'Belum sampai.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.statistics', [
            'distribution_group' => 'kepala',
            'distribution_rating' => 5,
        ]));

        $response->assertOk();
        $response->assertViewHas('distributionFilters', [
            'group' => 'kepala',
            'rating' => 5,
        ]);
        $response->assertViewHas('categoryStats', function (array $stats) {
            return array_key_exists('kepala', $stats)
                && array_key_exists('badan', $stats)
                && array_key_exists('kaki', $stats)
                && array_key_exists('belum_menerima', $stats);
        });
        $response->assertViewHas('distributionStats', function (array $stats) {
            return array_key_exists('kepala', $stats)
                && ! array_key_exists('badan', $stats)
                && $stats['kepala']['count'] === 1
                && $stats['kepala']['ratingBreakdown'][0]['count'] === 1;
        });
    }

    private function createAllocation(User $user, Satker $satker, string $itemName, string $snapshotCategory = 'Tutup Kepala', string $dbCategory = 'Tutup_Kepala'): PersonnelItemAllocation
    {
        $budgetYear = BudgetYear::firstOrCreate([
            'year' => 2026,
        ], [
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket '.$itemName,
            'status' => 'finalized',
            'total_budget' => 0,
        ]);

        $kaporItem = KaporItem::create([
            'category' => $dbCategory,
            'item_name' => $itemName,
            'price' => 120000,
            'unit' => 'PCS',
            'is_active' => true,
            'for_identifikasi' => true,
        ]);

        $packageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $kaporItem->id,
            'calculated_qty' => 1,
            'calculated_total' => 120000,
        ]);

        return PersonnelItemAllocation::create([
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'kapor_item_id' => $kaporItem->id,
            'user_id' => $user->id,
            'satker_id' => $satker->id,
            'fiscal_year' => 2026,
            'allocation_status' => 'eligible',
            'allocated_at' => now(),
            'nrp_snapshot' => $user->nrp_nip,
            'full_name_snapshot' => $user->name,
            'satker_name_snapshot' => $satker->name,
            'kapor_item_name_snapshot' => $itemName,
            'item_category_snapshot' => $snapshotCategory,
            'budget_package_name_snapshot' => $package->name,
        ]);
    }
}
