<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentBatchSizeReferenceSheet implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Referensi Ukuran';
    }

    public function collection(): Collection
    {
        return collect([
            ['JENIS UKURAN', 'FORMAT UMUM', 'CONTOH'],
            ['Topi', 'Angka', '54, 55, 56'],
            ['Kemeja', 'Pria angka; wanita kode pakaian', '15, 15.5, 16 / K, SD, B, EB'],
            ['Celana/Rok', 'Pria angka; wanita kode pakaian', '31, 32, 34 / K, SD, B, EB'],
            ['Kaos Olahraga', 'Kode ukuran pakaian', 'K, SD, B, EB, EEB'],
            ['Sepatu Dinas', 'Angka', '38, 39, 40, 41'],
            ['Sepatu Olahraga', 'Angka', '38, 39, 40, 41'],
            ['Jaket', 'Kode ukuran pakaian', 'K, SD, B, EB, EEB'],
            ['Sabuk', 'Angka', '44, 46, 48, 50'],
            ['Jilbab', 'Kode ukuran', 'K, SD, B'],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->getStyle('A1:C1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
                $sheet->getStyle('A1:C1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1F2937');
                $sheet->freezePane('A2');
            },
        ];
    }
}
