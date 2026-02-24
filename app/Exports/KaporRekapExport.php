<?php

namespace App\Exports;

use App\Models\Satker;
use App\Models\KaporItem;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

class KaporRekapExport implements FromCollection, WithHeadings, WithStyles, WithEvents, ShouldAutoSize
{
    protected $fiscalYear;
    protected $items;
    protected $category;
    protected $itemNameFilter;
    protected $aggregatedData;

    public function __construct($category, $itemNameFilter = null)
    {
        $this->fiscalYear = Setting::getValue('fiscal_year', date('Y'));
        $this->category = $category; // e.g., 'Tutup_Kepala', 'Tutup_Badan'
        $this->itemNameFilter = $itemNameFilter;

        // 1. Fetch Items and Sizes
        $query = KaporItem::where('category', $this->category)
            ->where('is_active', true);

        if ($this->itemNameFilter) {
            $query->where('item_name', 'LIKE', "%{$this->itemNameFilter}%");
        }

        $this->items = $query->with(['sizes' => function ($q) {
            $q->orderBy('sort_order');
        }])->orderBy('id')->get();

        // 2. Optimized Data Fetching (Aggregation)
        // Fetch count of submissions grouped by Satker, Item, and Size
        $itemIds = $this->items->pluck('id')->toArray();

        $results = DB::table('kapor_submissions')
            ->join('personnels', 'kapor_submissions.personnel_id', '=', 'personnels.id')
            ->where('kapor_submissions.fiscal_year', $this->fiscalYear)
            ->whereIn('kapor_submissions.kapor_item_id', $itemIds)
            // Ensure we only count active personnel if required, usually rekap includes all current personnel
            ->whereNull('personnels.deleted_at')
            ->select(
            'personnels.satker_id',
            'kapor_submissions.kapor_item_id',
            'kapor_submissions.kapor_size_id',
            DB::raw('count(*) as total')
        )
            ->groupBy('personnels.satker_id', 'kapor_submissions.kapor_item_id', 'kapor_submissions.kapor_size_id')
            ->get();

        // 3. Map Aggregated Data for O(1) Access
        // Structure: [satker_id][item_id][size_id] = count
        $this->aggregatedData = [];
        foreach ($results as $row) {
            $this->aggregatedData[$row->satker_id][$row->kapor_item_id][$row->kapor_size_id] = $row->total;
        }
    }

    public function collection()
    {
        $satkers = Satker::orderBy('name')->get();
        $data = [];
        $no = 1;

        foreach ($satkers as $satker) {
            // Get personnel count (could also be optimized but for now this one query per row is acceptable or can be eager loaded)
            $personnelCount = $satker->personnels()->count();

            $row = [
                $no++,
                $satker->name,
                $personnelCount,
            ];

            $satkerTotal = 0;

            // Loop through columns (Items -> Sizes)
            foreach ($this->items as $item) {
                foreach ($item->sizes as $size) {
                    // Get count from memory
                    $count = $this->aggregatedData[$satker->id][$item->id][$size->id] ?? 0;

                    $row[] = $count > 0 ? $count : '-';
                    $satkerTotal += $count;
                }
            }

            // Total Column
            $row[] = $satkerTotal > 0 ? $satkerTotal : '-';

            $data[] = $row;
        }

        return new Collection($data);
    }

    public function headings(): array
    {
        // Format Title
        $catLabel = str_replace('_', ' ', strtoupper($this->category));
        $itemLabel = $this->itemNameFilter ? strtoupper($this->itemNameFilter) . ' ' : '';

        $titleText = "REKAPITULASI DATA {$catLabel} {$itemLabel}PERSONEL POLDA NTB TA. {$this->fiscalYear}";

        $title = [$titleText];

        // Headers
        $headerRow1 = ['NO', 'SATUAN KERJA', 'JML PERSONIL'];
        $headerRow2 = ['', '', ''];

        foreach ($this->items as $item) {
            foreach ($item->sizes as $size) {
                $headerRow1[] = strtoupper($item->item_name);
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

            // Calculate total columns
            $totalSizes = 0;
            foreach ($this->items as $item) {
                $totalSizes += $item->sizes->count();
            }
            // Determine Last Column Index (Starting at 1)
            // NO, SATKER, JML (3) + Sizes + TOTAL (1)
            $lastColIndex = 3 + $totalSizes + 1;

            $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);

            // Merge Title
            $sheet->mergeCells("A1:{$lastColLetter}1");

            // Merge Fixed Headers
            $sheet->mergeCells('A3:A4');
            $sheet->mergeCells('B3:B4');
            $sheet->mergeCells('C3:C4');

            // Merge Item Headers
            $currentCol = 4; // Start at D
            foreach ($this->items as $item) {
                $count = $item->sizes->count();
                $startLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
                $endLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol + $count - 1);

                if ($count > 1) {
                    $sheet->mergeCells("{$startLetter}3:{$endLetter}3");
                }
                $currentCol += $count;
            }

            // Merge Total Header
            $totalLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($currentCol);
            $sheet->mergeCells("{$totalLetter}3:{$totalLetter}4");

            // Apply Borders
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

            // Header Background
            $sheet->getStyle("A3:{$lastColLetter}4")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('F2F2F2');

            // AutoSize
            foreach (range('A', $lastColLetter) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        },
        ];
    }
}
