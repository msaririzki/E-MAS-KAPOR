<?php

namespace Tests\Feature;

use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\Satker;
use App\Services\BudgetPackageSppmAssignmentService;
use App\Services\SppmWordExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;
use ZipArchive;

class SppmWordExportServiceTest extends TestCase
{
    public function test_it_generates_sppm_word_from_template_with_package_data(): void
    {
        $satker = Satker::create([
            'name' => 'ITWASDA',
            'code' => 'ITWASDA',
            'sort_order' => 1,
        ]);
        $secondSatker = Satker::create([
            'name' => 'POLRES LOMBOK TENGAH',
            'code' => 'POLRES-LOMBOK-TENGAH',
            'sort_order' => 32,
        ]);

        InvoiceSetting::query()->update([
            'signatory_name' => 'Kombes Test',
            'signatory_title' => 'KABAG LOGISTIK',
            'location' => 'Mataram',
            'organization_name' => 'KEPALA BIRO LOGISTIK POLDA NTB',
        ]);

        $assignmentService = Mockery::mock(BudgetPackageSppmAssignmentService::class);
        $assignmentService->shouldReceive('buildSppmSatkerData')
            ->once()
            ->andReturn([
                $satker->id => [
                    'satker' => $satker,
                    'items' => [
                        [
                            'item_name' => 'Rompi Keselamatan',
                            'unit' => 'PCS',
                            'price' => 250000,
                            'qty' => 12,
                            'total' => 3000000,
                        ],
                        [
                            'item_name' => 'Jaket Lapangan',
                            'unit' => 'PCS',
                            'price' => 175000,
                            'qty' => 3,
                            'total' => 525000,
                        ],
                    ],
                ],
                $secondSatker->id => [
                    'satker' => $secondSatker,
                    'items' => [
                        [
                            'item_name' => 'Rompi Keselamatan',
                            'unit' => 'PCS',
                            'price' => 250000,
                            'qty' => 4,
                            'total' => 1000000,
                        ],
                        [
                            'item_name' => 'Jaket Lapangan',
                            'unit' => 'PCS',
                            'price' => 175000,
                            'qty' => 6,
                            'total' => 1050000,
                        ],
                    ],
                ],
            ]);

        $service = new SppmWordExportService($assignmentService);

        $filePath = $service->generateForPackage(new BudgetPackage, [
            'sppm_number' => 'SPPM/001/III/LOG.5.16.1./2026/ROLOG',
            'sprin_number' => 'Sprin/123/III/LOG.5.16.1./2026',
            'sprin_date' => '19 MARET 2026',
            'date' => '19 MARET 2026',
        ]);

        $this->assertIsString($filePath);
        $documentXml = $this->readDocumentXml($filePath);

        $this->assertStringContainsString('Nomor: SPPM/001/III/LOG.5.16.1./2026/ROLOG', $documentXml);
        $this->assertStringContainsString('Diperintahkan untuk mengeluarkan kepada', $documentXml);
        $this->assertStringContainsString('ITWASDA POLDA NTB', $documentXml);
        $this->assertStringContainsString('POLRES LOMBOK TENGAH POLDA NTB', $documentXml);
        $this->assertStringContainsString('Sprin/123/III/LOG.5.16.1./2026', $documentXml);
        $this->assertStringContainsString('TANGGAL 19 MARET 2026', $documentXml);
        $this->assertStringContainsString('ROMPI KESELAMATAN', $documentXml);
        $this->assertStringContainsString('JAKET LAPANGAN', $documentXml);
        $this->assertStringContainsString('3.000.000', $documentXml);
        $this->assertStringContainsString('1.000.000', $documentXml);
        $this->assertStringContainsString('525.000', $documentXml);
        $this->assertStringContainsString('1.050.000', $documentXml);
        $this->assertStringContainsString('KABAG LOGISTIK', $documentXml);
        $this->assertStringContainsString('KOMBES TEST', $documentXml);
        $this->assertStringContainsString('<w:br w:type="page"/>', $documentXml);
        $this->assertStringContainsString('w:bottom="567"', $documentXml);
        $this->assertStringNotContainsString('TOPI LAPANGAN PAMEN', $documentXml);

        @unlink($filePath);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('satkers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('signatory_name')->default('');
            $table->string('signatory_rank')->default('');
            $table->string('signatory_nrp')->default('');
            $table->string('signatory_title')->default('PEJABAT PEMBUAT KOMITMEN');
            $table->string('location')->default('Mataram');
            $table->string('organization_name')->default('KEPALA BIRO LOGISTIK POLDA NTB');
            $table->string('header_title')->default('');
            $table->string('work_type')->default('');
            $table->timestamps();
        });

        InvoiceSetting::create();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
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
