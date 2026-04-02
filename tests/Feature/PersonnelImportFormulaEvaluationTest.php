<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Imports\PersonnelUpdateImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PersonnelImportFormulaEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_personnel_import_preview_uses_calculated_formula_values_for_sizes(): void
    {
        $satker = $this->createSatker();
        $this->createRank();

        $path = $this->createFormulaWorkbook();

        try {
            $import = new PersonnelImport($satker->id);
            $collection = Excel::toCollection($import, $path);
            $preview = $import->generatePreview($collection[0]);

            $this->assertCount(1, $preview);
            $this->assertSame('58', $preview[0]['sizes']['topi']);
            $this->assertSame('16.5', $preview[0]['sizes']['kemeja']);
            $this->assertSame('44', $preview[0]['sizes']['sepatu_dinas']);
            $this->assertSame('40', $preview[0]['sizes']['sabuk']);
        } finally {
            @unlink($path);
        }
    }

    public function test_personnel_update_preview_uses_calculated_formula_values_for_sizes(): void
    {
        $satker = $this->createSatker();
        $rank = $this->createRank();

        Personnel::create([
            'nrp' => '79071009',
            'full_name' => 'BENY ROYS BASIRANG',
            'rank_id' => $rank->id,
            'satker_id' => $satker->id,
            'personnel_type' => 'Polri',
            'gender' => 'L',
            'is_active' => true,
        ]);

        $path = $this->createFormulaWorkbook();

        try {
            $import = new PersonnelUpdateImport($satker->id);
            $collection = Excel::toCollection($import, $path);
            $preview = $import->generatePreview($collection[0]);

            $this->assertCount(1, $preview);
            $this->assertSame('58', $preview[0]['sizes']['topi']);
            $this->assertSame('16.5', $preview[0]['sizes']['kemeja']);
            $this->assertSame('44', $preview[0]['sizes']['sepatu_dinas']);
            $this->assertSame('40', $preview[0]['sizes']['sabuk']);
        } finally {
            @unlink($path);
        }
    }

    private function createSatker(): Satker
    {
        return Satker::create([
            'name' => 'DIT LANTAS',
            'code' => 'DITLANTAS',
            'sort_order' => 1,
        ]);
    }

    private function createRank(): Rank
    {
        return Rank::create([
            'name' => 'AIPTU',
            'category' => 'BINTARA',
            'sort_order' => 1,
        ]);
    }

    private function createFormulaWorkbook(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('Z1', 58);
        $sheet->setCellValue('AA1', 16.5);
        $sheet->setCellValue('AB1', 38);
        $sheet->setCellValue('AC1', 'EB');
        $sheet->setCellValue('AD1', 44);
        $sheet->setCellValue('AE1', 'M161');
        $sheet->setCellValue('AF1', 'L161');
        $sheet->setCellValue('AG1', 40);

        $sheet->setCellValue('A11', 1);
        $sheet->setCellValue('B11', 'BENY ROYS BASIRANG');
        $sheet->setCellValue('C11', 'AIPTU');
        $sheet->setCellValue('D11', 'BINTARA');
        $sheet->setCellValue('E11', '79071009');
        $sheet->setCellValue('F11', 'BANIT II SILAKA');
        $sheet->setCellValue('G11', 'SUBDIT REGIDENT');
        $sheet->setCellValue('H11', 'L');
        $sheet->setCellValue('I11', '=Z1');
        $sheet->setCellValue('J11', '=AA1');
        $sheet->setCellValue('K11', '=AB1');
        $sheet->setCellValue('L11', '=AC1');
        $sheet->setCellValue('M11', '=AD1');
        $sheet->setCellValue('N11', '=AE1');
        $sheet->setCellValue('O11', '=AF1');
        $sheet->setCellValue('P11', '=AG1');
        $sheet->setCellValue('Q11', '');
        $sheet->setCellValue('R11', 'STAF / LANTAS');

        $tempPath = tempnam(sys_get_temp_dir(), 'kapor-formula-');
        $path = $tempPath.'.xlsx';

        @unlink($tempPath);

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
