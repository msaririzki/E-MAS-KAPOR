<?php

namespace Tests\Feature;

use App\Exports\PersonnelKeteranganExport;
use App\Exports\PersonnelKeteranganSheetExport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class PersonnelKeteranganExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_keterangan_dikelompokkan_per_satker_dalam_sheet_terpisah(): void
    {
        $satkerA = Satker::create([
            'name' => 'Biro Logistik',
            'code' => 'ROLOG',
            'sort_order' => 1,
        ]);

        $satkerB = Satker::create([
            'name' => 'Polres Bima',
            'code' => 'POLRES-BIMA',
            'sort_order' => 2,
        ]);

        $rankPolri = Rank::create([
            'name' => 'IPDA',
            'category' => 'PAMA',
            'sort_order' => 1,
        ]);

        $rankPns = Rank::create([
            'name' => 'Penata',
            'category' => 'PNS',
            'sort_order' => 2,
        ]);

        $userA = User::factory()->create([
            'name' => 'SOFIAN HADI',
            'nrp_nip' => '78071191',
            'satker_id' => $satkerA->id,
        ]);
        $userB = User::factory()->create([
            'name' => 'NI MADE',
            'nrp_nip' => '19871122',
            'satker_id' => $satkerB->id,
        ]);

        Personnel::create([
            'user_id' => $userA->id,
            'satker_id' => $satkerA->id,
            'rank_id' => $rankPolri->id,
            'full_name' => 'SOFIAN HADI',
            'nrp' => '78071191',
            'gender' => 'L',
            'religion' => 'Islam',
            'personnel_type' => 'Polri',
            'jabatan' => 'PAMIN ROLOG',
            'bagian' => 'SUBBAGRENMIN',
            'keterangan' => 'STAF',
            'is_active' => true,
        ]);

        Personnel::create([
            'user_id' => $userB->id,
            'satker_id' => $satkerB->id,
            'rank_id' => $rankPns->id,
            'full_name' => 'NI MADE',
            'nrp' => '19871122',
            'gender' => 'P',
            'religion' => 'Hindu',
            'personnel_type' => 'PNS',
            'jabatan' => 'ANALIS',
            'bagian' => 'BAG UMUM',
            'keterangan' => 'ASN',
            'is_active' => true,
        ]);

        $export = new PersonnelKeteranganExport;
        $sheets = $export->sheets();

        $this->assertCount(2, $sheets);
        $this->assertContainsOnlyInstancesOf(PersonnelKeteranganSheetExport::class, $sheets);
        $this->assertSame('Biro Logistik', $sheets[0]->title());
        $this->assertSame('Polres Bima', $sheets[1]->title());

        $headers = $sheets[0]->headings();
        $this->assertSame('tipe_personel', $headers[5]);
        $this->assertSame('keterangan_4', $headers[15]);

        $firstSheetRows = $sheets[0]->collection();
        $secondSheetRows = $sheets[1]->collection();

        $this->assertCount(1, $firstSheetRows);
        $this->assertCount(1, $secondSheetRows);
        $this->assertSame('Polri', $firstSheetRows[0]['tipe_personel']);
        $this->assertSame('PNS', $secondSheetRows[0]['tipe_personel']);
        $this->assertSame('Biro Logistik', $firstSheetRows[0]['satker']);
        $this->assertSame('Polres Bima', $secondSheetRows[0]['satker']);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'personnel-keterangan-');
        file_put_contents($temporaryPath, Excel::raw($export, ExcelWriter::XLSX));

        try {
            $worksheet = IOFactory::load($temporaryPath)->getSheet(0);

            $this->assertSame('A1:P2', $worksheet->getAutoFilter()->getRange());
            $this->assertTrue($worksheet->getProtection()->getSort());
            $this->assertTrue($worksheet->getProtection()->getAutoFilter());
            $this->assertSame('FF1D4ED8', $worksheet->getStyle('C1')->getFill()->getStartColor()->getARGB());
            $this->assertSame('FF15803D', $worksheet->getStyle('N1')->getFill()->getStartColor()->getARGB());
        } finally {
            @unlink($temporaryPath);
        }
    }
}
