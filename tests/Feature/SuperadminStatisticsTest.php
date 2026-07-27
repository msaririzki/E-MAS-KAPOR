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
use App\Services\TestimonialExportService;
use App\Services\TestimonialWordExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

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
        $response->assertViewHas('fiscal_year', '2026');
        $response->assertViewHas('topSatkers', fn ($satkers) => $satkers->pluck('satker_name')->contains('Biro SDM'));
        $response->assertViewHas('latestTestimonials', function ($reviews) {
            return $reviews->pluck('comment')->contains('Aplikasi sangat membantu rekap kapor menjadi cepat dan rapi.');
        });
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
        $response->assertSeeText('Belum ada testimoni');
        $response->assertSeeText('masuk');
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
            'compare_items' => [],
        ]);
        $response->assertViewHas('categoryStats', function (array $stats) {
            return array_key_exists('kepala', $stats)
                && array_key_exists('badan', $stats)
                && array_key_exists('kaki', $stats)
                && array_key_exists('lainnya', $stats);
        });
        $response->assertViewHas('distributionStats', function (array $stats) {
            return array_key_exists('kepala', $stats)
                && ! array_key_exists('badan', $stats)
                && $stats['kepala']['count'] === 1
                && $stats['kepala']['ratingBreakdown'][0]['count'] === 1;
        });
    }

    public function test_statistics_page_honors_selected_year_filter(): void
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

        $personil2025 = User::factory()->create([
            'name' => 'User 2025',
            'satker_id' => $satker->id,
        ]);
        $personil2025->assignRole('personil');

        $personil2026 = User::factory()->create([
            'name' => 'User 2026',
            'satker_id' => $satker->id,
        ]);
        $personil2026->assignRole('personil');

        $allocation2025 = $this->createAllocation($personil2025, $satker, 'HELM TAKTIS', 'Tutup Kepala', 'Tutup_Kepala', 2025);
        $allocation2026 = $this->createAllocation($personil2026, $satker, 'ROMPI', 'Tutup Badan', 'Tutup_Badan', 2026);

        ItemReview::create([
            'personnel_item_allocation_id' => $allocation2025->id,
            'user_id' => $personil2025->id,
            'kapor_item_id' => $allocation2025->kapor_item_id,
            'fiscal_year' => 2025,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Review lama masih harus bisa dibuka.',
            'rating' => 5,
            'submitted_at' => now()->subYear(),
        ]);

        ItemReview::create([
            'personnel_item_allocation_id' => $allocation2026->id,
            'user_id' => $personil2026->id,
            'kapor_item_id' => $allocation2026->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Review tahun aktif.',
            'rating' => 2,
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->get(route('superadmin.statistics', [
            'year' => 2025,
        ]));

        $response->assertOk();
        $response->assertViewHas('fiscal_year', '2025');
        $response->assertViewHas('totalTestimonials', 1);
        $response->assertViewHas('latestTestimonials', function ($reviews) {
            return $reviews->count() === 1
                && $reviews->first()->comment === 'Review lama masih harus bisa dibuka.';
        });
    }

    public function test_testimonial_pages_show_historical_reviews_when_active_year_has_advanced(): void
    {
        Setting::setValue('fiscal_year', '2027');

        $satker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personil = User::factory()->create([
            'name' => 'Personel Review 2026',
            'satker_id' => $satker->id,
        ]);
        $personil->assignRole('personil');

        $allocation = $this->createAllocation(
            $personil,
            $satker,
            'SEPATU DINAS 2026',
            'Tutup Kaki',
            'Tutup_Kaki',
            2026,
        );

        ItemReview::create([
            'personnel_item_allocation_id' => $allocation->id,
            'user_id' => $personil->id,
            'kapor_item_id' => $allocation->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Review tahun 2026 tetap dapat dilihat.',
            'rating' => 5,
            'submitted_at' => now(),
        ]);

        $listResponse = $this->actingAs($superadmin)->get(route('superadmin.testimonials.index', [
            'year' => 2026,
        ]));

        $listResponse->assertOk();
        $listResponse->assertViewHas('fiscalYear', 2026);
        $listResponse->assertViewHas('activeYear', 2027);
        $listResponse->assertViewHas('testimonials', fn ($reviews) => $reviews->total() === 1);
        $listResponse->assertSeeText('Review tahun 2026 tetap dapat dilihat.');
        $listResponse->assertSee('name="year"', false);
        $listResponse->assertSee('onchange="this.form.requestSubmit()"', false);

        $statisticsResponse = $this->actingAs($superadmin)->get(route('superadmin.statistics', [
            'year' => 2026,
        ]));

        $statisticsResponse->assertOk();
        $statisticsResponse->assertViewHas('fiscal_year', '2026');
        $statisticsResponse->assertViewHas('active_year', 2027);
        $statisticsResponse->assertViewHas('totalTestimonials', 1);
        $statisticsResponse->assertViewHas(
            'latestTestimonials',
            fn ($reviews) => $reviews->count() === 1
                && $reviews->first()->comment === 'Review tahun 2026 tetap dapat dilihat.',
        );
    }

    public function test_testimonial_export_groups_percentages_by_category_item_and_satker(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satkerA = Satker::create([
            'name' => 'Biro SDM',
            'code' => 'BIRO-SDM',
            'sort_order' => 1,
        ]);
        $satkerB = Satker::create([
            'name' => 'Dit Samapta',
            'code' => 'DIT-SAMAPTA',
            'sort_order' => 2,
        ]);

        $userA = User::factory()->create(['satker_id' => $satkerA->id, 'name' => 'User A']);
        $userA->assignRole('personil');
        $userB = User::factory()->create(['satker_id' => $satkerB->id, 'name' => 'User B']);
        $userB->assignRole('personil');
        $userC = User::factory()->create(['satker_id' => $satkerA->id, 'name' => 'User C']);
        $userC->assignRole('personil');
        $userD = User::factory()->create(['satker_id' => $satkerA->id, 'name' => 'User D']);
        $userD->assignRole('personil');

        $allocationA = $this->createAllocation($userA, $satkerA, 'BARET LAPANGAN', 'Tutup Kepala', 'Tutup_Kepala');
        $allocationB = $this->createAllocation($userB, $satkerB, 'BARET LAPANGAN', 'Tutup Kepala', 'Tutup_Kepala');
        $allocationC = $this->createAllocation($userC, $satkerA, 'BARET LAPANGAN', 'Tutup Kepala', 'Tutup_Kepala');
        $allocationD = $this->createAllocation($userD, $satkerA, 'BARET LAPANGAN', 'Tutup Kepala', 'Tutup_Kepala');

        foreach ([
            [$allocationA, $userA, 4, 'Kualitas bagus dan nyaman.'],
            [$allocationB, $userB, 5, 'Sangat baik untuk dinas.'],
            [$allocationC, $userC, 4, 'Ukuran pas dan rapi.'],
            [$allocationD, $userD, 4, 'Komentar ketiga tidak masuk batas export.'],
        ] as [$allocation, $user, $rating, $comment]) {
            ItemReview::create([
                'personnel_item_allocation_id' => $allocation->id,
                'user_id' => $user->id,
                'kapor_item_id' => $allocation->kapor_item_id,
                'fiscal_year' => 2026,
                'response_status' => ItemReview::STATUS_REVIEWED,
                'comment' => $comment,
                'rating' => $rating,
                'submitted_at' => now(),
            ]);
        }

        $data = app(TestimonialExportService::class)->build(2026);
        $category = $data['categoryGroups']->firstWhere('name', 'Tutup Kepala');
        $item = $category['items']->firstWhere('name', 'BARET LAPANGAN');

        $this->assertSame(80.0, $item['satker_scores']['Biro SDM']);
        $this->assertSame(100.0, $item['satker_scores']['Dit Samapta']);
        $this->assertSame(2, $data['commentsByRating'][4]->count());

        $customData = app(TestimonialExportService::class)->build(2026, 3);

        $this->assertSame(3, $customData['commentsPerRating']);
        $this->assertSame(3, $customData['commentsByRating'][4]->count());
    }

    public function test_testimonial_word_export_matches_review_content_order(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'SPN',
            'code' => 'SPN',
            'sort_order' => 1,
        ]);

        $superadmin = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $superadmin->assignRole('superadmin');

        $personil = User::factory()->create([
            'name' => 'M. TAUFIK',
            'satker_id' => $satker->id,
        ]);
        $personil->assignRole('personil');

        $allocation = $this->createAllocation($personil, $satker, 'SEPATU OLAHRAGA', 'Tutup Kaki', 'Tutup_Kaki');

        ItemReview::create([
            'personnel_item_allocation_id' => $allocation->id,
            'user_id' => $personil->id,
            'kapor_item_id' => $allocation->kapor_item_id,
            'fiscal_year' => 2026,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'comment' => 'Nyaman madel bagus kualitas baik',
            'rating' => 5,
            'submitted_at' => now(),
        ]);

        $data = app(TestimonialExportService::class)->build(2026);
        $path = app(TestimonialWordExportService::class)->generate($data);
        $documentXml = $this->readDocumentXml($path);

        $source = 'M. TAUFIK - SPN - SEPATU OLAHRAGA';
        $comment = 'Nyaman madel bagus kualitas baik';

        $this->assertStringContainsString('HASIL REVIEW PENGADAAN KAPOR', $documentXml);
        $this->assertStringContainsString($source, $documentXml);
        $this->assertStringContainsString($comment, $documentXml);
        $this->assertLessThan(strpos($documentXml, $comment), strpos($documentXml, $source));
        $this->assertXmlStringEqualsXmlString($documentXml, $documentXml);

        @unlink($path);

        $this->actingAs($superadmin)
            ->get(route('superadmin.testimonials.export-word', ['year' => 2026, 'comments_per_rating' => 1]))
            ->assertOk()
            ->assertDownload('Hasil_Review_Kapor_TA_2026.docx');
    }

    private function createAllocation(User $user, Satker $satker, string $itemName, string $snapshotCategory = 'Tutup Kepala', string $dbCategory = 'Tutup_Kepala', int $fiscalYear = 2026): PersonnelItemAllocation
    {
        $budgetYear = BudgetYear::firstOrCreate([
            'year' => $fiscalYear,
        ], [
            'name' => 'Tahun Anggaran '.$fiscalYear,
            'is_active' => $fiscalYear === 2026,
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
            'fiscal_year' => $fiscalYear,
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

    private function readDocumentXml(string $path): string
    {
        $zip = new ZipArchive;
        $opened = $zip->open($path);

        $this->assertTrue($opened === true, 'DOCX hasil generate tidak bisa dibuka.');

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $this->assertIsString($documentXml);

        return $documentXml;
    }
}
