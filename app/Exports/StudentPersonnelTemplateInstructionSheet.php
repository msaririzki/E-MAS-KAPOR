<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentPersonnelTemplateInstructionSheet implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Petunjuk';
    }

    public function collection(): Collection
    {
        return collect([
            ['PETUNJUK UNGGAH DATA SISWA LENGKAP'],
            ['1', 'Isi data pada sheet Data Siswa mulai baris 12. Jangan mengubah posisi atau nama kolom.'],
            ['2', 'NAMA, PANGKAT, NRP/NIP, JABATAN, BAG/FUNGSI, dan JENIS KELAMIN wajib diisi.'],
            ['3', 'Pangkat harus dipilih dari daftar yang tersedia pada sheet Referensi Pangkat.'],
            ['4', 'Gunakan P untuk pria dan W untuk wanita. NRP/NIP harus unik dan disimpan sebagai teks.'],
            ['5', 'Untuk pangkat PNS, isi GOLONGAN dengan angka 1, 2, 3, atau 4.'],
            ['6', 'Kolom ukuran boleh dikosongkan jika belum diketahui. Nilai yang diisi akan divalidasi oleh sistem.'],
            ['7', 'Untuk siswa pria, ukuran jilbab akan diabaikan.'],
            ['8', 'Satker tujuan dipilih saat file diunggah melalui halaman Data Personel.'],
            ['9', 'Siswa dibuat tanpa akun login, tetapi tetap tampil pada Data Personel, nominatif paket, dan SPPM.'],
            ['10', 'Unggahan ulang dengan NRP/NIP yang sama hanya dapat memperbarui data siswa, bukan personel biasa.'],
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
                $sheet->getColumnDimension('B')->setWidth(110);
                $sheet->getStyle('B1:B11')->getAlignment()->setWrapText(true);
                $sheet->freezePane('A2');
            },
        ];
    }
}
