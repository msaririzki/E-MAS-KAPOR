<?php

namespace App\Exports;

use App\Models\Satker;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PersonnelConsolidationInstructionSheet implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{
    public function __construct(
        private readonly Satker $satker,
        private readonly string $year,
    ) {}

    public function title(): string
    {
        return 'Petunjuk';
    }

    public function collection(): Collection
    {
        return collect([
            ['PETUNJUK KONSOLIDASI PERSONEL'],
            ['Satker', $this->satker->name],
            ['Tahun Anggaran', $this->year],
            [''],
            ['1', 'Bagikan data kepada unit bawahan dan gabungkan kembali dalam satu file.'],
            ['2', 'Baris boleh dipindah, disalin, dan diurutkan berdasarkan BAG/FUNGSI.'],
            ['3', 'Jangan mengubah KODE DATA. Jika kode hilang, sistem masih mencoba mencocokkan NRP/NIP.'],
            ['4', 'Tambahkan personel baru di baris bawah. KODE DATA untuk personel baru dikosongkan.'],
            ['5', 'NRP/NIP ganda atau milik satker lain tidak langsung disimpan dan akan ditampilkan untuk diperiksa.'],
            ['6', 'Personel yang tidak ada dalam file tidak langsung dinonaktifkan. Admin harus memilihnya pada pratinjau.'],
            ['7', 'Jangan menghapus judul kolom pada baris 8.'],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:B1');
                $sheet->getStyle('A1:B1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '991B1B']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getStyle('A2:A3')->getFont()->setBold(true);
                $sheet->getStyle('B2:B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('A5:A11')->getFont()->setBold(true)->getColor()->setARGB('991B1B');
                $sheet->getStyle('B5:B11')->getAlignment()->setWrapText(true);
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(105);
                foreach (range(5, 11) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(30);
                }
                $sheet->freezePane('A5');
            },
        ];
    }
}
