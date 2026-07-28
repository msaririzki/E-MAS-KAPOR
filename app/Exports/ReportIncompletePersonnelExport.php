<?php

namespace App\Exports;

use App\Models\Personnel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportIncompletePersonnelExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $personnels = Personnel::with(['satker', 'rank'])
            ->active()
            ->where(function ($q) {
                $q->whereNull('kapor_sizes')
                    ->orWhereNull('rank_id')
                    ->orWhereNull('nrp');
            })
            ->orderBy('satker_id')
            ->orderBy('full_name')
            ->get();

        $rows = collect();
        $no = 1;

        foreach ($personnels as $p) {
            $missing = [];
            if (empty($p->nrp)) {
                $missing[] = 'NRP';
            }
            if (empty($p->rank_id)) {
                $missing[] = 'Pangkat';
            }
            if (empty($p->kapor_sizes)) {
                $missing[] = 'Ukuran KAPOR';
            }

            $rows->push([
                'no' => $no++,
                'nama' => $p->full_name,
                'nrp' => $p->nrp ?? '-',
                'satker' => $p->satker->name ?? '-',
                'pangkat' => $p->rank->name ?? '-',
                'jabatan' => $p->jabatan ?? '-',
                'data_kurang' => implode(', ', $missing),
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Nama Lengkap', 'NRP', 'Satker', 'Pangkat', 'Jabatan', 'Data yang Belum Lengkap'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Personil Belum Lengkap';
    }
}
