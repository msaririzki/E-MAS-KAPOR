<?php

namespace Tests\Feature;

use App\Imports\PersonnelImport;
use App\Imports\PersonnelUpdateImport;
use App\Models\Satker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PersonnelImportSatkerSpecificRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preview_allows_polda_ntb_special_ranks_as_is(): void
    {
        $satker = $this->createPoldaNtbSatker();
        $path = $this->createSpecialRankWorkbook();

        try {
            $import = new PersonnelImport($satker->id);
            $collection = Excel::toCollection($import, $path);
            $preview = $import->generatePreview($collection[0]);

            $this->assertCount(2, $preview);
            $this->assertSame('BA BRIMOB', $preview[0]['rank_name']);
            $this->assertSame('ok', $preview[0]['status']);
            $this->assertSame('TAMTAMA POLRI', $preview[1]['rank_name']);
            $this->assertSame('ok', $preview[1]['status']);

            $this->assertDatabaseHas('ranks', [
                'name' => 'BA BRIMOB',
                'category' => 'BINTARA',
            ]);
            $this->assertDatabaseHas('ranks', [
                'name' => 'TAMTAMA POLRI',
                'category' => 'BINTARA',
            ]);
        } finally {
            @unlink($path);
        }
    }

    public function test_update_preview_allows_polda_ntb_special_ranks_as_is(): void
    {
        $satker = $this->createPoldaNtbSatker();
        $path = $this->createSpecialRankWorkbook();

        try {
            $import = new PersonnelUpdateImport($satker->id);
            $collection = Excel::toCollection($import, $path);
            $preview = $import->generatePreview($collection[0]);

            $this->assertCount(2, $preview);
            $this->assertSame('BA BRIMOB', $preview[0]['rank_name']);
            $this->assertNotSame('error', $preview[0]['status']);
            $this->assertSame('TAMTAMA POLRI', $preview[1]['rank_name']);
            $this->assertNotSame('error', $preview[1]['status']);
        } finally {
            @unlink($path);
        }
    }

    private function createPoldaNtbSatker(): Satker
    {
        return Satker::create([
            'name' => 'Polda NTB',
            'code' => 'POLDA-NTB',
            'sort_order' => 1,
        ]);
    }

    private function createSpecialRankWorkbook(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A11', 1);
        $sheet->setCellValue('B11', 'KOMANG ADITYA SAPUTRA, S.H.');
        $sheet->setCellValue('C11', 'BA BRIMOB');
        $sheet->setCellValue('D11', 'BINTARA');
        $sheet->setCellValue('E11', '79110045');
        $sheet->setCellValue('F11', 'POLDA NTB');
        $sheet->setCellValue('H11', 'P');
        $sheet->setCellValue('I11', '56');
        $sheet->setCellValue('J11', '15');
        $sheet->setCellValue('K11', '34');
        $sheet->setCellValue('L11', 'EB');
        $sheet->setCellValue('M11', '40');
        $sheet->setCellValue('N11', '40');
        $sheet->setCellValue('O11', 'SD');
        $sheet->setCellValue('P11', '48');
        $sheet->setCellValue('R11', 'STAF');

        $sheet->setCellValue('A12', 2);
        $sheet->setCellValue('B12', 'RANGGA KURNIAWAN');
        $sheet->setCellValue('C12', 'TAMTAMA POLRI');
        $sheet->setCellValue('D12', 'BINTARA');
        $sheet->setCellValue('E12', '1101653');
        $sheet->setCellValue('F12', 'POLDA NTB');
        $sheet->setCellValue('H12', 'P');
        $sheet->setCellValue('I12', '57');
        $sheet->setCellValue('J12', '15');
        $sheet->setCellValue('K12', '35');
        $sheet->setCellValue('L12', 'B');
        $sheet->setCellValue('M12', '42');
        $sheet->setCellValue('N12', '41');
        $sheet->setCellValue('O12', 'B');
        $sheet->setCellValue('P12', '46');
        $sheet->setCellValue('R12', 'STAF');

        $tempPath = tempnam(sys_get_temp_dir(), 'kapor-rank-');
        $path = $tempPath.'.xlsx';

        @unlink($tempPath);

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
