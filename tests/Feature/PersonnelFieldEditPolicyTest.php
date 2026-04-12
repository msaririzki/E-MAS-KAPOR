<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BudgetPackage;
use App\Models\BudgetYear;
use App\Models\ItemReview;
use App\Models\KaporItem;
use App\Models\PackageItem;
use App\Models\Personnel;
use App\Models\PersonnelItemAllocation;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PersonnelFieldEditPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin_satker', 'personil'] as $roleName) {
            Role::findOrCreate($roleName);
        }
    }

    public function test_admin_satker_can_edit_full_personnel_profile_within_own_satker_scope(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $otherSatker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AKBP',
            'category' => 'PAMEN',
            'sort_order' => 1,
        ]);

        $otherRank = Rank::create([
            'name' => 'KOMPOL',
            'category' => 'PAMEN',
            'sort_order' => 2,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $user = User::factory()->create([
            'name' => 'EKO SUTOMO',
            'nrp_nip' => '82051489',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'PAMEN',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($adminSatker)->put(route('admin.personnel.update', $personnel), [
            'nrp' => '99999999',
            'full_name' => 'NAMA BARU',
            'rank_id' => $otherRank->id,
            'satker_id' => $otherSatker->id,
            'personnel_type' => 'PNS',
            'gender' => 'P',
            'jabatan' => 'JABATAN BARU',
            'bagian' => 'BAGIAN BARU',
            'golongan' => 'III/A',
            'religion' => 'Hindu',
            'phone' => '08123456789',
            'keterangan' => 'KET BARU',
            'keterangan_2' => 'KET2 BARU',
            'keterangan_3' => 'KET3 BARU',
            'keterangan_4' => 'KET4 BARU',
            'kapor_sizes' => ['topi' => '60'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'nrp' => '99999999',
            'full_name' => 'NAMA BARU',
            'rank_id' => $otherRank->id,
            'satker_id' => $satker->id,
            'jabatan' => 'JABATAN BARU',
            'bagian' => 'BAGIAN BARU',
            'keterangan' => 'KET BARU',
            'personnel_type' => 'PNS',
            'gender' => 'P',
            'golongan' => 'III/A',
            'religion' => 'Hindu',
            'phone' => '08123456789',
        ]);

        $this->assertSame(['topi' => '60'], $personnel->fresh()->kapor_sizes);
        $this->assertSame('99999999', $user->fresh()->nrp_nip);
        $this->assertSame('NAMA BARU', $user->fresh()->name);
        $this->assertSame($satker->id, $user->fresh()->satker_id);
        $this->assertSame('08123456789', $user->fresh()->phone);
    }

    public function test_admin_satker_gets_info_feedback_when_no_personnel_data_changes(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $otherSatker = Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 2,
        ]);

        $rank = Rank::create([
            'name' => 'AKBP',
            'category' => 'PAMEN',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $user = User::factory()->create([
            'name' => 'EKO SUTOMO',
            'nrp_nip' => '82051489',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'PAMEN',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($adminSatker)->put(route('admin.personnel.update', $personnel), [
            'nrp' => '82051489',
            'full_name' => 'EKO SUTOMO',
            'rank_id' => $rank->id,
            'satker_id' => $otherSatker->id,
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'keterangan' => 'KET LAMA',
            'keterangan_2' => 'KET2 LAMA',
            'jabatan' => 'JABATAN LAMA',
            'bagian' => 'BAGIAN LAMA',
            'golongan' => 'PAMEN',
            'religion' => 'Islam',
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info', 'Tidak ada perubahan pada data personel.');

        $this->assertDatabaseHas('personnels', [
            'id' => $personnel->id,
            'satker_id' => $satker->id,
            'keterangan_2' => 'KET2 LAMA',
        ]);
    }

    public function test_personil_can_update_jabatan_bagian_and_sizes_from_personil_form(): void
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

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN AWAL',
            'bagian' => 'BAGIAN AWAL',
            'satker_id' => $satker->id,
            'religion' => 'Katolik',
            'is_active' => true,
            'kapor_sizes' => [],
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
        $response->assertSessionHas('success');

        $personnel->refresh();

        $this->assertSame('BANIT RESKRIM', $personnel->jabatan);
        $this->assertSame('SAT RESKRIM', $personnel->bagian);
        $this->assertSame('08123456789', $personnel->phone);
        $this->assertSame($satker->id, $personnel->satker_id);
        $this->assertSame('15', $personnel->kapor_sizes['kemeja']);
        $this->assertSame('57', $personnel->kapor_sizes['topi']);
        $this->assertSame('08123456789', $user->fresh()->phone);
    }

    public function test_personil_can_save_identity_first_and_generate_audit_log(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Dompu',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'BRIPKA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'RIZAL ARDIANSYAH',
            'nrp_nip' => '76100161',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100161',
            'full_name' => 'RIZAL ARDIANSYAH',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN SDM',
            'bagian' => null,
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $response = $this->actingAs($user)->post(route('personil.kapor.store'), [
            'mode' => 'identity',
            'jabatan' => 'BANIT SAMAPTA',
            'bagian' => 'SIAGA',
            'phone' => '08123456789',
        ]);

        $response->assertRedirect(route('dashboard').'#ukuran-form');
        $response->assertSessionHas('success');

        $personnel->refresh();

        $this->assertSame('BANIT SAMAPTA', $personnel->jabatan);
        $this->assertSame('SIAGA', $personnel->bagian);
        $this->assertSame('08123456789', $personnel->phone);
        $this->assertSame('08123456789', $user->fresh()->phone);

        $auditLog = AuditLog::query()->latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertSame('Edit Referensi SDM Personil', $auditLog->action);
        $this->assertSame('Data Personil', $auditLog->category);
        $this->assertSame($personnel->id, $auditLog->auditable_id);
        $this->assertSame(['jabatan' => 'JABATAN SDM', 'bagian' => null, 'phone' => null], $auditLog->old_values);
        $this->assertSame(['jabatan' => 'BANIT SAMAPTA', 'bagian' => 'SIAGA', 'phone' => '08123456789'], $auditLog->new_values);
    }

    public function test_personil_non_polres_can_update_jabatan_and_sizes_without_bagian(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDay()->toDateString());

        $satker = Satker::create([
            'name' => 'DIT LANTAS',
            'code' => 'DITLANTAS',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPDA',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'AYUB',
            'nrp_nip' => '197001012014121003',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '197001012014121003',
            'full_name' => 'AYUB',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'JABATAN AWAL',
            'bagian' => 'BAGIAN SDM',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [],
        ]);

        $response = $this->actingAs($user)->post(route('personil.kapor.store'), [
            'jabatan' => 'BANUM URMINTU SUBBAGRENMIN DITLANTAS',
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
        $response->assertSessionHas('success');

        $personnel->refresh();

        $this->assertSame('BANUM URMINTU SUBBAGRENMIN DITLANTAS', $personnel->jabatan);
        $this->assertSame('BAGIAN SDM', $personnel->bagian);
        $this->assertSame('08123456789', $personnel->phone);
        $this->assertSame('15', $personnel->kapor_sizes['kemeja']);
        $this->assertSame('57', $personnel->kapor_sizes['topi']);
        $this->assertSame('08123456789', $user->fresh()->phone);
    }

    public function test_personil_dashboard_hides_bagian_field_for_non_polres_satker(): void
    {
        $satker = Satker::create([
            'name' => 'DIT LANTAS',
            'code' => 'DITLANTAS',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'AYUB',
            'nrp_nip' => '197001012014121003',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '197001012014121003',
            'full_name' => 'AYUB',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANUM DITLANTAS',
            'bagian' => 'BAGIAN SDM',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('1. Jabatan');
        $response->assertDontSee('name="bagian"', false);
        $response->assertDontSeeText('Bag/Fungsi');
    }

    public function test_personil_write_routes_are_blocked_when_system_is_locked_but_history_remains_read_only(): void
    {
        Setting::setValue('is_system_locked', 'true');

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

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT RESKRIM',
            'bagian' => 'SAT RESKRIM',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $this->actingAs($user)
            ->get(route('personil.kapor.history'))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('personil.kapor.store'), [
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
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_admin_satker_write_routes_are_blocked_outside_input_period_but_index_remains_read_only(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subMonths(3)->toDateString());
        Setting::setValue('input_end_date', now()->subDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $adminSatker = User::factory()->create([
            'satker_id' => $satker->id,
        ]);
        $adminSatker->assignRole('admin_satker');

        $this->actingAs($adminSatker)
            ->get(route('admin-satker.kebutuhan.index'))
            ->assertOk();

        $this->actingAs($adminSatker)
            ->post(route('admin-satker.kebutuhan.store'), [
                'items' => [],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_personil_dashboard_shows_input_period_status_and_testimonial_cta_when_sizes_complete(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDays(7)->toDateString());
        Setting::setValue('review_start_date', now()->subDay()->toDateString());
        Setting::setValue('review_end_date', now()->addDays(7)->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100171',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100171',
            'full_name' => 'BAYU SAPUTRA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT OPERASI',
            'bagian' => 'SIAGA',
            'phone' => '08123456789',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [
                'topi' => '57',
                'kemeja' => '15',
                'celana' => '32',
                'olahraga' => 'B',
                'jaket' => 'B',
                'sepatu_dinas' => '41',
                'sepatu_olahraga' => '41',
                'sabuk' => '42',
            ],
        ]);

        $this->createItemAllocation($user, $satker, 'BARET LAPANGAN');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Ada 1 Item Menunggu Respons');
        $response->assertSeeText('Buka Halaman Review');
        $response->assertDontSeeText('Periode Input Sedang Berjalan');
    }

    public function test_personil_dashboard_shows_review_completion_message_when_item_response_exists(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDays(7)->toDateString());
        Setting::setValue('review_start_date', now()->subDay()->toDateString());
        Setting::setValue('review_end_date', now()->addDays(7)->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100172',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100172',
            'full_name' => 'BAYU SAPUTRA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT OPERASI',
            'bagian' => 'SIAGA',
            'phone' => '08123456789',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => [
                'topi' => '57',
                'kemeja' => '15',
                'celana' => '32',
                'olahraga' => 'B',
                'jaket' => 'B',
                'sepatu_dinas' => '41',
                'sepatu_olahraga' => '41',
                'sabuk' => '42',
            ],
        ]);

        $allocation = $this->createItemAllocation($user, $satker, 'BARET LAPANGAN', $personnel);

        ItemReview::create([
            'personnel_item_allocation_id' => $allocation->id,
            'user_id' => $user->id,
            'personnel_id' => $personnel->id,
            'kapor_item_id' => $allocation->kapor_item_id,
            'fiscal_year' => (int) Setting::getValue('fiscal_year', date('Y')),
            'response_status' => ItemReview::STATUS_REVIEWED,
            'rating' => 5,
            'comment' => 'Nyaman dipakai.',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Semua Item Sudah Direspons');
        $response->assertSeeText('Kelola Review');
    }

    public function test_personil_dashboard_prioritizes_input_period_card_when_review_period_is_not_open(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('input_start_date', now()->subDay()->toDateString());
        Setting::setValue('input_end_date', now()->addDays(7)->toDateString());
        Setting::setValue('review_start_date', now()->addDays(14)->toDateString());
        Setting::setValue('review_end_date', now()->addDays(30)->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100176',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100176',
            'full_name' => 'BAYU SAPUTRA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $satker->id,
            'jabatan' => 'BANIT OPERASI',
            'phone' => '08123456789',
            'is_active' => true,
            'kapor_sizes' => [
                'topi' => '57',
                'kemeja' => '15',
                'celana' => '32',
                'olahraga' => 'B',
                'jaket' => 'B',
                'sepatu_dinas' => '41',
                'sepatu_olahraga' => '41',
                'sabuk' => '42',
            ],
        ]);

        $this->createItemAllocation($user, $satker, 'BARET LAPANGAN');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Periode Input Sedang Berjalan');
        $response->assertDontSeeText('Ada 1 Item Menunggu Respons');
    }

    public function test_personil_testimoni_page_shows_read_only_notice_when_review_period_is_closed(): void
    {
        Setting::setValue('is_system_locked', 'false');
        Setting::setValue('review_start_date', now()->subMonths(2)->toDateString());
        Setting::setValue('review_end_date', now()->subDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100173',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $this->createItemAllocation($user, $satker, 'SEPATU DINAS');

        $response = $this->actingAs($user)->get(route('personil.testimoni.index'));

        $response->assertOk();
        $response->assertSeeText('Periode Review Sudah Ditutup');
        $response->assertSeeText('Form tampil dalam mode baca saja');
    }

    public function test_personil_can_store_and_update_item_review_in_same_fiscal_year(): void
    {
        Setting::setValue('review_start_date', now()->subDay()->toDateString());
        Setting::setValue('review_end_date', now()->addDays(7)->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100174',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100174',
            'full_name' => 'BAYU SAPUTRA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);

        $allocation = $this->createItemAllocation($user, $satker, 'BARET LAPANGAN', $personnel);

        $this->actingAs($user)
            ->post(route('personil.testimoni.store'), [
                'allocation_id' => $allocation->id,
                'year' => $allocation->fiscal_year,
                'response_status' => ItemReview::STATUS_NOT_RECEIVED,
                'message' => 'Belum saya terima sampai sekarang.',
            ])
            ->assertRedirect(route('personil.testimoni.index', ['year' => $allocation->fiscal_year]))
            ->assertSessionHas('success_testimoni');

        $this->assertDatabaseHas('item_reviews', [
            'user_id' => $user->id,
            'kapor_item_id' => $allocation->kapor_item_id,
            'response_status' => ItemReview::STATUS_NOT_RECEIVED,
            'rating' => null,
        ]);

        $this->actingAs($user)
            ->post(route('personil.testimoni.store'), [
                'allocation_id' => $allocation->id,
                'year' => $allocation->fiscal_year,
                'response_status' => ItemReview::STATUS_REVIEWED,
                'rating' => 4,
                'message' => 'Item sudah diterima dan kondisinya baik.',
            ])
            ->assertRedirect(route('personil.testimoni.index', ['year' => $allocation->fiscal_year]))
            ->assertSessionHas('success_testimoni');

        $this->assertDatabaseHas('item_reviews', [
            'user_id' => $user->id,
            'kapor_item_id' => $allocation->kapor_item_id,
            'response_status' => ItemReview::STATUS_REVIEWED,
            'rating' => 4,
        ]);

        $this->assertSame(1, ItemReview::count());
    }

    public function test_personil_review_submit_is_blocked_outside_review_period(): void
    {
        Setting::setValue('review_start_date', now()->subMonths(2)->toDateString());
        Setting::setValue('review_end_date', now()->subDay()->toDateString());

        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100175',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $allocation = $this->createItemAllocation($user, $satker, 'BARET LAPANGAN');

        $this->actingAs($user)
            ->post(route('personil.testimoni.store'), [
                'allocation_id' => $allocation->id,
                'year' => $allocation->fiscal_year,
                'response_status' => ItemReview::STATUS_REVIEWED,
                'rating' => 5,
                'message' => 'Siap.',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseCount('item_reviews', 0);
    }

    public function test_personil_history_year_review_is_read_only(): void
    {
        Setting::setValue('review_start_date', now()->subDay()->toDateString());
        Setting::setValue('review_end_date', now()->addDays(7)->toDateString());
        Setting::setValue('fiscal_year', '2026');

        $satker = Satker::create([
            'name' => 'Polres Dompu',
            'code' => 'POLRES-DOMPU',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAGUS PRASETYO',
            'nrp_nip' => '76100199',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $personnel = Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100199',
            'full_name' => 'BAGUS PRASETYO',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'satker_id' => $satker->id,
            'is_active' => true,
        ]);

        $allocation2025 = $this->createItemAllocation($user, $satker, 'HELM TAKTIS', $personnel, 2025);

        $this->actingAs($user)
            ->get(route('personil.testimoni.index', ['year' => 2025]))
            ->assertOk()
            ->assertSeeText('TA 2025')
            ->assertSeeText('HELM TAKTIS')
            ->assertSeeText('Riwayat Tahun Sebelumnya')
            ->assertSeeText('tidak bisa mengirim atau mengubah data lagi');

        $this->actingAs($user)
            ->post(route('personil.testimoni.store'), [
                'allocation_id' => $allocation2025->id,
                'year' => 2025,
                'response_status' => ItemReview::STATUS_REVIEWED,
                'rating' => 5,
                'message' => 'Item tahun sebelumnya sudah saya terima.',
            ])
            ->assertRedirect(route('personil.testimoni.index', ['year' => 2025]))
            ->assertSessionHas('error_testimoni');

        $this->assertDatabaseMissing('item_reviews', [
            'user_id' => $user->id,
            'kapor_item_id' => $allocation2025->kapor_item_id,
            'fiscal_year' => 2025,
        ]);
    }

    public function test_personil_dashboard_renders_mobile_first_form_flow(): void
    {
        $satker = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 1,
        ]);

        $rank = Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'name' => 'BAYU SAPUTRA',
            'nrp_nip' => '76100171',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        Personnel::create([
            'user_id' => $user->id,
            'nrp' => '76100171',
            'full_name' => 'BAYU SAPUTRA',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'jabatan' => 'BANIT OPERASI',
            'bagian' => 'SIAGA',
            'satker_id' => $satker->id,
            'religion' => 'Islam',
            'is_active' => true,
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Data Kaporlap Personil');
        $response->assertSeeText('Data Personel');
        $response->assertSeeText('No. HP (WhatsApp)');
        $response->assertSeeText('Ukuran kaporlap');
    }

    private function createItemAllocation(User $user, Satker $satker, string $itemName, ?Personnel $personnel = null, ?int $fiscalYear = null): PersonnelItemAllocation
    {
        $fiscalYear ??= (int) Setting::getValue('fiscal_year', date('Y'));

        $budgetYear = BudgetYear::create([
            'year' => $fiscalYear,
            'name' => 'Tahun Anggaran '.$fiscalYear,
            'is_active' => true,
        ]);

        $package = BudgetPackage::create([
            'budget_year_id' => $budgetYear->id,
            'name' => 'Paket Review '.$itemName,
            'status' => 'finalized',
            'total_budget' => 0,
        ]);

        $kaporItem = KaporItem::create([
            'category' => 'Tutup_Kepala',
            'item_name' => $itemName,
            'price' => 100000,
            'unit' => 'PCS',
            'is_active' => true,
            'for_identifikasi' => true,
        ]);

        $packageItem = PackageItem::create([
            'budget_package_id' => $package->id,
            'kapor_item_id' => $kaporItem->id,
            'calculated_qty' => 1,
            'calculated_total' => 100000,
        ]);

        return PersonnelItemAllocation::create([
            'budget_package_id' => $package->id,
            'package_item_id' => $packageItem->id,
            'kapor_item_id' => $kaporItem->id,
            'user_id' => $user->id,
            'personnel_id' => $personnel?->id,
            'satker_id' => $satker->id,
            'fiscal_year' => $fiscalYear,
            'allocation_status' => 'eligible',
            'allocated_at' => now(),
            'nrp_snapshot' => $user->nrp_nip,
            'full_name_snapshot' => $personnel?->full_name ?? $user->name,
            'satker_name_snapshot' => $satker->name,
            'kapor_item_name_snapshot' => $itemName,
            'item_category_snapshot' => 'Tutup Kepala',
            'budget_package_name_snapshot' => $package->name,
        ]);
    }
}
