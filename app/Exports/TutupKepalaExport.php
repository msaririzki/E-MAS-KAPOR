<?php

namespace App\Exports;

use App\Models\Satker;
use App\Models\KaporItem;
use App\Models\KaporSize;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TutupKepalaExport implements FromCollection, WithHeadings, WithStyles, WithEvents, ShouldAutoSize
{
    protected $fiscalYear;
    protected $items;
    protected $sizesMap;
    protected $totalColumns;
    protected $type;

    public function __construct($type = null)
    {
        $this->fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        $this->type = $type;

        // Fetch all Tutup Kepala items
        $query = KaporItem::where('category', 'Tutup_Kepala')
            ->where('is_active', true);

        if ($this->type) {
            if ($this->type === 'TOPI LAPANGAN') {
                $query->where('item_name', 'LIKE', '%Topi Lapangan%');
            }
            elseif ($this->type === 'PET') {
                $query->where('item_name', 'LIKE', '%Pet%');
            }
            elseif ($this->type === 'BARET') {
                $query->where('item_name', 'LIKE', '%Baret%');
            }
            elseif ($this->type === 'PECI') {
                $query->where('item_name', 'LIKE', '%Peci%');
            }
            elseif ($this->type === 'JILBAB') {
                $query->where('item_name', 'LIKE', '%Jilbab%');
            }
        }

        $this->items = $query->with(['sizes' => function ($q) {
            $q->orderBy('sort_order');
        }])->orderBy('id')->get();

        // Map sizes for easy access
        $this->sizesMap = [];
        foreach ($this->items as $item) {
            $this->sizesMap[$item->id] = $item->sizes;
        }
    }

    public function collection()
    {
        $satkers = Satker::orderBy('name')->get();
        $data = [];
        $no = 1;

        foreach ($satkers as $satker) {
            $row = [
                $no++,
                $satker->name,
                $satker->personnels()->count(), // Jml Personil
            ];

            // For each Item in Tutup Kepala
            foreach ($this->items as $item) {
                foreach ($item->sizes as $size) {
                    // Count personnel in this Satker having this specific size submission
                    $count = $satker->personnels()
                        ->whereHas('submissions', function ($q) use ($item, $size) {
                        $q->where('fiscal_year', $this->fiscalYear)
                            ->where('kapor_item_id', $item->id)
                            ->where('kapor_size_id', $size->id);
                    })
                        ->count();

                    $row[] = $count > 0 ? $count : '-';
                }
            }

            // Add Total per Satker (Sum of all sizes in this category)
            // Note: This logic assumes one person gets one headgear. If they get multiple types, this sum might be > total personnel.
            // Usually rekap counts the ITEM quantity needed.
            $totalQty = $satker->personnels()
                ->whereHas('submissions', function ($q) {
                $q->where('fiscal_year', $this->fiscalYear)
                    ->whereHas('kaporItem', function ($qi) {
                    $qi->where('category', 'Tutup_Kepala');
                }
                );
            })
                ->count();

            $row[] = $totalQty;

            $data[] = $row;
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        // Row 1: Title
        $titleText = 'REKAPITULASI DATA TUTUP KEPALA ' . ($this->type ? strtoupper($this->type) . ' ' : '') . 'PERSONEL POLDA NTB TA. ' . $this->fiscalYear;
        $title = [$titleText];

        // Row 2: Empty

        // Row 3: Item Categories Helper (Merged cells usually)
        $headerRow1 = ['NO', 'SATUAN KERJA', 'JML PERSONIL'];
        $headerRow2 = ['', '', '']; // Spacers for merge

        foreach ($this->items as $item) {
            foreach ($item->sizes as $size) {
                $headerRow1[] = strtoupper($item->item_name); // Will be merged
                $headerRow2[] = $size->size_label;
            }
        }

        $headerRow1[] = 'TOTAL';
        $headerRow2[] = '';

        return [
            $title,
            [''], // Spacer
            $headerRow1,
            $headerRow2
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
            4 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();
            $lastColIndex = 3; // Starting after JML PERSONIL

            // Calculate last column
            $totalSizes = 0;
            foreach ($this->items as $item) {
                $totalSizes += $item->sizes->count();
            }
            $lastColIndex += $totalSizes;
            $lastColIndex++; // For TOTAL column (1-based count so far)

            // Convert index to Letter (e.g., 5 -> E)
            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

            // Merge Title
            $sheet->mergeCells("A1:{$lastColLetter}1");

            // Merge Fixed Headers
            $sheet->mergeCells('A3:A4'); // NO
            $sheet->mergeCells('B3:B4'); // SATKER
            $sheet->mergeCells('C3:C4'); // JML PERSONIL

            // Merge Item Headers
            $currentCol = 4; // Column D
            foreach ($this->items as $item) {
                $count = $item->sizes->count();
                $startLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                $endLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + $count - 1);

                if ($count > 1) {
                    $sheet->mergeCells("{$startLetter}3:{$endLetter}3");
                }
                $currentCol += $count;
            }

            // Merge Total
            $totalLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
            $sheet->mergeCells("{$totalLetter}3:{$totalLetter}4");

            // Borders
            $highestRow = $sheet->getHighestRow();
            $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'],
                        ],
                    ],
                ];
            $sheet->getStyle("A3:{$lastColLetter}{$highestRow}")->applyFromArray($styleArray);

            // Background for headers
            $sheet->getStyle("A3:{$lastColLetter}4")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('F2F2F2');
        },
        ];
    }
}
