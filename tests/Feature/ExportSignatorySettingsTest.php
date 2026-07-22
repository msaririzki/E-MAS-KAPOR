<?php

namespace Tests\Feature;

use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use App\Services\ExportSignatorySettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportSignatorySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
        Role::findOrCreate('admin_satker');
    }

    public function test_superadmin_can_update_global_export_signatory_settings(): void
    {
        $satker = $this->createSatker('POLDA-NTB', 'Polda NTB', 1);
        $superadmin = User::factory()->create(['satker_id' => $satker->id]);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->put(route('superadmin.settings.signatory.update'), [
            'signatory_name' => 'Kombes Pol Ahmad',
            'signatory_rank' => 'KOMBES POL',
            'signatory_nrp' => '12345678',
            'signatory_title' => 'PEJABAT PEMBUAT KOMITMEN',
            'location' => 'Mataram',
            'organization_name' => 'KEPALA BIRO LOGISTIK POLDA NTB',
        ]);

        $response->assertRedirect();
        $this->assertSame('Kombes Pol Ahmad', Setting::getValue('export_signatory.global.signatory_name'));

        $this->assertDatabaseHas('invoice_settings', [
            'signatory_name' => 'Kombes Pol Ahmad',
            'signatory_nrp' => '12345678',
            'location' => 'Mataram',
        ]);
    }

    public function test_superadmin_settings_page_displays_signatory_form(): void
    {
        $satker = $this->createSatker('POLDA-NTB', 'Polda NTB', 1);
        $superadmin = User::factory()->create(['satker_id' => $satker->id]);
        $superadmin->assignRole('superadmin');

        $response = $this->actingAs($superadmin)->get(route('superadmin.settings.index'));

        $response->assertOk();
        $response->assertSeeText('Penanda Tangan Dokumen');
    }

    public function test_admin_satker_settings_override_global_and_fallback_remains_available(): void
    {
        $polda = $this->createSatker('POLDA-NTB', 'Polda NTB', 1);
        $satkerA = $this->createSatker('RES-A', 'Polres A', 2);
        $satkerB = $this->createSatker('RES-B', 'Polres B', 3);

        $superadmin = User::factory()->create(['satker_id' => $polda->id]);
        $superadmin->assignRole('superadmin');

        $adminSatkerA = User::factory()->create(['satker_id' => $satkerA->id]);
        $adminSatkerA->assignRole('admin_satker');

        $adminSatkerB = User::factory()->create(['satker_id' => $satkerB->id]);
        $adminSatkerB->assignRole('admin_satker');

        Setting::setValue('export_signatory.global.signatory_title', 'KASUBBAG RENMIN');
        Setting::setValue('export_signatory.global.signatory_name', 'GLOBAL NAME');
        Setting::setValue('export_signatory.global.location', 'Mataram');
        Setting::setValue('export_signatory.global.organization_name', 'KEPALA BIRO LOGISTIK POLDA NTB');

        $response = $this->actingAs($adminSatkerA)->put(route('admin-satker.settings.signatory.update'), [
            'signatory_name' => 'AKBP SATKER A',
            'signatory_title' => 'KABAG LOG POLRES A',
            'signatory_rank' => 'AKBP',
            'signatory_nrp' => '99887766',
            'location' => 'Lombok Barat',
            'organization_name' => 'KAPOLRES A',
        ]);

        $response->assertRedirect();
        $this->assertSame('AKBP SATKER A', Setting::getValue("export_signatory.satker.{$satkerA->id}.signatory_name"));

        $service = app(ExportSignatorySettingService::class);
        $resolvedA = $service->resolveForUser($adminSatkerA);
        $resolvedB = $service->resolveForUser($adminSatkerB);

        $this->assertSame('AKBP SATKER A', $resolvedA['signatory_name']);
        $this->assertSame('KABAG LOG POLRES A', $resolvedA['signatory_title']);
        $this->assertSame('GLOBAL NAME', $resolvedB['signatory_name']);
        $this->assertSame('.............................', $resolvedB['signatory_title']);
    }

    public function test_admin_satker_settings_page_displays_signatory_form(): void
    {
        $satker = $this->createSatker('RES-A', 'Polres A', 1);
        $adminSatker = User::factory()->create(['satker_id' => $satker->id]);
        $adminSatker->assignRole('admin_satker');

        $response = $this->actingAs($adminSatker)->get(route('admin-satker.settings'));

        $response->assertOk();
        $response->assertSeeText('Penanda Tangan Dokumen');
        $response->assertSee('value="............................."', false);
    }

    private function createSatker(string $code, string $name, int $sortOrder): Satker
    {
        return Satker::create([
            'code' => $code,
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);
    }
}
