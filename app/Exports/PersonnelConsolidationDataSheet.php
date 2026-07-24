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
            $sizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];

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
                $sizes['topi'] ?? '',
                $sizes['kemeja'] ?? '',
                $sizes['celana'] ?? '',
                $sizes['olahraga'] ?? '',
                $sizes['sepatu_dinas'] ?? '',
                $sizes['sepatu_olahraga'] ?? '',
                $sizes['jaket'] ?? '',
                $sizes['sabuk'] ?? '',
                $sizes['jilbab'] ?? '',
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
            ['KONSOLIDASI DATA PERSONEL SATKER '.strtoupper($this->satker->name)],
            ['TAHUN ANGGARAN '.$this->year],
            ['Urutan baris boleh diubah. Jangan mengubah KODE DATA. Baris baru boleh ditambahkan di bagian bawah.'],
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
                'TUTUP KEPALA',
                'KEMEJA',
                'CELANA/ROK',
                'T.SHIRT/OLAHRAGA',
                'SEPATU DINAS',
                'SEPATU OLAHRAGA',
                'JAKET',
                'SABUK',
                'JILBAB',
                'KETERANGAN',
                'KODE DATA (JANGAN DIUBAH)',
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT,
            'T' => NumberFormat::FORMAT_TEXT,
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
            'J' => 15,
            'K' => 13,
            'L' => 15,
            'M' => 20,
            'N' => 16,
            'O' => 19,
            'P' => 12,
            'Q' => 12,
            'R' => 12,
            'S' => 24,
            'T' => 39,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(8, $sheet->getHighestDataRow());

                $this->styleHeader($sheet);
                $sheet->freezePane('A9');
                $sheet->setAutoFilter("A8:T{$lastRow}");
                $sheet->getRowDimension(8)->setRowHeight(42);
                $sheet->getStyle("A8:T{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A9:T{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A9:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H9:R{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                if ($lastRow >= 9) {
                    $sheet->getStyle("A9:T{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_HAIR,
                                'color' => ['argb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                    for ($row = 9; $row <= $lastRow; $row++) {
                        $nrp = trim((string) $sheet->getCell("E{$row}")->getValue());
                        $sheet->getCell("E{$row}")->setValueExplicit($nrp, DataType::TYPE_STRING);
                        $code = trim((string) $sheet->getCell("T{$row}")->getValue());
                        $sheet->getCell("T{$row}")->setValueExplicit($code, DataType::TYPE_STRING);
                    }
                }

                $sheet->getStyle("T8:T{$lastRow}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB('E2E8F0');
                $sheet->getStyle("T8:T{$lastRow}")->getFont()->getColor()->setARGB('475569');
                $sheet->getComment('T8')->getText()->createTextRun(
                    'Kode ini dipakai untuk mengenali personel walaupun baris dipindah atau diurutkan. Jangan diubah atau dihapus.'
                );
            },
        ];
    }

    private function styleHeader(Worksheet $sheet): void
    {
        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');
        $sheet->mergeCells('A3:C3');
        $sheet->mergeCells('A5:T5');
        $sheet->mergeCells('A6:T6');
        $sheet->mergeCells('A7:T7');
        $sheet->getStyle('A1:T7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A5:A6')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A7')->getFont()->setItalic(true)->getColor()->setARGB('64748B');
        $sheet->getStyle('A8:T8')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => '991B1B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => '7F1D1D'],
                ],
            ],
        ]);
    }
}
