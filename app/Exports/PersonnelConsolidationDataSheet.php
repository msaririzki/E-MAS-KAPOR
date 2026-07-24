<?php

namespace App\Exports;

use App\Models\Satker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PersonnelConsolidationDataSheet implements FromCollection, WithColumnFormatting, WithColumnWidths, WithEvents, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Collection $personnels,
        private readonly Satker $satker,
        private readonly string $title,
        private readonly string $year,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function collection(): Collection
    {
        return $this->personnels->values()->map(function ($personnel, int $index): array {
            return [
                $index + 1,
                $personnel->full_name,
                $personnel->rank?->name ?? '',
                $personnel->golongan ?? '',
                $personnel->nrp,
                $personnel->jabatan,
                $personnel->bagian,
                $personnel->gender === 'P' ? 'W' : 'P',
                $personnel->religion,
                $personnel->keterangan,
                $personnel->sync_token,
            ];
        });
    }

    public function headings(): array
    {
        return [
            ['KEPOLISIAN NEGARA REPUBLIK INDONESIA'],
            ['DAERAH NUSA TENGGARA BARAT'],
            ['BIRO LOGISTIK'],
            [''],
            ['DATA PERSONEL SATKER '.strtoupper($this->satker->name)],
            ['TEMPLATE PEMBARUAN DATA PERSONEL TA. '.$this->year],
            ['Baris boleh diurutkan dan personel baru boleh ditambahkan. KODE DATA jangan diubah.'],
            [
                'NO',
                'NAMA',
                'PANGKAT',
                'GOLONGAN',
                'NRP/NIP',
                'JABATAN',
                'BAG/FUNGSI',
                'JENIS KELAMIN P/W',
                'AGAMA',
                'KETERANGAN',
                'KODE DATA (JANGAN DIUBAH)',
            ],
            array_fill(0, 11, ' '),
            array_fill(0, 11, ' '),
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 34,
            'C' => 20,
            'D' => 14,
            'E' => 21,
            'F' => 34,
            'G' => 24,
            'H' => 18,
            'I' => 16,
            'J' => 24,
            'K' => 39,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                // Keep one editable blank row when a sheet has no personnel yet.
                $lastRow = max(11, $sheet->getHighestDataRow());

                $this->styleHeader($sheet);
                $sheet->freezePane('A11');
                $sheet->getRowDimension(8)->setRowHeight(20);
                $sheet->getRowDimension(9)->setRowHeight(20);
                $sheet->getRowDimension(10)->setRowHeight(24);
                $sheet->getStyle("A8:K{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A11:K{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A11:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H11:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($lastRow >= 11) {
                    $sheet->getStyle("A11:K{$lastRow}")->applyFromArray([
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

                    for ($row = 11; $row <= $lastRow; $row++) {
                        $nrp = trim((string) $sheet->getCell("E{$row}")->getValue());
                        $sheet->getCell("E{$row}")->setValueExplicit($nrp, DataType::TYPE_STRING);
                        $code = trim((string) $sheet->getCell("K{$row}")->getValue());
                        $sheet->getCell("K{$row}")->setValueExplicit($code, DataType::TYPE_STRING);
                    }

                    $sheet->getStyle("A11:K{$lastRow}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_PROTECTED);
                    $sheet->getStyle("A11:J{$lastRow}")
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_UNPROTECTED);
                }

                $sheet->getStyle("K8:K{$lastRow}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('E2E8F0');
                $sheet->getStyle("K8:K{$lastRow}")->getFont()->getColor()->setARGB('475569');
                $sheet->getComment('K8')->getText()->createTextRun(
                    'Kode ini dipakai untuk mengenali personel walaupun baris dipindah atau diurutkan. Jangan diubah atau dihapus.'
                );

                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(false);
                $sheet->getProtection()->setInsertRows(false);
                $sheet->getProtection()->setDeleteRows(false);
                $sheet->getProtection()->setFormatCells(false);
                $sheet->getProtection()->setPassword('EMAS-KAPOR');
            },
        ];
    }

    private function styleHeader(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        $sheet->mergeCells('A5:K5');
        $sheet->mergeCells('A6:K6');
        $sheet->mergeCells('A7:K7');
        $sheet->getStyle('A1:K7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A5:A6')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A7')->getFont()->setItalic(true)->getColor()->setARGB('64748B');

        foreach (range('A', 'K') as $column) {
            $sheet->mergeCells($column.'8:'.$column.'10');
        }

        $sheet->getStyle('A8:K10')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => '111827'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'F2F2F2'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '000000'],
                ],
            ],
        ]);
    }
}
