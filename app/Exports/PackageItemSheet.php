<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\PackageItem;
use App\Models\Personnel;
use App\Models\Satker;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PackageItemSheet implements FromView, ShouldAutoSize, WithEvents, WithTitle
{
    protected $packageItem;

    protected $sheetName;

    protected $budgetPackage;

    protected $matrixCount = 0;

    protected $filteredSizeCount = 0; // jumlah ukuran setelah difilter gender

    protected $gender; // 'L' = Pria, 'P' = Wanita, null = semua

    protected $sizeKeyOverride = null; // Override sizeKey (misal 'celana' untuk sheet celana dari item STEL)

    protected $sizeLabel = null; // Label tambahan (misal 'Celana') untuk judul sheet

    protected $overrideSizes = null; // Override daftar ukuran (array string)

    protected $combinedGender = false; // True = gabung pria+wanita dalam 1 sheet

    public function __construct(PackageItem $packageItem, string $sheetName, BudgetPackage $budgetPackage, ?string $gender = null, ?string $sizeKeyOverride = null, ?string $sizeLabel = null, ?array $overrideSizes = null, bool $combinedGender = false)
    {
        $this->packageItem = $packageItem;
        $this->sheetName = strlen($sheetName) > 31 ? substr($sheetName, 0, 28).'...' : $sheetName;
        $this->budgetPackage = $budgetPackage;
        $this->gender = $gender;
        $this->sizeKeyOverride = $sizeKeyOverride;
        $this->sizeLabel = $sizeLabel;
        $this->overrideSizes = $overrideSizes;
        $this->combinedGender = $combinedGender;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    private function getSizeKey()
    {
        // Jika ada override (misal sheet celana dari item STEL), langsung pakai
        if ($this->sizeKeyOverride !== null) {
            return $this->sizeKeyOverride;
        }

        $name = strtoupper($this->packageItem->kaporItem->item_name);

        if (str_contains($name, 'TOPI') || str_contains($name, 'PET') || str_contains($name, 'BARET') || str_contains($name, 'PECI')) {
            return 'topi';
        }
        if (str_contains($name, 'JILBAB')) {
            return 'jilbab';
        }
        if (str_contains($name, 'CELANA') || str_contains($name, 'ROK')) {
            return 'celana';
        }
        if (str_contains($name, 'SEPATU OLAHRAGA')) {
            return 'sepatu_olahraga';
        }
        if (str_contains($name, 'SEPATU')) {
            return 'sepatu_dinas';
        }
        if (str_contains($name, 'JAKET')) {
            return 'jaket';
        }
        if (str_contains($name, 'OLAHRAGA')) {
            return 'olahraga';
        }
        if (str_contains($name, 'SABUK')) {
            return 'sabuk';
        }

        return 'kemeja';
    }

    /**
     * Build matrix gabungan untuk sheet olahraga (L dan P di baris yang sama per satker)
     */
    private function buildCombinedMatrix(string $sizeKey, array $availableSizes): array
    {
        $matrix = [];
        $totalPerSizePria = array_fill_keys($availableSizes, 0);
        $totalPerSizeWanita = array_fill_keys($availableSizes, 0);
        $grandTotalPria = 0;
        $grandTotalWanita = 0;

        foreach ($this->packageItem->recipients as $recipient) {
            $filters = $recipient->recipient_filters ?? [];
            $satker = $recipient->satker;

            $query = Personnel::where('satker_id', $satker->id)
                ->where('is_active', true);

            if (! empty($filters['personnel_type'])) {
                $mappedTypes = array_map(function ($t) {
                    $lower = strtolower($t);
                    if ($lower === 'polri') return 'Polri';
                    if ($lower === 'pns') return 'PNS';
                    if ($lower === 'pppk') return 'PPPK';
                    return $t;
                }, $filters['personnel_type']);
                $query->whereIn('personnel_type', $mappedTypes);
            }

            if (! empty($filters['gender'])) {
                $query->whereIn('gender', $filters['gender']);
            }

            if (! empty($filters['rank_categories'])) {
                $query->whereHas('rank', function ($q) use ($filters) {
                    $q->whereIn('category', $filters['rank_categories']);
                });
            }

            if (! empty($filters['keterangan'])) {
                $query->whereIn('keterangan', $filters['keterangan']);
            }

            if (! empty($filters['golongan'])) {
                $query->whereIn('golongan', $filters['golongan']);
            }

            $personnels = $query->get(['gender', 'kapor_sizes']);

            $row = [
                'satker_name' => $satker->name,
                'sizes_pria' => array_fill_keys($availableSizes, 0),
                'total_pria' => 0,
                'sizes_wanita' => array_fill_keys($availableSizes, 0),
                'total_wanita' => 0,
            ];

            foreach ($personnels as $p) {
                $gen = $p->gender;
                $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                $sizeVal = $sizes[$sizeKey] ?? null;
                $sizeValStr = (string) $sizeVal;

                if (! empty($sizeValStr) && $sizeValStr !== '-' && $sizeValStr !== 'null' && in_array($sizeValStr, $availableSizes)) {
                    if ($gen === 'L') {
                        $row['sizes_pria'][$sizeValStr]++;
                        $totalPerSizePria[$sizeValStr]++;
                        $row['total_pria']++;
                        $grandTotalPria++;
                    } elseif ($gen === 'P') {
                        $row['sizes_wanita'][$sizeValStr]++;
                        $totalPerSizeWanita[$sizeValStr]++;
                        $row['total_wanita']++;
                        $grandTotalWanita++;
                    }
                }
            }

            if ($row['total_pria'] > 0 || $row['total_wanita'] > 0) {
                $matrix[] = $row;
            }
        }

        return compact('matrix', 'totalPerSizePria', 'totalPerSizeWanita', 'grandTotalPria', 'grandTotalWanita');
    }

    /**
     * Build matrix data per satker untuk gender tertentu.
     */
    private function buildMatrix(string $sizeKey, array $availableSizes, ?string $genderFilter): array
    {
        $matrix = [];
        $totalPerSize = array_fill_keys($availableSizes, 0);
        $grandTotal = 0;

        foreach ($this->packageItem->recipients as $recipient) {
            $filters = $recipient->recipient_filters ?? [];
            $satker = $recipient->satker;

            $query = Personnel::where('satker_id', $satker->id)
                ->where('is_active', true);

            if ($genderFilter !== null) {
                $query->where('gender', $genderFilter);
            }

            if (! empty($filters['personnel_type'])) {
                $mappedTypes = array_map(function ($t) {
                    $lower = strtolower($t);
                    if ($lower === 'polri') return 'Polri';
                    if ($lower === 'pns') return 'PNS';
                    if ($lower === 'pppk') return 'PPPK';
                    return $t;
                }, $filters['personnel_type']);
                $query->whereIn('personnel_type', $mappedTypes);
            }

            if (! empty($filters['gender'])) {
                $query->whereIn('gender', $filters['gender']);
            }

            if (! empty($filters['rank_categories'])) {
                $query->whereHas('rank', function ($q) use ($filters) {
                    $q->whereIn('category', $filters['rank_categories']);
                });
            }

            if (! empty($filters['keterangan'])) {
                $query->whereIn('keterangan', $filters['keterangan']);
            }

            if (! empty($filters['golongan'])) {
                $query->whereIn('golongan', $filters['golongan']);
            }

            $personnels = $query->get(['kapor_sizes']);

            $row = [
                'satker_name' => $satker->name,
                'sizes' => array_fill_keys($availableSizes, 0),
                'row_total' => 0,
            ];

            foreach ($personnels as $p) {
                $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                $sizeVal = $sizes[$sizeKey] ?? null;
                $sizeValStr = (string) $sizeVal;

                if (! empty($sizeValStr) && $sizeValStr !== '-' && $sizeValStr !== 'null' && in_array($sizeValStr, $availableSizes)) {
                    $row['sizes'][$sizeValStr]++;
                    $totalPerSize[$sizeValStr]++;
                }

                $row['row_total']++;
                $grandTotal++;
            }

            if ($row['row_total'] > 0) {
                $matrix[] = $row;
            }
        }

        return compact('matrix', 'totalPerSize', 'grandTotal');
    }

    public function view(): View
    {
        $kaporItem = $this->packageItem->kaporItem;
        $sizeKey = $this->getSizeKey();

        // Tentukan availableSizes
        if ($this->overrideSizes !== null) {
            $availableSizes = $this->overrideSizes;
        } else {
            $sizesQuery = $kaporItem->sizes()->orderBy('sort_order');
            if ($this->gender !== null) {
                $sizesQuery->where(function ($q) {
                    $q->where('gender', $this->gender)
                      ->orWhereNull('gender');
                });
            } else {
                // Combined mode: ambil ukuran dari gender pertama (sama untuk kedua gender di olahraga)
                $sizesQuery->where(function ($q) {
                    $q->where('gender', 'L')->orWhereNull('gender');
                });
            }
            $sizeObjects = $sizesQuery->get();
            $availableSizes = $sizeObjects->pluck('size_label')->toArray();
        }
        $this->filteredSizeCount = count($availableSizes);

        if (empty($availableSizes)) {
            $availableSizes = ['-'];
        }

        $this->packageItem->load('recipients.satker');
        $settings = InvoiceSetting::getSettings();

        // ── MODE COMBINED (Pria + Wanita dalam 1 sheet, menyamping) ──
        if ($this->combinedGender) {
            $data = $this->buildCombinedMatrix($sizeKey, $availableSizes);

            $this->matrixCount = count($data['matrix']);

            return view('admin.exports.recap_sheet_combined', [
                'packageItem' => $this->packageItem,
                'kaporItem' => $kaporItem,
                'budgetPackage' => $this->budgetPackage,
                'availableSizes' => $availableSizes,
                'matrix' => $data['matrix'],
                'totalPerSizePria' => $data['totalPerSizePria'],
                'grandTotalPria' => $data['grandTotalPria'],
                'totalPerSizeWanita' => $data['totalPerSizeWanita'],
                'grandTotalWanita' => $data['grandTotalWanita'],
                'settings' => $settings,
                'sizeLabel' => $this->sizeLabel,
            ]);
        }

        // ── MODE NORMAL (1 gender per sheet) ──
        $data = $this->buildMatrix($sizeKey, $availableSizes, $this->gender);

        $this->matrixCount = count($data['matrix']);

        $genderLabel = match($this->gender) {
            'L' => 'PRIA',
            'P' => 'WANITA',
            default => null,
        };

        return view('admin.exports.recap_sheet', [
            'packageItem' => $this->packageItem,
            'kaporItem' => $kaporItem,
            'budgetPackage' => $this->budgetPackage,
            'availableSizes' => $availableSizes,
            'matrix' => $data['matrix'],
            'totalPerSize' => $data['totalPerSize'],
            'grandTotal' => $data['grandTotal'],
            'settings' => $settings,
            'genderLabel' => $genderLabel,
            'sizeLabel' => $this->sizeLabel,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ═══ HITUNG POSISI BARIS ═══
                $headerStartRow = 7;
                $headerRowCount = $this->combinedGender ? 4 : 2;
                $firstDataRow = $headerStartRow + $headerRowCount; // baris 9 atau 11
                $lastDataRow = $firstDataRow + max($this->matrixCount, 0) - 1;
                $footerRow = $lastDataRow + 1;

                // Hitung jumlah kolom
                $totalSizeCols = $this->filteredSizeCount;
                if ($this->combinedGender) {
                    $totalCols = 2 + ($totalSizeCols * 2) + 3; // NO, SATKER + 2*(sizes) + 2*(JML) + 1*(TOTAL)
                } else {
                    $totalCols = 2 + $totalSizeCols + 1;
                }
                $lastColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

                // ═══ DEFAULT FONT ═══
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

                // ═══ KOP SURAT (Baris 1-3: bold, font 11, merge seluruh lebar) ═══
                $sheet->mergeCells("A1:{$lastColLetter}1");
                $sheet->mergeCells("A2:{$lastColLetter}2");
                $sheet->mergeCells("A3:{$lastColLetter}3");
                $sheet->getStyle("A1:{$lastColLetter}3")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A1:{$lastColLetter}3")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Garis bawah kop surat
                $sheet->getStyle("A3:{$lastColLetter}3")->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setRGB('000000');

                // ═══ JUDUL DOKUMEN (Baris 5: bold, font 11, underline, center) ═══
                $sheet->mergeCells("A5:{$lastColLetter}5");
                $sheet->getStyle("A5:{$lastColLetter}5")->getFont()->setBold(true)->setSize(11)->setUnderline(true);
                $sheet->getStyle("A5:{$lastColLetter}5")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ═══ HEADER TABEL ═══
                $headerRange = "A{$headerStartRow}:{$lastColLetter}".($headerStartRow + $headerRowCount - 1);
                $sheet->getStyle($headerRange)->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle($headerRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // ═══ BORDER TABEL ═══
                $fullTableRange = "A{$headerStartRow}:{$lastColLetter}{$footerRow}";
                $sheet->getStyle($fullTableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('000000');

                // ═══ FOOTER TOTAL ═══
                $sheet->getStyle("A{$footerRow}:{$lastColLetter}{$footerRow}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$footerRow}:{$lastColLetter}{$footerRow}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ═══ TANDA TANGAN (pojok kanan, centered) ═══
                $ttdStartRow = $footerRow + 2;
                // Hitung kolom F-H equivalent (3 kolom terakhir)
                $ttdStartCol = max($totalCols - 2, 1);
                $ttdStartColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ttdStartCol);

                for ($r = $ttdStartRow; $r <= $ttdStartRow + 8; $r++) {
                    $sheet->getStyle("{$ttdStartColLetter}{$r}:{$lastColLetter}{$r}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                // Jabatan bold
                $jabatanRow = $ttdStartRow + 2;
                $sheet->getStyle("{$ttdStartColLetter}{$jabatanRow}")->getFont()->setBold(true);
                // Nama bold + underline
                $namaRow = $ttdStartRow + 7;
                $sheet->getStyle("{$ttdStartColLetter}{$namaRow}")->getFont()->setBold(true)->setUnderline(true);

                // ═══ COLUMN WIDTHS ═══
                $sheet->getColumnDimension('A')->setWidth(6);   // NO
                $sheet->getColumnDimension('B')->setWidth(30);  // SATKER
            },
        ];
    }
}
