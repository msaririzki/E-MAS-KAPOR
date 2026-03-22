<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WarehouseTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            [
                'nama_barang' => 'CONTOH BARANG A',
                'satuan' => 'STEL',
                'ukuran' => '14',
                'kuantitas' => '50',
                'harga_satuan' => '150000',
            ],
            [
                'nama_barang' => 'CONTOH BARANG A',
                'satuan' => 'STEL',
                'ukuran' => '15',
                'kuantitas' => '30',
                'harga_satuan' => '150000',
            ],
            [
                'nama_barang' => 'CONTOH BARANG B',
                'satuan' => 'PCS',
                'ukuran' => 'L',
                'kuantitas' => '100',
                'harga_satuan' => '25000',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'nama_barang',
            'satuan',
            'ukuran',
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
