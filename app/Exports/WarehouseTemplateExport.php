<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehouseTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                'no' => '1',
                'nama_barang' => 'CONTOH BARANG A',
                'kuantitas' => '50',
                'harga_satuan' => '150000',
            ],
            [
                'no' => '2',
                'nama_barang' => 'CONTOH BARANG B',
                'kuantitas' => '100',
                'harga_satuan' => '25000',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'no',
            'nama_barang',
            'kuantitas',
            'harga_satuan',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
