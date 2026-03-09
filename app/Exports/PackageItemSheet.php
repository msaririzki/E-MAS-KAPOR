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

    public function __construct(PackageItem $packageItem, string $sheetName, BudgetPackage $budgetPackage, ?string $gender = null)
    {
        $this->packageItem = $packageItem;
        // Trim just in case
        $this->sheetName = strlen($sheetName) > 31 ? substr($sheetName, 0, 28).'...' : $sheetName;
        $this->budgetPackage = $budgetPackage;
        $this->gender = $gender;
    }

    public function title(): string
    {
        return $this->sheetName;
    }

    private function getSizeKey()
    {
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

    public function view(): View
    {
        $kaporItem = $this->packageItem->kaporItem;
        $sizeKey = $this->getSizeKey();

        // Dapatkan ukuran yang sesuai dengan gender sheet ini dari master data
        // gender = 'L' hanya ambil ukuran pria (gender L atau null)
        // gender = 'P' hanya ambil ukuran wanita (gender P atau null)
        $sizesQuery = $kaporItem->sizes()->orderBy('sort_order');
        if ($this->gender !== null) {
            $sizesQuery->where(function ($q) {
                $q->where('gender', $this->gender)
                  ->orWhereNull('gender');
            });
        }
        $sizeObjects = $sizesQuery->get();
        $availableSizes = $sizeObjects->pluck('size_label')->toArray();
        $this->filteredSizeCount = count($availableSizes); // simpan untuk registerEvents()

        if (empty($availableSizes)) {
            // Fallback kalau item tidak punya ukuran standard
            $availableSizes = ['-'];
        }

        $matrix = [];
        $totalPerSize = array_fill_keys($availableSizes, 0);
        $totalPerSize['UNKNOWN'] = 0; // Untuk yang ukurannya kosong/tidak standard
        $grandTotal = 0;

        // Load recipients dan build query per satker
        $this->packageItem->load('recipients.satker');

        foreach ($this->packageItem->recipients as $recipient) {
            $filters = $recipient->recipient_filters ?? [];
            $satker = $recipient->satker;

            $query = Personnel::where('satker_id', $satker->id)
                ->where('is_active', true);

            // Filter berdasarkan gender jika ditentukan
            if ($this->gender !== null) {
                $query->where('gender', $this->gender);
            }

            // Apply filters (sama dengan PackageItemRecipient->calculateMatchedCount)
            if (! empty($filters['personnel_type'])) {
                $mappedTypes = array_map(function ($t) {
                    $lower = strtolower($t);
                    if ($lower === 'polri') {
                        return 'Polri';
                    }
                    if ($lower === 'pns') {
                        return 'PNS';
                    }
                    if ($lower === 'pppk') {
                        return 'PPPK';
                    }

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

            // Hitung Group By size (mengambil array JSON kapor_sizes)
            $personnels = $query->get(['kapor_sizes']);

            $row = [
                'satker_name' => $satker->name,
                'sizes' => array_fill_keys($availableSizes, 0),
                'unknown' => 0,
                'row_total' => 0,
            ];

            foreach ($personnels as $p) {
                // Ambil nilai dari JSON (jika ada)
                $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                $sizeVal = $sizes[$sizeKey] ?? null;
                $sizeValStr = (string) $sizeVal;

                if (empty($sizeValStr) || $sizeValStr == '-' || $sizeValStr == 'null') {
                    $row['unknown']++;
                    $totalPerSize['UNKNOWN']++;
                } elseif (in_array($sizeValStr, $availableSizes)) {
                    $row['sizes'][$sizeValStr]++;
                    $totalPerSize[$sizeValStr]++;
                } else {
                    // Jika ada size yang tidak tercatat di standard, masukkan ke unknown agar tidak hilang
                    $row['unknown']++;
                    $totalPerSize['UNKNOWN']++;
                }

                $row['row_total']++;
                $grandTotal++;
            }

            // Hanya tampilkan baris satker kalau jumlahnya > 0
            if ($row['row_total'] > 0) {
                $matrix[] = $row;
            }
        }

        $this->matrixCount = count($matrix);
        $settings = InvoiceSetting::getSettings();

        // Sheet pria tidak perlu kolom 'Tdk Diketahui' karena pakai ukuran angka
        $hideUnknown = ($this->gender === 'L');

        // Label gender untuk judul
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
            'matrix' => $matrix,
            'totalPerSize' => $totalPerSize,
            'grandTotal' => $grandTotal,
            'settings' => $settings,
            'hideUnknown' => $hideUnknown,
            'genderLabel' => $genderLabel,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ═══ HITUNG POSISI BARIS ═══
                // Baris 1-3: Kop surat (pojok kiri)
                // Baris 4: kosong
                // Baris 5: Judul dokumen
                // Baris 6: kosong
                // Baris 7-8: Header tabel (2 baris)
                $headerStartRow = 7;
                $firstDataRow = $headerStartRow + 2; // baris 9
                $lastDataRow = $firstDataRow + max($this->matrixCount, 0) - 1;
                $footerRow = $lastDataRow + 1;

                // Hitung jumlah kolom dari ukuran yang sudah difilter gender
                $totalSizeCols = $this->filteredSizeCount;
                // NO + SATKER + sizes + (UNKNOWN jika bukan pria) + JML
                $hideUnknown = ($this->gender === 'L');
                $totalCols = 2 + $totalSizeCols + ($hideUnknown ? 0 : 1) + 1;
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
                $sheet->getStyle("A5:{$lastColLetter}5")->getFont()->setBold(true)->setSize(11)->setUnderline(true);
                $sheet->getStyle("A5:{$lastColLetter}5")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ═══ HEADER TABEL (Baris 7-8) ═══
                $headerRange = "A{$headerStartRow}:{$lastColLetter}".($headerStartRow + 1);
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
