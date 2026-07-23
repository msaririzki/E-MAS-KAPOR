<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentBatchInstructionSheet implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Petunjuk';
    }

    public function collection(): Collection
    {
        return collect([
            ['PETUNJUK PENGISIAN DATA SISWA'],
            ['1', 'Jangan mengubah KODE SISTEM karena kode ini dipakai untuk mencocokkan data saat diunggah.'],
            ['2', 'Nama, pangkat, NRP/NIP, jabatan, bagian, agama, dan keterangan dapat diperbaiki.'],
            ['3', 'Pangkat harus sama dengan nama pangkat yang tersedia pada aplikasi.'],
            ['4', 'NRP/NIP wajib unik. Gunakan pilihan PRIA atau WANITA pada kolom jenis kelamin.'],
            ['5', 'Kelompok menentukan kecocokan penerima barang: TAMTAMA, BINTARA, PAMA, atau PAMEN.'],
            ['6', 'Kosongkan ukuran yang belum diketahui. Sistem akan menandainya sebagai belum lengkap.'],
            ['7', 'Untuk siswa pria, kolom JILBAB akan diabaikan.'],
            ['8', 'Unggah kembali file melalui halaman angkatan yang sama dan periksa pratinjau sebelum menyimpan.'],
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:B1');
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFF');
                $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('991B1B');
                $sheet->getColumnDimension('A')->setWidth(7);
                $sheet->getColumnDimension('B')->setWidth(105);
                $sheet->getStyle('B1:B9')->getAlignment()->setWrapText(true);
            },
        ];
    }
}
