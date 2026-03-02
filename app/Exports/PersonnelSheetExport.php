<?php

namespace App\Exports;

use App\Models\Personnel;
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
 * Sheet tunggal (satu tipe personel: Polri atau PNS).
 * Digunakan oleh PersonnelExport sebagai bagian dari multi-sheet export.
 */
class PersonnelSheetExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithStyles, WithTitle
{
    protected Collection $personnels;
    protected string $satkerName;
    protected string $sheetTitle;

    public function __construct(Collection $personnels, string $satkerName, string $sheetTitle)
    {
        $this->personnels = $personnels;
        $this->satkerName = $satkerName;
        $this->sheetTitle = $sheetTitle;
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $no   = 1;

        foreach ($this->personnels as $p) {
            $sizes = is_array($p->kapor_sizes) ? $p->kapor_sizes : [];

            // Gender: L (Laki-laki) → 'P', P (Perempuan) → 'W' (sesuai format Excel template)
            $genderExcel = ($p->gender === 'P') ? 'W' : 'P';

            $rows->push([
                $no++,                                           // A  NO
                $p->full_name ?? '',                             // B  NAMA
                $p->rank->name ?? '',                            // C  PANGKAT
                $p->golongan ?? ($p->rank->category ?? ''),     // D  GOLONGAN
                "\t" . ($p->nrp ?? ''),                         // E  NRP/NIP — prefix TAB agar Excel baca sebagai teks
                $p->jabatan ?? '',                               // F  JABATAN
                $p->bagian ?? '',                                // G  BAG/FUNGSI
                $genderExcel,                                    // H  JENIS KELAMIN
                $sizes['topi'] ?? '',                            // I  TUTUP KEPALA
                $sizes['kemeja'] ?? '',                          // J  KEMEJA
                $sizes['celana'] ?? '',                          // K  CELANA/ROK
                $sizes['olahraga'] ?? '',                        // L  T-SHIRT/OLAHRAGA
                $sizes['sepatu_dinas'] ?? '',                    // M  SEPATU DINAS
                $sizes['sepatu_olahraga'] ?? '',                 // N  SEPATU OLAHRAGA
                $sizes['jaket'] ?? '',                           // O  JAKET
                $sizes['sabuk'] ?? '',                           // P  SABUK
                $sizes['jilbab'] ?? '',                          // Q  JILBAB
                $p->keterangan ?? '',                            // R  KETERANGAN
            ]);
        }

        return $rows;
    }

    /**
     * Format kolom E (NRP/NIP) sebagai TEXT agar leading zero tidak hilang.
     * Format kolom A (NO) tetap angka.
     */
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // NRP/NIP → teks
        ];
    }

    public function headings(): array
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));

        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['DATA UKURAN KAPOR PERSONEL ' . strtoupper($this->satkerName)],
            ['UNTUK DUKUNGAN KAPOR TA. ' . $fiscalYear],
            [''],
            ['NO', 'NAMA', 'PANGKAT', 'GOLONGAN', 'NRP', 'JABATAN', 'BAG/FUNGSI', 'JENIS KELAMIN P / W', 'UKURAN', '', '', '', '', '', '', '', '', 'KETERANGAN'],
            ['', '', '', '', '', '', '', '', 'TUTUP KEPALA', 'TUTUP BADAN', '', '', 'TUTUP KAKI SEPATU', '', 'JAKET', 'SABUK', 'JILBAB', ''],
            ['', '', '', '', '', '', '', '', '', 'KEMEJA', 'CELANA / ROK', 'T.SHIRT / OLAHRAGA', 'DINAS', 'OLAHRAGA', '', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1  => ['font' => ['bold' => true]],
            2  => ['font' => ['bold' => true]],
            3  => ['font' => ['bold' => true, 'underline' => true]],
            5  => ['font' => ['bold' => true, 'size' => 12]],
            6  => ['font' => ['bold' => true, 'size' => 12, 'underline' => true]],
            8  => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
            9  => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
            10 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ── Set kolom E sebagai teks sebelum data ditulis ──────────
                // Ini memastikan leading zero (mis. 087701234) tidak hilang
                $lastDataRow = $sheet->getHighestRow();
                for ($row = 11; $row <= $lastDataRow; $row++) {
                    $cell = $sheet->getCell('E' . $row);
                    $rawVal = ltrim((string) $cell->getValue(), "\t"); // hapus prefix TAB
                    $cell->setValueExplicit($rawVal, DataType::TYPE_STRING);
                }

                // ── Alignment header ──────────────────────────────────────
                $sheet->getStyle('A1:R6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ── Merge Top Headers ─────────────────────────────────────
                $sheet->mergeCells('A1:C1');
                $sheet->mergeCells('A2:C2');
                $sheet->mergeCells('A3:C3');
                $sheet->mergeCells('A5:R5');
                $sheet->mergeCells('A6:R6');

                // ── Merge Table Headers ───────────────────────────────────
                $sheet->mergeCells('A8:A10');
                $sheet->mergeCells('B8:B10');
                $sheet->mergeCells('C8:C10');
                $sheet->mergeCells('D8:D10');
                $sheet->mergeCells('E8:E10');
                $sheet->mergeCells('F8:F10');
                $sheet->mergeCells('G8:G10');
                $sheet->mergeCells('H8:H10');
                $sheet->mergeCells('I8:Q8');
                $sheet->mergeCells('I9:I10');
                $sheet->mergeCells('J9:L9');
                $sheet->mergeCells('M9:N9');
                $sheet->mergeCells('O9:O10');
                $sheet->mergeCells('P9:P10');
                $sheet->mergeCells('Q9:Q10');
                $sheet->mergeCells('R8:R10');

                // ── Border header ─────────────────────────────────────────
                $sheet->getStyle('A8:R10')->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => '000000'],
                        ],
                    ],
                ]);

                // ── Border data rows ──────────────────────────────────────
                if ($lastDataRow >= 11) {
                    $sheet->getStyle('A11:R' . $lastDataRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'CCCCCC'],
                            ],
                            'outline' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => '000000'],
                            ],
                        ],
                    ]);
                }

                // ── Background header ─────────────────────────────────────
                $sheet->getStyle('A8:R10')->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('F2F2F2');

                // ── Row heights ───────────────────────────────────────────
                $sheet->getRowDimension(8)->setRowHeight(20);
                $sheet->getRowDimension(9)->setRowHeight(20);
                $sheet->getRowDimension(10)->setRowHeight(30);

                // ── Freeze panes ──────────────────────────────────────────
                $sheet->freezePane('A11');

                // ── Alignment data rows ───────────────────────────────────
                if ($lastDataRow >= 11) {
                    $sheet->getStyle('A11:A' . $lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('H11:Q' . $lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    // Kolom NRP: left-aligned (teks)
                    $sheet->getStyle('E11:E' . $lastDataRow)
                        ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            },
        ];
    }
}
