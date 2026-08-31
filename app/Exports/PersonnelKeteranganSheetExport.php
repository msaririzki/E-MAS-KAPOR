<?php

namespace App\Exports;

use App\Models\Satker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
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

class PersonnelKeteranganSheetExport implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithTitle
{
    public function __construct(
        private readonly Satker $satker,
        private readonly Collection $personnels,
    ) {}

    public function collection(): Collection
    {
        return $this->personnels->values()->map(function ($personnel, int $index): array {
            return [
                'id' => $personnel->id,
                'no' => $index + 1,
                'nama' => $personnel->full_name,
                'nrp_nip' => $personnel->user?->nrp_nip ?? $personnel->nrp,
                'satker' => $personnel->satker?->name,
                'tipe_personel' => $personnel->personnel_type,
                'pangkat' => $personnel->rank?->name,
                'golongan' => $personnel->golongan,
                'jenis_kelamin' => $personnel->gender,
                'agama' => $personnel->religion,
                'jabatan' => $personnel->jabatan,
                'bag_fungsi' => $personnel->bagian,
                'keterangan_1' => $personnel->keterangan,
                'keterangan_2' => $personnel->keterangan_2,
                'keterangan_3' => $personnel->keterangan_3,
                'keterangan_4' => $personnel->keterangan_4,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'id',
            'no',
            'nama',
            'nrp_nip',
            'satker',
            'tipe_personel',
            'pangkat',
            'golongan',
            'jenis_kelamin',
            'agama',
            'jabatan',
            'bag_fungsi',
            'keterangan_1',
            'keterangan_2',
            'keterangan_3',
            'keterangan_4',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function title(): string
    {
        $base = $this->satker->name ?: ('SATKER-'.$this->satker->id);
        $clean = preg_replace('/[\\\\\\/\\?\\*\\:\\[\\]]+/', ' ', $base) ?? $base;
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);

        return mb_substr($clean, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastDataRow = $sheet->getHighestRow();

                $sheet->setAutoFilter('A1:P'.max(1, $lastDataRow));
                $sheet->freezePane('C2');
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A1:P1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FFCBD5E1'],
                        ],
                    ],
                ]);

                // Kelompok warna memudahkan membaca referensi tanpa mengubah nama kolom impor.
                $headerColors = [
                    'A' => 'FF475569', // Kode data
                    'B' => 'FF64748B', // Nomor
                    'C' => 'FF1D4ED8', // Nama
                    'D' => 'FF7C3AED', // NRP/NIP
                    'E' => 'FF0F766E', // Satker
                    'F' => 'FF0369A1', // Tipe personel
                    'G' => 'FFC2410C', // Pangkat
                    'H' => 'FFC2410C', // Golongan
                    'I' => 'FF475569', // Jenis kelamin
                    'J' => 'FF475569', // Agama
                    'K' => 'FFB91C1C', // Jabatan
                    'L' => 'FFB91C1C', // Bag/fungsi
                    'M' => 'FF475569', // Keterangan 1 (referensi)
                    'N' => 'FF15803D', // Keterangan 2 (dapat diubah)
                    'O' => 'FF15803D', // Keterangan 3 (dapat diubah)
                    'P' => 'FF15803D', // Keterangan 4 (dapat diubah)
                ];

                foreach ($headerColors as $column => $color) {
                    $sheet->getStyle($column.'1')->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($color);
                }

                for ($row = 2; $row <= $lastDataRow; $row++) {
                    $sheet->getCell('A'.$row)->setValueExplicit((string) $sheet->getCell('A'.$row)->getValue(), DataType::TYPE_STRING);

                    $nrpValue = ltrim((string) $sheet->getCell('D'.$row)->getValue(), "\t");
                    $sheet->getCell('D'.$row)->setValueExplicit($nrpValue, DataType::TYPE_STRING);
                }

                if ($lastDataRow >= 2) {
                    $sheet->getStyle('A2:P'.$lastDataRow)
                        ->getProtection()
                        ->setLocked(Protection::PROTECTION_PROTECTED);

                    $sheet->getStyle('A2:M'.$lastDataRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF8FAFC');

                    $sheet->getStyle('N2:P'.$lastDataRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FFF0FDF4');

                    foreach (['N', 'O', 'P'] as $editableColumn) {
                        $sheet->getStyle($editableColumn.'2:'.$editableColumn.$lastDataRow)
                            ->getProtection()
                            ->setLocked(Protection::PROTECTION_UNPROTECTED);
                    }
                }

                $sheet->getProtection()->setSheet(true);
                $sheet->getProtection()->setSort(true);
                $sheet->getProtection()->setAutoFilter(true);
                $sheet->getProtection()->setInsertRows(false);
                $sheet->getProtection()->setFormatCells(false);
                $sheet->getProtection()->setPassword('EMAS-KAPOR');
            },
        ];
    }
}
