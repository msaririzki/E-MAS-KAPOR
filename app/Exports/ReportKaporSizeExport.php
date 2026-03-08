<?php

namespace App\Exports;

use App\Models\Personnel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportKaporSizeExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        // Ambil semua personil yang punya kapor_sizes
        $personnels = Personnel::with(['satker', 'rank'])
            ->whereNotNull('kapor_sizes')
            ->get();

        $rows = collect();
        $no = 1;

        foreach ($personnels as $p) {
            $sizes = $p->kapor_sizes ?? [];

            $rows->push([
                'no' => $no++,
                'nama' => $p->full_name,
                'nrp' => $p->nrp ?? '-',
                'satker' => $p->satker->name ?? '-',
                'pangkat' => $p->rank->name ?? '-',
                'topi' => $sizes['topi_lapangan'] ?? $sizes['head'] ?? '-',
                'kemeja' => $sizes['kemeja'] ?? $sizes['shirt'] ?? '-',
                'celana' => $sizes['celana'] ?? $sizes['pants'] ?? '-',
                'sepatu_dinas' => $sizes['sepatu_dinas'] ?? $sizes['shoes'] ?? '-',
                'sepatu_or' => $sizes['sepatu_olahraga'] ?? $sizes['sport_shoes'] ?? '-',
                'sabuk' => $sizes['sabuk'] ?? $sizes['belt'] ?? '-',
                'jilbab' => $sizes['jilbab'] ?? '-',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Nama Lengkap', 'NRP', 'Satker', 'Pangkat', 'Topi', 'Kemeja', 'Celana', 'Sepatu Dinas', 'Sepatu OR', 'Sabuk', 'Jilbab'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Rekap Ukuran KAPOR';
    }
}
