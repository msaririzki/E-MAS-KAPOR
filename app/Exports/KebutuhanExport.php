<?php

namespace App\Exports;

use App\Models\Kebutuhan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class KebutuhanExport implements FromView, ShouldAutoSize, WithStyles, WithDrawings, WithEvents
{
    protected $kebutuhan;

    public function __construct(Kebutuhan $kebutuhan)
    {
        $this->kebutuhan = $kebutuhan;
    }

    public function view(): View
    {
        return view('admin-satker.kebutuhan.export_excel', [
            'kebutuhan' => $this->kebutuhan
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Kop Surat');
        $drawing->setDescription('Kop Surat Kaporlap');
        $drawing->setPath(public_path('kop suratt.png'));
        $drawing->setHeight(80);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(15);
        $drawing->setOffsetY(10);

        return $drawing;
    }
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // --- Page Setup ---
                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
                    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0); // 0 means automatic height

                // --- Margins ---
                $sheet->getPageMargins()->setTop(0.75);
                $sheet->getPageMargins()->setRight(0.75);
                $sheet->getPageMargins()->setLeft(0.75);
                $sheet->getPageMargins()->setBottom(0.75);
            },
        ];
    }
}
