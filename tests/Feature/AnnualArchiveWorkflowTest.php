<?php

namespace Tests\Feature;

use App\Models\AnnualArchive;
use App\Models\BudgetYear;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnnualArchiveWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
        Role::findOrCreate('personil');
        Storage::fake('local');
    }

    public function test_next_year_transition_generates_annual_archive_files(): void
    {
        Setting::setValue('fiscal_year', '2026');
        Setting::setValue('is_system_locked', 'false');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

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

        BudgetYear::create([
            'year' => 2026,
            'name' => 'Tahun Anggaran 2026',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'EGAS DOSANTOS',
            'nrp_nip' => '76100151',
            'satker_id' => $satker->id,
        ]);
        $user->assignRole('personil');

        $user->personnel()->create([
            'nrp' => '76100151',
            'full_name' => 'EGAS DOSANTOS',
            'gender' => 'L',
            'personnel_type' => 'Polri',
            'rank_id' => $rank->id,
            'golongan' => 'BINTARA',
            'satker_id' => $satker->id,
            'religion' => 'Katolik',
            'is_active' => true,
            'verification_status' => 'approved',
            'kapor_sizes' => ['topi' => '57'],
        ]);

        $this->actingAs($superadmin)->post(route('superadmin.settings.next-year'))->assertRedirect();

        $this->assertDatabaseHas('annual_archives', [
            'fiscal_year' => 2026,
            'format' => 'xlsx',
        ]);

        $this->assertDatabaseHas('annual_archives', [
            'fiscal_year' => 2026,
            'format' => 'pdf',
        ]);

        Storage::disk('local')->assertExists('annual-archives/2026/arsip_final_tahunan_2026.xlsx');
        Storage::disk('local')->assertExists('annual-archives/2026/arsip_final_tahunan_2026.pdf');

        $archive = AnnualArchive::query()
            ->where('fiscal_year', 2026)
            ->where('format', 'xlsx')
            ->firstOrFail();

        $this->assertSame(1, $archive->metadata['total_personnel']);
        $this->assertSame(0, $archive->metadata['submitted_personnel']);
    }

    public function test_superadmin_can_view_and_download_annual_archives_page(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        Storage::disk('local')->put('annual-archives/2026/arsip_final_tahunan_2026.pdf', 'dummy pdf');

        $archive = AnnualArchive::create([
            'fiscal_year' => 2026,
            'format' => 'pdf',
            'title' => 'Arsip Final Tahunan 2026',
            'file_name' => 'arsip_final_tahunan_2026.pdf',
            'file_path' => 'annual-archives/2026/arsip_final_tahunan_2026.pdf',
            'disk' => 'local',
            'generated_by' => $superadmin->id,
            'generated_at' => now(),
            'metadata' => ['total_personnel' => 1],
        ]);

        $this->actingAs($superadmin)
            ->get(route('admin.reports.annual-archives'))
            ->assertOk()
            ->assertSeeText('Arsip Final Tahunan')
            ->assertSeeText('TA 2026');

        $this->actingAs($superadmin)
            ->get(route('admin.reports.annual-archives.download', $archive))
            ->assertOk();
    }

    public function test_superadmin_settings_history_displays_archive_and_submission_columns(): void
    {
        Setting::setValue('fiscal_year', '2026');

        $superadmin = User::factory()->create();
        $superadmin->assignRole('superadmin');

        AnnualArchive::create([
            'fiscal_year' => 2025,
            'format' => 'pdf',
            'title' => 'Arsip Final Tahunan 2025',
            'file_name' => 'arsip_final_tahunan_2025.pdf',
            'file_path' => 'annual-archives/2025/arsip_final_tahunan_2025.pdf',
            'disk' => 'local',
            'generated_by' => $superadmin->id,
            'generated_at' => now(),
            'metadata' => ['total_personnel' => 1],
        ]);

        $this->actingAs($superadmin)
            ->get(route('superadmin.settings.index'))
            ->assertOk()
            ->assertSeeText('Personel Final')
            ->assertSeeText('Lengkap Ukuran')
            ->assertSeeText('Arsip Final')
            ->assertSeeText('Snapshot arsip final')
            ->assertSeeText('Terkunci')
            ->assertSeeText('TA 2025');
    }
}
