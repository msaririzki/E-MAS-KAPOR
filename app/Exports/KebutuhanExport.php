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

class KebutuhanExport implements FromView, ShouldAutoSize, WithStyles, WithDrawings
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
}
