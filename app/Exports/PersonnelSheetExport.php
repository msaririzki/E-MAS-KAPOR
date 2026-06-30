<?php

namespace App\Exports;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PersonnelSheetExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    public const MODE_UPDATE = 'update';

    public const MODE_MONITORING = 'monitoring';

    private const SIZE_KEYS = [
        'topi',
        'kemeja',
        'celana',
        'olahraga',
        'sepatu_dinas',
        'sepatu_olahraga',
        'jaket',
        'sabuk',
        'jilbab',
    ];

    public function __construct(
        protected Collection $personnels,
        protected string $satkerName,
        protected string $sheetTitle,
        protected array $signatorySettings = [],
        protected string $mode = self::MODE_UPDATE,
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function collection(): Collection
    {
        return $this->mode === self::MODE_MONITORING
            ? $this->monitoringRows()
            : $this->updateTemplateRows();
    }

    private function updateTemplateRows(): Collection
    {
        $rows = collect();
        $no = 1;

        foreach ($this->personnels as $personnel) {
            $genderExcel = $personnel->gender === 'P' ? 'W' : 'P';

            $rows->push([
                $no++,
                $personnel->full_name ?? '',
                $personnel->rank->name ?? '',
                $personnel->golongan ?? ($personnel->rank->category ?? ''),
                "\t".($personnel->nrp ?? ''),
                $personnel->jabatan ?? '',
                $personnel->bagian ?? '',
                $genderExcel,
                $personnel->religion ?? '',
                $personnel->keterangan ?? '',
            ]);
        }

        return $rows;
    }

    private function monitoringRows(): Collection
    {
        $rows = collect();
        $no = 1;

        foreach ($this->personnels as $personnel) {
            $genderExcel = $personnel->gender === 'P' ? 'W' : ($personnel->gender === 'L' ? 'P' : '-');
            $kaporSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];

            $row = [
                $no++,
                strtoupper($personnel->full_name ?? ''),
                strtoupper($personnel->rank->name ?? '-'),
                $personnel->golongan ?? ($personnel->rank->category ?? '-'),
                "\t".($personnel->nrp ?? ''),
                strtoupper($personnel->jabatan ?? '-'),
                strtoupper($personnel->bagian ?? '-'),
                $genderExcel,
            ];

            foreach (self::SIZE_KEYS as $key) {
                $value = trim($kaporSizes[$key] ?? '');
                $row[] = ($value !== '' && $value !== '-' && $value !== '0') ? $value : '-';
            }

            $row[] = '';

            $rows->push($row);
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function headings(): array
    {
        return $this->mode === self::MODE_MONITORING
            ? $this->monitoringHeadings()
            : $this->updateTemplateHeadings();
    }

    private function updateTemplateHeadings(): array
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['DATA PERSONEL SATKER '.strtoupper($this->satkerName)],
            ['TEMPLATE UPDATE JABATAN, BAG/FUNGSI, DAN KETERANGAN TA. '.$fiscalYear],
            [''],
            ['NO', 'NAMA', 'PANGKAT', 'GOLONGAN', 'NRP/NIP', 'JABATAN', 'BAG/FUNGSI', 'JENIS KELAMIN P / W', 'AGAMA', 'KETERANGAN'],
            array_fill(0, 10, ' '),
            array_fill(0, 10, ' '),
        ];
    }

    private function monitoringHeadings(): array
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['MONITORING DATA PERSONEL & UKURAN KAPOR'],
            ['SATKER '.strtoupper($this->satkerName).' - TA. '.$fiscalYear],
            [''],
            ['NO', 'NAMA LENGKAP', 'PANGKAT', 'GOL', 'NRP/NIP', 'JABATAN', 'BAG/FUNGSI', 'JK',
                'U K U R A N', '', '', '', '', '', '', '', '', 'KET'],
            ['', '', '', '', '', '', '', '',
                'TUTUP KEPALA', 'TUTUP BADAN', '', '', 'TUTUP KAKI', '', 'JAKET', 'SABUK', 'JILBAB', ''],
            ['', '', '', '', '', '', '', '',
                '', 'KEMEJA', 'CELANA/ ROK', 'T-SHIRT OLHRG', 'DINAS', 'OLHRG', '', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true, 'underline' => true]],
            5 => ['font' => ['bold' => true, 'size' => 12]],
            6 => ['font' => ['bold' => true, 'size' => 12, 'underline' => true]],
            8 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
            9 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
            10 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = $sheet->getHighestRow();

                if ($this->mode === self::MODE_MONITORING) {
                    $this->applyMonitoringSheet($sheet, $lastDataRow);

                    return;
                }

                $this->applyUpdateTemplateSheet($sheet, $lastDataRow);
            },
        ];
    }

    private function applyUpdateTemplateSheet(Worksheet $sheet, int $lastDataRow): void
    {
        $this->normalizeDataRows($sheet, $lastDataRow);

        for ($row = 11; $row <= $lastDataRow; $row++) {
            $cell = $sheet->getCell('E'.$row);
            $rawValue = ltrim((string) $cell->getValue(), "\t");
            $cell->setValueExplicit($rawValue, DataType::TYPE_STRING);
        }

        $sheet->getStyle('A1:J6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        $sheet->mergeCells('A5:J5');
        $sheet->mergeCells('A6:J6');

        foreach (range('A', 'J') as $column) {
            $sheet->mergeCells($column.'8:'.$column.'10');
        }

        $sheet->getStyle('A8:J10')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        if ($lastDataRow >= 11) {
            $sheet->getStyle('A11:J'.$lastDataRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'CCCCCC'],
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);
        }

        $sheet->getStyle('A8:J10')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F2F2F2');

        $sheet->getRowDimension(8)->setRowHeight(20);
        $sheet->getRowDimension(9)->setRowHeight(20);
        $sheet->getRowDimension(10)->setRowHeight(24);
        $sheet->freezePane('A11');

        if ($lastDataRow >= 11) {
            $sheet->getStyle('A11:A'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H11:I'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E11:E'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle('A11:J'.$lastDataRow)
                ->getProtection()
                ->setLocked(Protection::PROTECTION_PROTECTED);

            foreach (['F', 'G', 'J'] as $editableColumn) {
                $sheet->getStyle($editableColumn.'11:'.$editableColumn.$lastDataRow)
                    ->getProtection()
                    ->setLocked(Protection::PROTECTION_UNPROTECTED);
            }
        }

        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setSort(false);
        $sheet->getProtection()->setInsertRows(false);
        $sheet->getProtection()->setFormatCells(false);
        $sheet->getProtection()->setPassword('EMAS-KAPOR');
    }

    private function applyMonitoringSheet(Worksheet $sheet, int $lastDataRow): void
    {
        $this->normalizeDataRows($sheet, $lastDataRow);

        for ($row = 11; $row <= $lastDataRow; $row++) {
            $cell = $sheet->getCell('E'.$row);
            $rawValue = ltrim((string) $cell->getValue(), "\t");
            $cell->setValueExplicit($rawValue, DataType::TYPE_STRING);
        }

        $sheet->getStyle('A1:R6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        $sheet->mergeCells('A5:R5');
        $sheet->mergeCells('A6:R6');

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->mergeCells("{$col}8:{$col}10");
        }

        $sheet->mergeCells('I8:Q8');
        $sheet->mergeCells('I9:I10');
        $sheet->mergeCells('J9:L9');
        $sheet->mergeCells('M9:N9');
        $sheet->mergeCells('O9:O10');
        $sheet->mergeCells('P9:P10');
        $sheet->mergeCells('Q9:Q10');
        $sheet->mergeCells('R8:R10');

        $headerRange = 'A8:R10';
        $sheet->getStyle($headerRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F2F2F2');
        $sheet->getStyle('I8:Q8')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F1F5F9');
        $sheet->getStyle('I9:Q9')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F8FAFC');
        $sheet->getStyle('J10:N10')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('F1F5F9');

        if ($lastDataRow >= 11) {
            $sheet->getStyle('A11:R'.$lastDataRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'CCCCCC'],
                    ],
                    'outline' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);

            $sheet->getStyle('A11:A'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D11:D'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H11:H'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E11:E'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('I11:Q'.$lastDataRow)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getRowDimension(8)->setRowHeight(20);
        $sheet->getRowDimension(9)->setRowHeight(20);
        $sheet->getRowDimension(10)->setRowHeight(24);
        $sheet->freezePane('A11');

        if (! empty($this->signatorySettings)) {
            $this->renderSignatory($sheet, $lastDataRow);
        }
    }

    private function normalizeDataRows(Worksheet $sheet, int $lastDataRow): void
    {
        $sequence = 1;

        for ($row = 11; $row <= $lastDataRow; $row++) {
            $rowDimension = $sheet->getRowDimension($row);
            $rowDimension->setVisible(true);
            $rowDimension->setZeroHeight(false);
            $rowDimension->setOutlineLevel(0);
            $rowDimension->setCollapsed(false);

            $sheet->setCellValue('A'.$row, $sequence++);
        }
    }

    private function renderSignatory(Worksheet $sheet, int $lastDataRow): void
    {
        $location = $this->signatorySettings['location'] ?? 'Mataram';
        $orgName = strtoupper($this->signatorySettings['organization_name'] ?? '');
        $jabatan = strtoupper($this->signatorySettings['signatory_title'] ?? 'KEPALA..........................');
        $nama = strtoupper($this->signatorySettings['signatory_name'] ?? '..........................................');
        $nrp = $this->signatorySettings['signatory_nrp'] ?? '.............................';

        $startRow = $lastDataRow + 2;
        $sigCol = 'N';
        $endCol = 'R';

        $sheet->setCellValue($sigCol.$startRow, $location.', '.now()->translatedFormat('d F Y'));
        $sheet->mergeCells("{$sigCol}{$startRow}:{$endCol}{$startRow}");
        $sheet->getStyle($sigCol.$startRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currentRow = $startRow + 1;
        if ($orgName !== '') {
            $sheet->setCellValue($sigCol.$currentRow, $orgName);
            $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
            $sheet->getStyle($sigCol.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow++;
        }

        $sheet->setCellValue($sigCol.$currentRow, $jabatan);
        $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
        $sheet->getStyle($sigCol.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $currentRow += 4;
        $sheet->setCellValue($sigCol.$currentRow, $nama);
        $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
        $sheet->getStyle($sigCol.$currentRow)->applyFromArray([
            'font' => ['bold' => true, 'underline' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $currentRow++;
        $sheet->setCellValue($sigCol.$currentRow, 'NRP/NIP. '.$nrp);
        $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
        $sheet->getStyle($sigCol.$currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($sigCol.$currentRow)->getFont()->setSize(10);
    }
}
