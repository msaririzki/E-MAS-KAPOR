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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet tunggal — format Excel monitoring yang konsisten dengan format PDF/cetak.
 *
 * Kolom: NO | NAMA LENGKAP | PANGKAT | GOL | NRP/NIP | JABATAN | BAG/FUNGSI | JK
 *        | (9 kolom UKURAN) | KET
 *
 * Total 18 kolom  (A–R).
 */
class PersonnelSheetExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected Collection $personnels;

    protected string $satkerName;

    protected string $sheetTitle;

    /** @var array<string, string> */
    protected array $signatorySettings;

    /**
     * JSON key → short label used in the PDF/print view.
     */
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

    public function __construct(Collection $personnels, string $satkerName, string $sheetTitle, array $signatorySettings = [])
    {
        $this->personnels = $personnels;
        $this->satkerName = $satkerName;
        $this->sheetTitle = $sheetTitle;
        $this->signatorySettings = $signatorySettings;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function collection(): Collection
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

            // 9 size columns – same order as PDF
            foreach (self::SIZE_KEYS as $key) {
                $v = trim($kaporSizes[$key] ?? '');
                $row[] = ($v !== '' && $v !== '-' && $v !== '0') ? $v : '-';
            }

            // Keterangan (dikosongkan)
            $row[] = '';

            $rows->push($row);
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // NRP/NIP
        ];
    }

    /**
     * Headings — 3 header rows that mirror the PDF print layout.
     *
     * Row 1-6: KOP
     * Row 7: spacer
     * Row 8: header row 1  (NO, NAMA LENGKAP … , UKURAN (colspan 9), KET)
     * Row 9: header row 2  (TUTUP KEPALA, TUTUP BADAN (colspan 3), TUTUP KAKI (colspan 2), JAKET, SABUK, JILBAB)
     * Row 10: header row 3 (KEMEJA, CELANA/ROK, T-SHIRT OLHRG, DINAS, OLHRG)
     */
    public function headings(): array
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['MONITORING DATA PERSONEL & UKURAN KAPOR'],
            ['SATKER '.strtoupper($this->satkerName).' — TA. '.$fiscalYear],
            [''],
            // Header row 1: 8 fixed cols + 9 size placeholder + KET = 18 cols
            ['NO', 'NAMA LENGKAP', 'PANGKAT', 'GOL', 'NRP/NIP', 'JABATAN', 'BAG/FUNGSI', 'JK',
                'U K U R A N', '', '', '', '', '', '', '', '', 'KET'],
            // Header row 2: empty for fixed cols, then sub-headers
            ['', '', '', '', '', '', '', '',
                'TUTUP KEPALA', 'TUTUP BADAN', '', '', 'TUTUP KAKI', '', 'JAKET', 'SABUK', 'JILBAB', ''],
            // Header row 3: empty for fixed cols, then detail labels
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

                // ── Fix NRP/NIP (col E) so leading zeros are preserved ──
                for ($row = 11; $row <= $lastDataRow; $row++) {
                    $cell = $sheet->getCell('E'.$row);
                    $rawValue = ltrim((string) $cell->getValue(), "\t");
                    $cell->setValueExplicit($rawValue, DataType::TYPE_STRING);
                }

                // ── KOP merges (rows 1–6) ──
                $sheet->getStyle('A1:R6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells('A1:C1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('A5:R5');
                $sheet->mergeCells('A6:R6');

                // ── Header merges (rows 8–10) matching PDF structure ──

                // Fixed columns span all 3 header rows
                foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
                    $sheet->mergeCells("{$col}8:{$col}10");
                }

                // "U K U R A N" spans I8:Q8 (9 cols)
                $sheet->mergeCells('I8:Q8');

                // KET spans R8:R10
                $sheet->mergeCells('R8:R10');

                // Row 9 sub-headers:
                // TUTUP KEPALA: I9:I10 (rowspan 2)
                $sheet->mergeCells('I9:I10');
                // TUTUP BADAN: J9:L9 (colspan 3)
                $sheet->mergeCells('J9:L9');
                // TUTUP KAKI: M9:N9 (colspan 2)
                $sheet->mergeCells('M9:N9');
                // JAKET: O9:O10
                $sheet->mergeCells('O9:O10');
                // SABUK: P9:P10
                $sheet->mergeCells('P9:P10');
                // JILBAB: Q9:Q10
                $sheet->mergeCells('Q9:Q10');

                // ── Header styling ──
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

                // Size columns header light teal
                $sheet->getStyle('I8:Q8')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F1F5F9');

                $sheet->getStyle('I9:Q9')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F8FAFC');

                $sheet->getStyle('J10:N10')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F1F5F9');

                // ── Data rows styling ──
                if ($lastDataRow >= 11) {
                    $dataRange = 'A11:R'.$lastDataRow;

                    $sheet->getStyle($dataRange)->applyFromArray([
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

                    // NO column centered
                    $sheet->getStyle('A11:A'.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // GOL column centered
                    $sheet->getStyle('D11:D'.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // JK column centered
                    $sheet->getStyle('H11:H'.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // NRP left
                    $sheet->getStyle('E11:E'.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                    // All 9 size columns centered (I–Q)
                    $sheet->getStyle('I11:Q'.$lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ── Row heights ──
                $sheet->getRowDimension(8)->setRowHeight(20);
                $sheet->getRowDimension(9)->setRowHeight(20);
                $sheet->getRowDimension(10)->setRowHeight(24);

                // ── Freeze pane below headers ──
                $sheet->freezePane('A11');

                // ── Signatory / Tanda Tangan ──
                if (! empty($this->signatorySettings)) {
                    $this->renderSignatory($sheet, $lastDataRow);
                }
            },
        ];
    }

    /**
     * Render signatory block below the data table in the Excel sheet.
     */
    private function renderSignatory(Worksheet $sheet, int $lastDataRow): void
    {
        $location = $this->signatorySettings['location'] ?? 'Mataram';
        $orgName = strtoupper($this->signatorySettings['organization_name'] ?? '');
        $jabatan = strtoupper($this->signatorySettings['signatory_title'] ?? 'KEPALA..........................');
        $nama = strtoupper($this->signatorySettings['signatory_name'] ?? '..........................................');
        $nrp = $this->signatorySettings['signatory_nrp'] ?? '.............................';

        // Start 2 rows below the last data row
        $startRow = $lastDataRow + 2;

        // Signatory is placed in columns N–R (right side of the table)
        $sigCol = 'N'; // Start column
        $endCol = 'R'; // End column for merge

        // Row 1: Location & Date
        $dateStr = $location.', '.now()->translatedFormat('d F Y');
        $sheet->setCellValue($sigCol.$startRow, $dateStr);
        $sheet->mergeCells("{$sigCol}{$startRow}:{$endCol}{$startRow}");
        $sheet->getStyle("{$sigCol}{$startRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Row 2: Organization Name (if set)
        $currentRow = $startRow + 1;
        if ($orgName !== '') {
            $sheet->setCellValue($sigCol.$currentRow, $orgName);
            $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
            $sheet->getStyle("{$sigCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $currentRow++;
        }

        // Row 3: Jabatan
        $sheet->setCellValue($sigCol.$currentRow, $jabatan);
        $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
        $sheet->getStyle("{$sigCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Skip rows for signature space
        $currentRow += 4;

        // Row 7: Nama (bold + underline)
        $sheet->setCellValue($sigCol.$currentRow, $nama);
        $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
        $sheet->getStyle("{$sigCol}{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'underline' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Row 8: NRP/NIP
        $currentRow++;
        $sheet->setCellValue($sigCol.$currentRow, 'NRP/NIP. '.$nrp);
        $sheet->mergeCells("{$sigCol}{$currentRow}:{$endCol}{$currentRow}");
        $sheet->getStyle("{$sigCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("{$sigCol}{$currentRow}")->getFont()->setSize(10);
    }
}
