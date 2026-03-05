<?php

namespace App\Exports;

use App\Models\PackageItem;
use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\Personnel;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PackageDetailSheet implements FromView, WithTitle, WithEvents
{
    protected $packageItem;
    protected $sheetName;
    protected $budgetPackage;
    protected $personnelCount = 0;

    public function __construct(PackageItem $packageItem, string $sheetName, BudgetPackage $budgetPackage)
    {
        $this->packageItem = $packageItem;
        $this->sheetName = strlen($sheetName) > 31 ? substr($sheetName, 0, 28) . '...' : $sheetName;
        $this->budgetPackage = $budgetPackage;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    private function getSizeKey()
    {
        $name = strtoupper($this->packageItem->kaporItem->item_name);

        if (str_contains($name, 'TOPI') || str_contains($name, 'PET') || str_contains($name, 'BARET') || str_contains($name, 'PECI')) return 'topi';
        if (str_contains($name, 'JILBAB')) return 'jilbab';
        if (str_contains($name, 'CELANA') || str_contains($name, 'ROK')) return 'celana';
        if (str_contains($name, 'SEPATU OLAHRAGA')) return 'sepatu_olahraga';
        if (str_contains($name, 'SEPATU')) return 'sepatu_dinas';
        if (str_contains($name, 'JAKET')) return 'jaket';
        if (str_contains($name, 'OLAHRAGA')) return 'olahraga';
        if (str_contains($name, 'SABUK')) return 'sabuk';

        return 'kemeja';
    }

    public function view(): View
    {
        // Perbesar memory limit untuk data besar (12.000+ personel)
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', '300');

        $kaporItem = $this->packageItem->kaporItem;
        $sizeKey = $this->getSizeKey();

        $this->packageItem->load('recipients.satker');

        $personnelList = [];
        $grandTotal = 0;

        foreach ($this->packageItem->recipients as $recipient) {
            $filters = $recipient->recipient_filters ?? [];
            $satker = $recipient->satker;

            $query = Personnel::where('satker_id', $satker->id)
                              ->where('is_active', true)
                              ->select(['id', 'full_name', 'nrp', 'rank_id', 'jabatan', 'gender', 'kapor_sizes', 'personnel_type']);

            if (!empty($filters['personnel_type'])) {
                $mappedTypes = array_map(function ($t) {
                    $lower = strtolower($t);
                    if ($lower === 'polri') return 'Polri';
                    if ($lower === 'pns') return 'PNS';
                    if ($lower === 'pppk') return 'PPPK';
                    return $t;
                }, $filters['personnel_type']);
                $query->whereIn('personnel_type', $mappedTypes);
            }

            if (!empty($filters['gender'])) {
                $query->whereIn('gender', $filters['gender']);
            }

            if (!empty($filters['rank_categories'])) {
                $query->whereHas('rank', function ($q) use ($filters) {
                    $q->whereIn('category', $filters['rank_categories']);
                });
            }

            // Chunk untuk hemat memori
            $query->with('rank:id,name')->chunk(500, function ($personnels) use ($satker, $sizeKey, &$personnelList, &$grandTotal) {
                foreach ($personnels as $p) {
                    $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                    $sizeVal = $sizes[$sizeKey] ?? null;
                    $sizeValStr = (string)$sizeVal;

                    if (empty($sizeValStr) || $sizeValStr == '-' || $sizeValStr == 'null') {
                        $sizeValStr = '-';
                    }

                    $personnelList[] = [
                        'full_name'   => $p->full_name,
                        'nrp'         => $p->nrp ?? '-',
                        'rank_name'   => $p->rank?->name ?? '-',
                        'jabatan'     => $p->jabatan ?? '-',
                        'satker_name' => $satker->name,
                        'gender'      => $p->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                        'size'        => $sizeValStr,
                    ];

                    $grandTotal++;
                }
            });
        }

        $this->personnelCount = $grandTotal;
        $settings = InvoiceSetting::getSettings();

        return view('admin.exports.detail_sheet', [
            'packageItem'   => $this->packageItem,
            'kaporItem'     => $kaporItem,
            'budgetPackage' => $this->budgetPackage,
            'personnelList' => $personnelList,
            'grandTotal'    => $grandTotal,
            'settings'      => $settings,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ═══ HITUNG POSISI BARIS ═══
                // Baris 1-3: Kop surat (pojok kiri, A-C — colspan di HTML)
                // Baris 4: kosong
                // Baris 5-6: Judul dokumen (colspan di HTML)
                // Baris 7: kosong
                // Baris 8: Header tabel
                $headerRow = 8;
                $firstDataRow = $headerRow + 1;
                $lastDataRow = $headerRow + max($this->personnelCount, 0);
                $footerRow = $lastDataRow + 1;

                // ═══ DEFAULT FONT ═══
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

                // ═══ KOP SURAT (Baris 1-3: bold, font 11 — merge handled by colspan) ═══
                $sheet->getStyle('A1:C3')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1:C3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Garis bawah kop surat
                $sheet->getStyle('A3:C3')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setRGB('000000');

                // ═══ JUDUL DOKUMEN (Baris 5-6: bold, font 11, underline — merge by colspan) ═══
                $sheet->getStyle('A5:H6')->getFont()->setBold(true)->setSize(11)->setUnderline(true);
                $sheet->getStyle('A5:H6')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ═══ HEADER TABEL ═══
                $headerRange = "A{$headerRow}:H{$headerRow}";
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle($headerRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($headerRow)->setRowHeight(22);

                // ═══ DATA ROWS (tidak bold) ═══
                if ($this->personnelCount > 0) {
                    $dataRange = "A{$firstDataRow}:H{$lastDataRow}";
                    $sheet->getStyle($dataRange)->getFont()->setBold(false)->setSize(10);
                    $sheet->getStyle($dataRange)->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    // Center: NO(A), NRP(C), PANGKAT(D), JK(G), UKURAN(H)
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$firstDataRow}:D{$lastDataRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("G{$firstDataRow}:H{$lastDataRow}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // ═══ FOOTER TOTAL ═══
                $sheet->getStyle("A{$footerRow}:H{$footerRow}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$footerRow}:H{$footerRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ═══ BORDER TABEL (hitam tipis) ═══
                $fullTableRange = "A{$headerRow}:H{$footerRow}";
                $sheet->getStyle($fullTableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('000000');

                // ═══ TANDA TANGAN (F-H, centered — merge by colspan) ═══
                $ttdStartRow = $footerRow + 2;
                for ($r = $ttdStartRow; $r <= $ttdStartRow + 8; $r++) {
                    $sheet->getStyle("F{$r}:H{$r}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                // Jabatan bold
                $jabatanRow = $ttdStartRow + 2;
                $sheet->getStyle("F{$jabatanRow}")->getFont()->setBold(true);
                // Nama bold + underline
                $namaRow = $ttdStartRow + 7;
                $sheet->getStyle("F{$namaRow}")->getFont()->setBold(true)->setUnderline(true);

                // ═══ COLUMN WIDTHS ═══
                $sheet->getColumnDimension('A')->setWidth(6);   // NO
                $sheet->getColumnDimension('B')->setWidth(30);  // NAMA
                $sheet->getColumnDimension('C')->setWidth(22);  // NRP/NIP
                $sheet->getColumnDimension('D')->setWidth(18);  // PANGKAT
                $sheet->getColumnDimension('E')->setWidth(25);  // JABATAN
                $sheet->getColumnDimension('F')->setWidth(20);  // SATKER
                $sheet->getColumnDimension('G')->setWidth(14);  // JK
                $sheet->getColumnDimension('H')->setWidth(12);  // UKURAN
            },
        ];
    }
}
