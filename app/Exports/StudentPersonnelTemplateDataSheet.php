<?php

namespace App\Exports;

use App\Models\Rank;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StudentPersonnelTemplateDataSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithTitle
{
    private const FIRST_DATA_ROW = 12;

    private const TEMPLATE_ROWS = 1000;

    public function title(): string
    {
        return 'Data Siswa';
    }

    public function collection(): Collection
    {
        return collect(range(1, self::TEMPLATE_ROWS))
            ->map(fn (int $number): array => array_merge([$number], array_fill(0, 17, '')));
    }

    public function headings(): array
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['TEMPLATE UNGGAH DATA SISWA LENGKAP'],
            ['DATA PERSONEL DAN UKURAN KAPOR T.A. '.$fiscalYear],
            [''],
            [
                'NO', 'NAMA', 'PANGKAT', 'GOLONGAN', 'NRP', 'JABATAN', 'BAG/FUNGSI',
                'JENIS KELAMIN P / W', 'U K U R A N', '', '', '', '', '', '', '', '', 'KETERANGAN',
            ],
            [
                '', '', '', '', '', '', '', '', 'TUTUP KEPALA', 'TUTUP BADAN', '', '',
                'TUTUP KAKI', '', 'JAKET', 'SABUK', 'JILBAB', '',
            ],
            [
                '', '', '', '', '', '', '', '', '', 'KEMEJA', 'CELANA / ROK',
                'T.SHIRT/ OLAHRAGA', 'SEPATU', '', '', '', '', '',
            ],
            [
                '', '', '', '', '', '', '', '', '', '', '', '', 'DINAS', 'OLAHRAGA', '', '', '', '',
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
            'I:Q' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = self::FIRST_DATA_ROW + self::TEMPLATE_ROWS - 1;

                $sheet->mergeCells('A1:C1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('A5:R5');
                $sheet->mergeCells('A6:R6');

                foreach (range('A', 'H') as $column) {
                    $sheet->mergeCells($column.'8:'.$column.'11');
                }

                $sheet->mergeCells('I8:Q8');
                $sheet->mergeCells('I9:I11');
                $sheet->mergeCells('J9:L9');
                $sheet->mergeCells('J10:J11');
                $sheet->mergeCells('K10:K11');
                $sheet->mergeCells('L10:L11');
                $sheet->mergeCells('M9:N9');
                $sheet->mergeCells('M10:N10');
                $sheet->mergeCells('O9:O11');
                $sheet->mergeCells('P9:P11');
                $sheet->mergeCells('Q9:Q11');
                $sheet->mergeCells('R8:R11');

                $sheet->getStyle('A1:R6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A1:C3')->getFont()->setBold(true);
                $sheet->getStyle('A3:C3')->getFont()->setUnderline(true);
                $sheet->getStyle('A5:R6')->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle('A8:R11')->getFont()->setBold(true);
                $sheet->getStyle('A8:R11')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle('A8:R11')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('E5E7EB');
                $sheet->getStyle('I8:Q8')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FEE2E2');

                $sheet->getStyle('A8:R'.$lastRow)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setARGB('CBD5E1');
                $sheet->getStyle('A'.self::FIRST_DATA_ROW.':A'.$lastRow)
                    ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F8FAFC');
                $sheet->getStyle('A'.self::FIRST_DATA_ROW.':A'.$lastRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('D'.self::FIRST_DATA_ROW.':H'.$lastRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('I'.self::FIRST_DATA_ROW.':Q'.$lastRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                for ($row = self::FIRST_DATA_ROW; $row <= $lastRow; $row++) {
                    $sheet->getCell('E'.$row)->setDataType(DataType::TYPE_STRING);
                }

                $rankLastRow = max(2, Rank::query()->count() + 1);
                $this->addFormulaValidation(
                    $sheet,
                    'C'.self::FIRST_DATA_ROW.':C'.$lastRow,
                    'INDIRECT("\'Referensi Pangkat\'!$A$2:$A$'.$rankLastRow.'")',
                    'Pilih pangkat dari sheet Referensi Pangkat.',
                );
                $this->addListValidation($sheet, 'H'.self::FIRST_DATA_ROW.':H'.$lastRow, 'P,W', 'Gunakan P untuk pria atau W untuk wanita.');
                $sheet->freezePane('B'.self::FIRST_DATA_ROW);
                $sheet->setSelectedCell('B'.self::FIRST_DATA_ROW);
                $sheet->getRowDimension(8)->setRowHeight(24);
                $sheet->getRowDimension(9)->setRowHeight(22);
                $sheet->getRowDimension(10)->setRowHeight(30);
                $sheet->getRowDimension(11)->setRowHeight(24);

                $widths = [
                    'A' => 7, 'B' => 34, 'C' => 23, 'D' => 13, 'E' => 22, 'F' => 30,
                    'G' => 22, 'H' => 18, 'I' => 16, 'J' => 14, 'K' => 16, 'L' => 20,
                    'M' => 14, 'N' => 14, 'O' => 14, 'P' => 14, 'Q' => 14, 'R' => 26,
                ];
                foreach ($widths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->setAutoFilter('A11:R'.$lastRow);
            },
        ];
    }

    private function addListValidation($sheet, string $range, string $values, string $message, bool $allowBlank = false): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank($allowBlank);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Pilihan tidak valid');
        $validation->setError($message);
        $validation->setFormula1('"'.$values.'"');
        $sheet->setDataValidation($range, $validation);
    }

    private function addFormulaValidation($sheet, string $range, string $formula, string $message): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Pangkat tidak valid');
        $validation->setError($message);
        $validation->setFormula1($formula);
        $sheet->setDataValidation($range, $validation);
    }
}
