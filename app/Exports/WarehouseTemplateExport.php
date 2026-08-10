<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class WarehouseTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles, WithEvents
{
    public function array(): array
    {
        return [
            [
                'no' => '1',
                'nama_barang' => 'CONTOH BARANG A',
                'satuan' => 'PCS',
                'tahun' => '2024',
                'kuantitas' => '50',
                'harga_satuan' => '150000',
                'sumber_pengadaan' => 'Mabes Polri',
                'kategori_stok' => 'Stok',
            ],
            [
                'no' => '2',
                'nama_barang' => 'CONTOH BARANG B',
                'satuan' => 'STEL',
                'tahun' => '2023',
                'kuantitas' => '100',
                'harga_satuan' => '25000',
                'sumber_pengadaan' => 'Polda NTB',
                'kategori_stok' => 'Luar Stok',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'no',
            'nama_barang',
            'satuan',
            'tahun',
            'kuantitas',
            'harga_satuan',
            'sumber_pengadaan',
            'kategori_stok',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Dropdown untuk Satuan (Kolom C)
                $satuanValidation = $sheet->getCell('C2')->getDataValidation();
                $satuanValidation->setType(DataValidation::TYPE_LIST);
                $satuanValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $satuanValidation->setAllowBlank(true);
                $satuanValidation->setShowInputMessage(true);
                $satuanValidation->setShowErrorMessage(true);
                $satuanValidation->setShowDropDown(true);
                $satuanValidation->setErrorTitle('Input Error');
                $satuanValidation->setError('Satuan tidak valid.');
                $satuanValidation->setPromptTitle('Pilih Satuan');
                $satuanValidation->setPrompt('Silakan pilih dari daftar drop-down.');
                $satuanValidation->setFormula1('"PCS,STEL,PASANG,BUAH,RIM,KODI,ROLL,METER,PAK,LUSIN,LEMBAR,SET,UNIT"');

                // Dropdown untuk Kategori Stok (Kolom H)
                $kategoriValidation = $sheet->getCell('H2')->getDataValidation();
                $kategoriValidation->setType(DataValidation::TYPE_LIST);
                $kategoriValidation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $kategoriValidation->setAllowBlank(true);
                $kategoriValidation->setShowInputMessage(true);
                $kategoriValidation->setShowErrorMessage(true);
                $kategoriValidation->setShowDropDown(true);
                $kategoriValidation->setErrorTitle('Input Error');
                $kategoriValidation->setError('Kategori tidak valid.');
                $kategoriValidation->setPromptTitle('Pilih Kategori Stok');
                $kategoriValidation->setPrompt('Silakan pilih dari daftar drop-down.');
                $kategoriValidation->setFormula1('"Stok,Luar Stok"');

                // Terapkan ke 1000 baris ke bawah
                for ($i = 2; $i <= 1000; $i++) {
                    $sheet->getCell('C' . $i)->setDataValidation(clone $satuanValidation);
                    $sheet->getCell('H' . $i)->setDataValidation(clone $kategoriValidation);
                }
            },
        ];
    }
}
