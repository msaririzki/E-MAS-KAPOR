<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\Satker;
use Exception;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Table;

class SppmWordExportService
{
    private BudgetCalculationService $calcService;

    public function __construct(BudgetCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * Generate SPPM Word documents for all applicable satkers in the package.
     * Returns an array mapping satker_name to file_path.
     */
    public function generateForPackage(BudgetPackage $package, array $suratData): array
    {
        $data = $this->calcService->calculatePackage($package);
        $settings = InvoiceSetting::getSettings();
        $files = [];

        // We need to group items by recipient (satker)
        $satkerData = [];

        foreach ($data['items'] as $item) {
            foreach ($item['recipients'] as $recipient) {
                if ($recipient['matched_count'] <= 0) {
                    continue;
                }

                $satkerId = $recipient['satker_id'];
                if (!isset($satkerData[$satkerId])) {
                    $satkerData[$satkerId] = [
                        'satker' => Satker::find($satkerId, ['*']),
                        'items' => [],
                    ];
                }

                $satkerData[$satkerId]['items'][] = [
                    'package_item_id' => $item['package_item_id'],
                    'item_name' => $item['item_name'],
                    'category' => $item['category'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'qty' => $recipient['matched_count'],
                    'total' => $recipient['subtotal'],
                ];
            }
        }

        foreach ($satkerData as $sd) {
            $filePath = $this->generateForSatker($package, $sd['satker'], $sd['items'], $suratData, $settings);
            // safe filename
            $safeSatkerName = preg_replace('/[^A-Za-z0-9\-]/', '_', $sd['satker']->name);
            $files[$safeSatkerName] = $filePath;
        }

        return $files;
    }

    /**
     * Generate a single Word document for a specific satker.
     */
    private function generateForSatker(BudgetPackage $package, Satker $satker, array $itemsData, array $suratData, InvoiceSetting $settings): string
    {
        $phpWord = new PhpWord();
        
        // Define styles
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => Converter::cmToTwip(1.5),
            'marginLeft' => Converter::cmToTwip(2.5),
            'marginRight' => Converter::cmToTwip(2),
            'marginBottom' => Converter::cmToTwip(2),
        ]);

        // --- KOP SURAT ---
        $kopTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $kopTable->addRow();
        $cellKiri = $kopTable->addCell(Converter::cmToTwip(8));
        
        // Text kop surat
        $cellKiri->addText('KEPOLISIAN NEGARA REPUBLIK INDONESIA', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cellKiri->addText('DAERAH NUSA TENGGARA BARAT', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cellKiri->addText('BIRO LOGISTIK', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        // Garis batas kop (menggunakan border bottom pada textrun terakhir)
        $textRun = $cellKiri->addTextRun(['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $textRun->addText('____________________________________________', ['size' => 10]);

        $cellKanan = $kopTable->addCell(Converter::cmToTwip(8));
        $cellKanan->addText('Bentuk : 007/LOG/POLRI', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        $cellKanan->addText('Lembar ke', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

        $section->addTextBreak(1);

        // --- JUDUL ---
        $section->addText('SURAT PERINTAH PENGELUARAN MATERIIL', ['bold' => true, 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addText('(S.P.P.M)', ['bold' => true, 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $section->addText('Nomor: ' . ($suratData['sppm_number'] ?? ''), ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        $section->addTextBreak(1);

        // --- INFO SURAT ---
        $infoTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        
        $this->addInfoRow($infoTable, 'Kepada Kepala Gudang Materiil Golongan', ':', 'BIRO LOGISTIK POLDA NTB');
        $this->addInfoRow($infoTable, 'Diperintahkan untuk mengeluarkan kepada', ':', strtoupper($satker->name));
        $this->addInfoRow($infoTable, 'Berdasarkan', ':', $suratData['sprin_number'] ?? '');
        $this->addInfoRow($infoTable, '', '', 'TANGGAL ' . strtoupper($suratData['date'] ?? ''));

        $section->addTextBreak(1);
        $section->addText('Materiil sebagai berikut :');

        // --- TABEL MATERIIL ---
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ];
        $phpWord->addTableStyle('Materiil Table', $tableStyle);
        $table = $section->addTable('Materiil Table');

        // Header definitions
        $headerFontStyle = ['bold' => true, 'size' => 10];
        $cellCenter = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'valign' => 'center'];
        
        $table->addRow();
        $table->addCell(500, ['vMerge' => 'restart'])->addText('No', $headerFontStyle, $cellCenter);
        $table->addCell(4000, ['vMerge' => 'restart'])->addText('Kode dan Nama Materiil', $headerFontStyle, $cellCenter);
        $table->addCell(1200, ['vMerge' => 'restart'])->addText('Satuan', $headerFontStyle, $cellCenter);
        $table->addCell(2000, ['gridSpan' => 2])->addText('Banyaknya', $headerFontStyle, $cellCenter);
        $table->addCell(2500, ['gridSpan' => 2])->addText('Harga (Rp)', $headerFontStyle, $cellCenter);
        $table->addCell(1000, ['vMerge' => 'restart'])->addText('Ket', $headerFontStyle, $cellCenter);

        $table->addRow();
        $table->addCell(null, ['vMerge' => 'continue']);
        $table->addCell(null, ['vMerge' => 'continue']);
        $table->addCell(null, ['vMerge' => 'continue']);
        $table->addCell(800)->addText('Angka', $headerFontStyle, $cellCenter);
        $table->addCell(1200)->addText('Huruf', $headerFontStyle, $cellCenter);
        $table->addCell(1200)->addText('Satuan', $headerFontStyle, $cellCenter);
        $table->addCell(1300)->addText('Jumlah', $headerFontStyle, $cellCenter);
        $table->addCell(null, ['vMerge' => 'continue']);

        // Data rows
        $no = 1;
        $rowStyle = ['size' => 10];
        foreach ($itemsData as $item) {
            $table->addRow();
            $table->addCell(500, ['valign' => 'center'])->addText($no++, $rowStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $table->addCell(4000, ['valign' => 'center'])->addText(strtoupper($item['item_name']), $rowStyle);
            $table->addCell(1200, ['valign' => 'center'])->addText(strtoupper($item['unit']), $rowStyle, $cellCenter);
            $table->addCell(800, ['valign' => 'center'])->addText($item['qty'], $rowStyle, $cellCenter);
            $table->addCell(1200, ['valign' => 'center'])->addText($this->numberToWordsDigit($item['qty']), $rowStyle, $cellCenter);
            $table->addCell(1200, ['valign' => 'center'])->addText(number_format($item['price'], 0, ',', '.'), $rowStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $table->addCell(1300, ['valign' => 'center'])->addText(number_format($item['total'], 0, ',', '.'), $rowStyle, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $table->addCell(1000, ['valign' => 'center'])->addText('', $rowStyle);
        }

        $section->addTextBreak(2);

        // --- TANDA TANGAN ---
        $ttdTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
        $ttdTable->addRow();
        
        $cellTtdKiri = $ttdTable->addCell(Converter::cmToTwip(8));
        $cellTtdKiri->addText('Untuk Penerima :', ['size' => 11]);
        $cellTtdKiri->addTextBreak(1);
        $textRunNama = $cellTtdKiri->addTextRun();
        $textRunNama->addText('Nama', ['size' => 11]);
        $textRunNama->addText("\t: ..............................................................", ['size' => 11]);
        
        $cellTtdKiri->addTextBreak(1);
        $textRunPangkat = $cellTtdKiri->addTextRun();
        $textRunPangkat->addText('Pangkat', ['size' => 11]);
        $textRunPangkat->addText("\t: ..............................................................", ['size' => 11]);
        
        $cellTtdKiri->addTextBreak(1);
        $textRunNrp = $cellTtdKiri->addTextRun();
        $textRunNrp->addText('NRP/NIP', ['size' => 11]);
        $textRunNrp->addText("\t: ..............................................................", ['size' => 11]);
        
        $cellTtdKiri->addTextBreak(1);
        $textRunJabatan = $cellTtdKiri->addTextRun();
        $textRunJabatan->addText('Jabatan', ['size' => 11]);
        $textRunJabatan->addText("\t: ..............................................................", ['size' => 11]);

        $cellTtdKanan = $ttdTable->addCell(Converter::cmToTwip(8));
        $cellTtdKanan->addText($settings->location . ',        ' . ($suratData['date'] ?? ''), ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cellTtdKanan->addText('a.n. ' . strtoupper($settings->organization_name), ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cellTtdKanan->addText(strtolower($settings->signatory_title), ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        $cellTtdKanan->addTextBreak(4);
        
        $cellTtdKanan->addText($settings->signatory_name ?: '..........................................', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $cellTtdKanan->addText(($settings->signatory_rank ?? '') . ' NRP ' . ($settings->signatory_nrp ?? ''), ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        
        $section->addTextBreak(2);
        
        // --- FOOTER INFO ---
        $section->addText('.........................................., Tgl. ........... ' . ($suratData['date'] ?? ''), ['size' => 11]);
        $section->addTextBreak(1);
        $section->addText('Titik Pembekalan (POLRES/SATKER POLDA)', ['size' => 11]);

        // Save to temp file
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile = tempnam(sys_get_temp_dir(), 'SPPM_');
        $objWriter->save($tempFile);

        return $tempFile;
    }

    private function addInfoRow($table, $col1, $col2, $col3)
    {
        $table->addRow();
        $table->addCell(Converter::cmToTwip(6))->addText($col1, ['size' => 11]);
        $table->addCell(Converter::cmToTwip(0.5))->addText($col2, ['size' => 11]);
        $table->addCell(Converter::cmToTwip(9.5))->addText($col3, ['size' => 11]);
    }

    /**
     * Mengkonversi angka menjadi terbilang per-digit.
     * Contoh: 29 -> "Dua Sembilan", 7 -> "Tujuh"
     */
    public function numberToWordsDigit(int $number): string
    {
        $wordsMap = [
            '0' => 'Nol',
            '1' => 'Satu',
            '2' => 'Dua',
            '3' => 'Tiga',
            '4' => 'Empat',
            '5' => 'Lima',
            '6' => 'Enam',
            '7' => 'Tujuh',
            '8' => 'Delapan',
            '9' => 'Sembilan',
        ];

        $strNum = (string)$number;
        $words = [];
        
        for ($i = 0; $i < strlen($strNum); $i++) {
            $digit = $strNum[$i];
            if (isset($wordsMap[$digit])) {
                $words[] = $wordsMap[$digit];
            }
        }

        return implode(' ', $words);
    }
}
