<?php

namespace App\Exports;

use App\Models\StudentBatch;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class StudentBatchDataSheet implements FromCollection, ShouldAutoSize, WithColumnFormatting, WithEvents, WithHeadings, WithTitle
{
    public function __construct(private readonly StudentBatch $batch) {}

    public function title(): string
    {
        return 'Data Siswa';
    }

    public function collection(): Collection
    {
        return $this->batch->students()
            ->with('rank:id,name')
            ->orderBy('gender')
            ->orderBy('student_code')
            ->get()
            ->values()
            ->map(function ($student, int $index): array {
                $sizes = is_array($student->kapor_sizes) ? $student->kapor_sizes : [];

                return [
                    $index + 1,
                    $student->student_code,
                    $student->full_name,
                    $student->rank?->name,
                    $student->procurement_group,
                    $student->nrp,
                    $student->jabatan,
                    $student->bagian,
                    $student->gender === 'P' ? 'WANITA' : 'PRIA',
                    $sizes['topi'] ?? '',
                    $sizes['kemeja'] ?? '',
                    $sizes['celana'] ?? '',
                    $sizes['olahraga'] ?? '',
                    $sizes['sepatu_dinas'] ?? '',
                    $sizes['sepatu_olahraga'] ?? '',
                    $sizes['jaket'] ?? '',
                    $sizes['sabuk'] ?? '',
                    $sizes['jilbab'] ?? '',
                    $student->religion,
                    $student->keterangan,
                    $student->keterangan_2,
                ];
            });
    }

    public function headings(): array
    {
        return [
            ['MANAJEMEN DATA SISWA KAPOR'],
            [sprintf('%s | T.A. %d | %s', $this->batch->name, $this->batch->fiscal_year, $this->batch->code)],
            ['Kode sistem dan nomor baris dikunci. Kolom lainnya dapat diperbaiki lalu diunggah kembali.'],
            [
                'NO', 'KODE SISTEM', 'NAMA', 'PANGKAT', 'KELOMPOK PENGADAAN', 'NRP/NIP',
                'JABATAN', 'BAG/FUNGSI', 'JENIS KELAMIN', 'TOPI', 'KEMEJA', 'CELANA/ROK',
                'KAOS OLAHRAGA', 'SEPATU DINAS', 'SEPATU OLAHRAGA', 'JAKET', 'SABUK',
                'JILBAB', 'AGAMA', 'KETERANGAN', 'KETERANGAN 2',
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(5, $sheet->getHighestRow());

                foreach (['A1:U1', 'A2:U2', 'A3:U3'] as $range) {
                    $sheet->mergeCells($range);
                }

                $sheet->getStyle('A1:U1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFF');
                $sheet->getStyle('A1:U1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('991B1B');
                $sheet->getStyle('A2:U2')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A3:U3')->getFont()->setItalic(true)->getColor()->setARGB('64748B');
                $sheet->getStyle('A1:U4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A4:U4')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
                $sheet->getStyle('A4:U4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1F2937');
                $sheet->getStyle('A4:U'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('CBD5E1');
                $sheet->getStyle('A5:B'.$lastRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('F1F5F9');
                $sheet->getStyle('A5:A'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('E5:E'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('I5:I'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->freezePane('C5');
                $sheet->setAutoFilter('A4:U'.$lastRow);

                $sheet->getColumnDimension('A')->setWidth(7);
                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(34);
                $sheet->getColumnDimension('D')->setWidth(22);
                $sheet->getColumnDimension('E')->setWidth(20);
                $sheet->getColumnDimension('F')->setWidth(22);
                $sheet->getColumnDimension('G')->setWidth(30);
                $sheet->getColumnDimension('H')->setWidth(22);
                $sheet->getColumnDimension('I')->setWidth(17);
                foreach (range('J', 'R') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(17);
                }
                $sheet->getColumnDimension('S')->setWidth(16);
                $sheet->getColumnDimension('T')->setWidth(24);
                $sheet->getColumnDimension('U')->setWidth(24);

                $this->addListValidation($sheet, 'E5:E'.$lastRow, 'TAMTAMA,BINTARA,PAMA,PAMEN');
                $this->addListValidation($sheet, 'I5:I'.$lastRow, 'PRIA,WANITA');

                $sheet->getStyle('A5:U'.$lastRow)->getProtection()->setLocked(Protection::PROTECTION_PROTECTED);
                $sheet->getStyle('C5:U'.$lastRow)->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);
                $sheet->getProtection()->setPassword('EMAS-KAPOR')->setSheet(true);
            },
        ];
    }

    private function addListValidation($sheet, string $range, string $values): void
    {
        $validation = $sheet->getCell(explode(':', $range)[0])->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(false);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Pilihan tidak valid');
        $validation->setError('Pilih nilai yang tersedia pada daftar.');
        $validation->setFormula1('"'.$values.'"');

        $sheet->setDataValidation($range, clone $validation);
    }
}
