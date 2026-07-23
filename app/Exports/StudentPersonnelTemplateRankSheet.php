<?php

namespace App\Exports;

use App\Models\Rank;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentPersonnelTemplateRankSheet implements FromCollection, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Referensi Pangkat';
    }

    public function collection(): Collection
    {
        return collect([['NAMA PANGKAT', 'KATEGORI']])->concat(
            Rank::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['name', 'category'])
                ->map(fn (Rank $rank): array => [$rank->name, $rank->category]),
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(2, $sheet->getHighestRow());
                $sheet->getStyle('A1:B1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFF');
                $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('1F2937');
                $sheet->setAutoFilter('A1:B'.$lastRow);
                $sheet->freezePane('A2');
            },
        ];
    }
}
