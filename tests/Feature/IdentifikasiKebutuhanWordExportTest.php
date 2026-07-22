<?php

namespace Tests\Feature;

use App\Models\IdentifikasiItem;
use App\Models\Kebutuhan;
use App\Models\KebutuhanItem;
use App\Models\Satker;
use App\Models\User;
use App\Services\IdentifikasiKebutuhanWordExportService;
use App\Services\KebutuhanExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use ZipArchive;

class IdentifikasiKebutuhanWordExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('superadmin');
    }

    public function test_service_generates_editable_word_document_with_export_content(): void
    {
        $this->seedExportData();

        $data = app(KebutuhanExportService::class)->build(2026, true);
        $data['signatorySettings'] = [
            'signatory_name' => 'Kombes Test',
            'signatory_rank' => 'KOMBES POL',
            'signatory_nrp' => '12345678',
            'signatory_title' => 'PEJABAT PEMBUAT KOMITMEN',
            'location' => 'Mataram',
            'organization_name' => 'KEPALA BIRO LOGISTIK POLDA NTB',
        ];

        $path = app(IdentifikasiKebutuhanWordExportService::class)->generate($data, true);
        $documentXml = $this->readDocumentXml($path);

        $this->assertStringContainsString('HASIL IDENTIFIKASI KEBUTUHAN KAPOR', $documentXml);
        $this->assertStringContainsString('PDL I-A &amp; I-B SABHARA', $documentXml);
        $this->assertStringContainsString('POLRES A', $documentXml);
        $this->assertStringContainsString('KOMBES TEST', $documentXml);
        $this->assertXmlStringEqualsXmlString($documentXml, $documentXml);

        @unlink($path);
    }

    public function test_admin_can_download_rekap_and_detail_word_exports(): void
    {
        $superadmin = $this->seedExportData();

        $this->actingAs($superadmin)
            ->get(route('admin.identifikasi-kebutuhan.export-word', ['year' => 2026]))
            ->assertOk()
            ->assertDownload('Identifikasi_Kebutuhan_Kapor_TA_2026.docx');

        $this->actingAs($superadmin)
            ->get(route('admin.identifikasi-kebutuhan.export-detail-word', ['year' => 2026]))
            ->assertOk()
            ->assertDownload('Detail_Identifikasi_Kebutuhan_Kapor_TA_2026.docx');
    }

    private function seedExportData(): User
    {
        $satker = Satker::create([
            'name' => 'POLRES A',
            'code' => 'POLRES-A',
            'sort_order' => 1,
        ]);

        Satker::create([
            'name' => 'POLRES B',
            'code' => 'POLRES-B',
            'sort_order' => 2,
        ]);

        $superadmin = User::factory()->create(['satker_id' => $satker->id]);
        $superadmin->assignRole('superadmin');

        $item = IdentifikasiItem::create([
            'item_name' => 'PDL I-A & I-B SABHARA',
            'category' => 'Tutup_Badan',
            'eligible_satker_count' => 2,
            'is_active' => true,
        ]);

        $kebutuhan = Kebutuhan::create([
            'satker_id' => $satker->id,
            'user_id' => $superadmin->id,
            'title' => 'Identifikasi POLRES A',
            'fiscal_year' => '2026',
            'status' => 'diajukan',
            'submitted_at' => now(),
        ]);

        KebutuhanItem::create([
            'kebutuhan_id' => $kebutuhan->id,
            'identifikasi_item_id' => $item->id,
            'quantity' => 1,
        ]);

        return $superadmin;
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
