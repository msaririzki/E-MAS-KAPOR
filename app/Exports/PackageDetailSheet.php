<?php

namespace App\Exports;

use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\PackageItem;
use App\Models\Personnel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PackageDetailSheet implements FromArray, WithEvents, WithTitle
{
    protected $packageItem;

    protected $sheetName;

    protected $budgetPackage;

    protected $personnelCount = 0;

    protected $rows = [];

    public function __construct(PackageItem $packageItem, string $sheetName, BudgetPackage $budgetPackage)
    {
        $this->packageItem = $packageItem;
        $this->sheetName = strlen($sheetName) > 31 ? substr($sheetName, 0, 28).'...' : $sheetName;
        $this->budgetPackage = $budgetPackage;
        $this->buildData();
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
        if (str_contains($name, 'JAKET') || str_contains($name, 'ROMPI')) {
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

    private function buildData(): void
    {
        ini_set('memory_limit', '2G');
        set_time_limit(0);

        $kaporItem = $this->packageItem->kaporItem;
        $sizeKey = $this->getSizeKey();

        $this->packageItem->load('recipients.satker');
        $settings = InvoiceSetting::getSettings();

        // Kop surat
        $this->rows[] = [$settings->organization_name ?? 'KEPOLISIAN NEGARA REPUBLIK INDONESIA', '', ''];
        $this->rows[] = [$settings->header_title ?? 'DAERAH NUSA TENGGARA BARAT', '', ''];
        $this->rows[] = ['BIRO LOGISTIK', '', ''];
        $this->rows[] = ['']; // Baris kosong

        // Judul
        $this->rows[] = ['DAFTAR NOMINATIF PENERIMA '.strtoupper($kaporItem->item_name)];
        $this->rows[] = [strtoupper($this->budgetPackage->name).' T.A. '.($this->budgetPackage->budgetYear->year ?? '')];
        $this->rows[] = ['']; // Baris kosong

        // Header tabel
        $this->rows[] = ['NO', 'NAMA', 'NRP/NIP', 'PANGKAT/GOL', 'JABATAN', 'SATKER', 'JK', 'UKURAN'];

        $no = 0;

        foreach ($this->packageItem->recipients as $recipient) {
            $filters = $recipient->recipient_filters ?? [];
            $satker = $recipient->satker;

            $query = Personnel::where('satker_id', $satker->id)
                ->where('is_active', true)
                ->select(['id', 'full_name', 'nrp', 'rank_id', 'jabatan', 'gender', 'kapor_sizes', 'personnel_type']);

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

            if (! empty($filters['keterangan'])) {
                $ketValues = $filters['keterangan'];
                $query->where(function ($q) use ($ketValues) {
                    $q->whereIn('keterangan', $ketValues)
                        ->orWhereIn('keterangan_2', $ketValues)
                        ->orWhereIn('keterangan_3', $ketValues)
                        ->orWhereIn('keterangan_4', $ketValues);
                });
            }

            if (! empty($filters['golongan'])) {
                $query->whereIn('golongan', $filters['golongan']);
            }

            // Chunk untuk hemat memori
            $query->with('rank:id,name')->chunk(500, function ($personnels) use ($satker, $sizeKey, &$no) {
                foreach ($personnels as $p) {
                    $sizes = is_string($p->kapor_sizes) ? json_decode($p->kapor_sizes, true) : $p->kapor_sizes;
                    $sizeVal = $sizes[$sizeKey] ?? null;
                    $sizeValStr = (string) $sizeVal;

                    if (empty($sizeValStr) || $sizeValStr == '-' || $sizeValStr == 'null') {
                        $sizeValStr = '-';
                    }

                    $no++;
                    $this->rows[] = [
                        $no,
                        $p->full_name,
                        "'".($p->nrp ?? '-'), // Prefix ' agar NRP tidak jadi angka
                        $p->rank?->name ?? '-',
                        $p->jabatan ?? '-',
                        $satker->name,
                        $p->gender === 'L' ? 'Laki-laki' : 'Perempuan',
                        $sizeValStr,
                    ];
                }
            });

            gc_collect_cycles();
        }

        $this->personnelCount = $no;

        // Footer total
        $this->rows[] = ['', 'TOTAL', '', '', '', '', '', $no.' Personel'];

        // Baris kosong
        $this->rows[] = [''];
        $this->rows[] = [''];

        // Tanda tangan
        $this->rows[] = ['', '', '', '', '', ($settings->location ?? 'Mataram').', '.now()->translatedFormat('d F Y')];
        $this->rows[] = ['', '', '', '', '', $settings->signatory_title ?? 'Kabag RenMin'];
        $this->rows[] = [''];
        $this->rows[] = ['', '', '', '', '', $settings->signatory_rank ?? ''];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = [''];
        $this->rows[] = ['', '', '', '', '', $settings->signatory_name ?? ''];
        $this->rows[] = ['', '', '', '', '', 'NRP. '.($settings->signatory_nrp ?? '')];
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // ═══ HITUNG POSISI BARIS ═══
                $headerRow = 8;
                $firstDataRow = $headerRow + 1;
                $lastDataRow = $headerRow + max($this->personnelCount, 0);
                $footerRow = $lastDataRow + 1;

                // ═══ DEFAULT FONT ═══
                $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

                // ═══ KOP SURAT (Baris 1-3) ═══
                $sheet->getStyle('A1:C3')->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle('A1:C3')->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // Merge kop surat
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                // Garis bawah kop surat
                $sheet->getStyle('A3:H3')->getBorders()->getBottom()
                    ->setBorderStyle(Border::BORDER_MEDIUM)
                    ->getColor()->setRGB('000000');

                // ═══ JUDUL DOKUMEN (Baris 5-6) ═══
                $sheet->mergeCells('A5:H5');
                $sheet->mergeCells('A6:H6');
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

                // ═══ DATA ROWS ═══
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

                // ═══ BORDER TABEL ═══
                $fullTableRange = "A{$headerRow}:H{$footerRow}";
                $sheet->getStyle($fullTableRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->getColor()->setRGB('000000');

                // ═══ TANDA TANGAN ═══
                $ttdStartRow = $footerRow + 2;
                for ($r = $ttdStartRow; $r <= $ttdStartRow + 8; $r++) {
                    $sheet->getStyle("F{$r}:H{$r}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $jabatanRow = $ttdStartRow + 1;
                $sheet->getStyle("F{$jabatanRow}")->getFont()->setBold(true);
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
