<?php

namespace App\Exports;

use App\Models\Satker;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportPersonnelSummaryExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $poldaId = Satker::where('code', 'POLDA-NTB')->value('id');

        $satkers = Satker::withCount([
            'personnels as total_personnel' => fn ($query) => $query->active(),
        ])
            ->withCount([
                'personnels as submitted_count' => function ($q) {
                    $q->active()
                        ->whereNotNull('kapor_sizes')
                        ->whereNotNull('rank_id')
                        ->whereNotNull('nrp');
                },
            ])
            ->where(function ($query) use ($poldaId) {
                $query->whereNull('parent_id')->orWhere('parent_id', $poldaId);
            })
            ->orderBy('sort_order')
            ->get();

        $rows = collect();
        $no = 1;

        foreach ($satkers as $s) {
            $pending = $s->total_personnel - $s->submitted_count;
            $pct = $s->total_personnel > 0 ? round(($s->submitted_count / $s->total_personnel) * 100, 1) : 0;

            $rows->push([
                'no' => $no++,
                'satker' => $s->name,
                'total' => $s->total_personnel,
                'sudah_input' => $s->submitted_count,
                'belum_input' => $pending,
                'persentase' => $pct.'%',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Satuan Kerja', 'Total Personil', 'Sudah Input', 'Belum Input', 'Persentase (%)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Rekap Personil per Satker';
    }
}
