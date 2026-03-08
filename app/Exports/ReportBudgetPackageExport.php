<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportBudgetPackageExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $packages = BudgetPackage::with(['budgetYear', 'items'])->latest()->get();

        $rows = collect();
        $no = 1;

        foreach ($packages as $pkg) {
            $rows->push([
                'no' => $no++,
                'nama_paket' => $pkg->name,
                'tahun_anggaran' => $pkg->budgetYear->year ?? '-',
                'total_item' => $pkg->items->count(),
                'total_anggaran' => $pkg->formatted_budget,
                'status' => $pkg->status_label,
                'dibuat' => $pkg->created_at->format('d M Y'),
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Nama Paket', 'Tahun Anggaran', 'Total Item', 'Total Anggaran', 'Status', 'Tanggal Dibuat'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Rekap Anggaran per Paket';
    }
}
