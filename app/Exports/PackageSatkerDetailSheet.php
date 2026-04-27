<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use App\Models\Satker;
use App\Services\ExportSignatorySettingService;
use App\Services\PackageSatkerAllocationService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class PackageSatkerDetailSheet implements FromArray, WithEvents, WithTitle
{
    private array $rows = [];

    private int $personnelCount = 0;

    private int $itemCount = 0;

    public function __construct(
        private readonly BudgetPackage $budgetPackage,
        private readonly Satker $satker,
        private readonly string $sheetName,
    ) {
        $this->buildData();
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    public function array(): array
    {
        return $this->rows;
    }

    private function buildData(): void
    {
        ini_set('memory_limit', '2G');
        set_time_limit(0);

        $settings = (object) app(ExportSignatorySettingService::class)->resolveForCurrentUser();

        $this->rows[] = [$settings->organization_name ?? 'KEPOLISIAN NEGARA REPUBLIK INDONESIA'];
        $this->rows[] = [$settings->header_title ?? 'DAERAH NUSA TENGGARA BARAT'];
        $this->rows[] = ['BIRO LOGISTIK'];
        $this->rows[] = [''];
        $this->rows[] = ['DAFTAR NOMINATIF PENERIMA BERDASARKAN SATKER'];
        $this->rows[] = [strtoupper($this->budgetPackage->name).' T.A. '.($this->budgetPackage->budgetYear->year ?? '')];
        $this->rows[] = ['SATKER: '.strtoupper($this->satker->name)];
        $this->rows[] = [''];
        $this->rows[] = [
            'NO',
            'NAMA',
            'NRP/NIP',
            'PANGKAT/GOL',
            'JABATAN',
            'BAGIAN',
            'JENIS PERSONEL',
            'JK',
            'BARANG',
            'KATEGORI',
            'UKURAN',
            'JUMLAH',
        ];

        $personnelRows = app(PackageSatkerAllocationService::class)->buildRows($this->budgetPackage, $this->satker);
        $this->itemCount = $personnelRows->sum('item_count');

        $no = 0;
        foreach ($personnelRows as $personnelRow) {
            $no++;
            $this->rows[] = [
                $no,
                $personnelRow['full_name'],
                "'".$personnelRow['nrp'],
                $personnelRow['rank'],
                $personnelRow['jabatan'],
                $personnelRow['bagian'],
                $personnelRow['personnel_type'],
                $personnelRow['gender'],
                implode("\n", $personnelRow['items']),
                implode("\n", $personnelRow['categories']),
                implode("\n", $personnelRow['sizes']),
                count($personnelRow['items']),
            ];
        }

        $this->personnelCount = $no;

        $this->rows[] = ['', 'TOTAL', '', '', '', '', '', '', '', '', '', $this->itemCount.' Item'];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = ['', '', '', '', '', '', '', '', ($settings->location ?? 'Mataram').', '.now()->translatedFormat('d F Y')];
        $this->rows[] = ['', '', '', '', '', '', '', '', $settings->signatory_title ?? 'Kabag RenMin'];
        $this->rows[] = [''];
        $this->rows[] = ['', '', '', '', '', '', '', '', $settings->signatory_rank ?? ''];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = ['', '', '', '', '', '', '', '', $settings->signatory_name ?? ''];
        $this->rows[] = ['', '', '', '', '', '', '', '', 'NRP. '.($settings->signatory_nrp ?? '')];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $headerRow = 9;
                $firstDataRow = $headerRow + 1;
                $lastDataRow = $headerRow + max($this->personnelCount, 0);
                $footerRow = $lastDataRow + 1;
                $lastCol = 'L';

                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');
                $sheet->mergeCells('A3:L3');
                $sheet->mergeCells('A5:L5');
                $sheet->mergeCells('A6:L6');
                $sheet->mergeCells('A7:L7');

                $sheet->getStyle('A1:L3')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1:L7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A3:L3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
                $sheet->getStyle('A5:L7')->getFont()->setBold(true)->setSize(11);

                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);

                if ($this->personnelCount > 0) {
                    $sheet->getStyle("A{$firstDataRow}:{$lastCol}{$lastDataRow}")->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$firstDataRow}:D{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$firstDataRow}:H{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("K{$firstDataRow}:L{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getStyle("A{$footerRow}:{$lastCol}{$footerRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$footerRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $ttdStartRow = $footerRow + 3;
                for ($row = $ttdStartRow; $row <= $ttdStartRow + 9; $row++) {
                    $sheet->mergeCells("I{$row}:L{$row}");
                    $sheet->getStyle("I{$row}:L{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $sheet->getStyle('I'.($ttdStartRow + 1))->getFont()->setBold(true);
                $sheet->getStyle('I'.($ttdStartRow + 7))->getFont()->setBold(true)->setUnderline(true);

                foreach ([
                    'A' => 6,
                    'B' => 28,
                    'C' => 20,
                    'D' => 16,
                    'E' => 26,
                    'F' => 18,
                    'G' => 16,
                    'H' => 12,
                    'I' => 30,
                    'J' => 18,
                    'K' => 12,
                    'L' => 10,
                ] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getPageSetup()
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToPage(true)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setLeft(0.35);
                $sheet->getPageMargins()->setRight(0.35);
                $sheet->getPageSetup()->setPrintArea('A1:L'.($ttdStartRow + 8));
            },
        ];
    }
}
