<?php

namespace App\Exports;

use App\Models\KaporItem;
use App\Models\Setting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PersonnelTemplateExport implements FromCollection, WithHeadings, WithStyles, WithEvents, ShouldAutoSize
{
    public function collection()
    {
        // Example data row matching the visual reference columns
        return new Collection([
            [
                '1',
                'DEDE RUHIAT DJUNAEDI, S.I.K., M.H.',
                'KOMBES POL',
                'PAMEN',
                '69120263',
                'IRWASDA',
                'ITWASDA',
                'P',
                '57', // Tutup Kepala
                '16', // Kemeja
                '34', // Celana/Rok
                'B', // T-Shirt
                '41', // Sepatu Dinas
                '41', // Sepatu Olahraga
                'B', // Jaket
                '42', // Sabuk
                '-', // Jilbab
                'CONTOH KETERANGAN'
            ]
        ]);
    }

    public function headings(): array
    {
        $fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['DATA UKURAN KAPOR PERSONEL SATKER BIRO LOGISTIK'],
            ['UNTUK DUKUNGAN KAPOR TA. ' . $fiscalYear],
            [''],
            ['NO', 'NAMA', 'PANGKAT', 'GOLONGAN', 'NRP', 'JABATAN', 'BAG/FUNGSI', 'JENIS KELAMIN P / W', 'UKURAN', '', '', '', '', '', '', '', '', 'KETERANGAN'],
            ['', '', '', '', '', '', '', '', 'TUTUP KEPALA', 'TUTUP BADAN', '', '', 'TUTUP KAKI SEPATU', '', 'JAKET', 'SABUK', 'JILBAB', ''],
            ['', '', '', '', '', '', '', '', '', 'KEMEJA', 'CELANA / ROK', 'T.SHIRT / OLAHRAGA', 'DINAS', 'OLAHRAGA', '', '', '', '']
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

            // Alignment for central headers
            $sheet->getStyle('A1:R6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Merging Top Headers
            $sheet->mergeCells('A1:C1');
            $sheet->mergeCells('A2:C2');
            $sheet->mergeCells('A3:C3');
            $sheet->mergeCells('A5:R5');
            $sheet->mergeCells('A6:R6');

            // Merging Table Headers
            $sheet->mergeCells('A8:A10'); // NO
            $sheet->mergeCells('B8:B10'); // NAMA
            $sheet->mergeCells('C8:C10'); // PANGKAT
            $sheet->mergeCells('D8:D10'); // GOLONGAN
            $sheet->mergeCells('E8:E10'); // NRP
            $sheet->mergeCells('F8:F10'); // JABATAN
            $sheet->mergeCells('G8:G10'); // BAG/FUNGSI
            $sheet->mergeCells('H8:H10'); // JENIS KELAMIN

            $sheet->mergeCells('I8:Q8'); // UKURAN Group
            $sheet->mergeCells('I9:I10'); // TUTUP KEPALA
            $sheet->mergeCells('J9:L9'); // TUTUP BADAN group row
            $sheet->mergeCells('M9:N9'); // TUTUP KAKI group row
            $sheet->mergeCells('O9:O10'); // JAKET
            $sheet->mergeCells('P9:P10'); // SABUK
            $sheet->mergeCells('Q9:Q10'); // JILBAB

            $sheet->mergeCells('R8:R10'); // KETERANGAN

            // Borders for table
            $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ];
            $sheet->getStyle('A8:R11')->applyFromArray($styleArray);

            // Background color for header
            $sheet->getStyle('A8:R10')->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('F2F2F2');

            // Auto row height for header
            $sheet->getRowDimension(8)->setRowHeight(20);
            $sheet->getRowDimension(9)->setRowHeight(20);
            $sheet->getRowDimension(10)->setRowHeight(30);
        },
        ];
    }
}
