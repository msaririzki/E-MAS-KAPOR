<?php

namespace App\Services;

use App\Models\WarehouseSignatory;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use ZipArchive;

class WarehouseSppmExportService
{
    /**
     * Generate SPPM Word document for warehouse outflow.
     * $outflowData should contain:
     * - satker_name
     * - outflow_date
     * - recipient_name
     * - letter_number
     * - letter_date
     * - items (array of: item_name, unit, size_label, quantity)
     */
    public function generate(array $outflowData): string
    {
        $signatory = WarehouseSignatory::firstWhere('is_active', true);

        $xml = $this->buildDocumentXml($outflowData, $signatory);

        $tempFile = tempnam(sys_get_temp_dir(), 'SPPM_GUDANG_');
        $this->createDocxFromXml($tempFile, $xml);

        return $tempFile;
    }

    private function buildDocumentXml(array $data, ?WarehouseSignatory $signatory): string
    {
        $satkerName = strtoupper($data['satker_name'] ?? '-');
        $recipientName = strtoupper($data['recipient_name'] ?? '-');
        $letterNumber = $data['letter_number'] ?? '';
        $letterDateFormatted = ($data['letter_date'] ?? null) ? $this->formatDateIndonesian($data['letter_date']) : '';
        
        $outflowDate = $data['outflow_date'] ?? now()->format('Y-m-d');
        $parsedOutflowDate = Carbon::parse($outflowDate);
        $todayIndo = $this->formatDateIndonesian($outflowDate);
        
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $fullDateIndo = $parsedOutflowDate->day . ' ' . $months[$parsedOutflowDate->month] . ' ' . $parsedOutflowDate->year;
        $partialDateIndo = $months[$parsedOutflowDate->month] . ' ' . $parsedOutflowDate->year;
        $romanMonth = $romanMonths[$parsedOutflowDate->month];
        $sppmNumber = "SPPM/           /{$romanMonth}/LOG.5.16.1./{$parsedOutflowDate->year}/ROLOG";

        $sigName = $signatory ? strtoupper($signatory->name) : '..............................';
        $sigJabatan = $signatory ? strtoupper($signatory->jabatan) : '..............................';
        $sigRankLine = $signatory ? trim($signatory->pangkat . ' NRP ' . $signatory->nrp) : '..............................';
        // Normalize 'A.N.' to 'a.n.' at the start if user entered it in uppercase
        $sigSatuanKerja = $signatory && $signatory->satuan_kerja ? $signatory->satuan_kerja : '';
        if (stripos($sigSatuanKerja, 'A.N.') === 0) {
            $sigSatuanKerja = 'a.n.' . substr($sigSatuanKerja, 4);
        }
        $sigSatuanKerja = strtoupper($sigSatuanKerja);
        // Wait, if I uppercase everything, 'a.n.' becomes 'A.N.'!
        // I should uppercase everything *after* the prefix if it exists.
        if (stripos($sigSatuanKerja, 'a.n.') === 0) {
            $sigSatuanKerja = 'a.n. ' . strtoupper(trim(substr($sigSatuanKerja, 4)));
        } else {
            $sigSatuanKerja = strtoupper($sigSatuanKerja);
        }
        $sigAtribut = $signatory && $signatory->atribut ? $signatory->atribut : '';
        $sigWakil = $signatory && $signatory->wakil ? strtoupper($signatory->wakil) : '';

        $skeleton = $this->getRawSkeleton();

        $rowsXml = '';
        foreach (($data['items'] ?? []) as $index => $item) {
            $rowsXml .= $this->createItemRow($index + 1, $item);
        }

        $placeholders = [
            '{TEMPLATE_SPPM_NUMBER}'    => htmlspecialchars($sppmNumber, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SATKER_NAME}'    => htmlspecialchars($satkerName, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_DOC_NUMBER}'     => htmlspecialchars($letterNumber, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_DOC_DATE}'       => htmlspecialchars(strtoupper($letterDateFormatted), ENT_XML1, 'UTF-8'),
            '{TEMPLATE_DATE}'           => htmlspecialchars($todayIndo, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_FULL_DATE}'      => htmlspecialchars($fullDateIndo, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_PARTIAL_DATE}'   => htmlspecialchars($partialDateIndo, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SIG_SATKER}'     => htmlspecialchars($sigSatuanKerja, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SIG_JABATAN}'    => htmlspecialchars($sigJabatan, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SIG_ATRIBUT}'    => htmlspecialchars($sigAtribut, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SIG_WAKIL}'      => htmlspecialchars($sigWakil, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SIG_NAME}'       => htmlspecialchars($sigName, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_SIG_RANK_LINE}'  => htmlspecialchars($sigRankLine, ENT_XML1, 'UTF-8'),
            '{TEMPLATE_ITEM_ROWS}'      => $rowsXml,
        ];

        return strtr($skeleton, $placeholders);
    }

    private function createItemRow(int $index, array $item): string
    {
        $name = strtoupper($item['item_name'] ?? '-');
        $unit = strtoupper($item['unit'] ?? 'PCS');
        $qty = (int) ($item['quantity'] ?? 0);
        $price = (float) ($item['price'] ?? 0);
        $total = $price * $qty;

        $xml = '<w:tr><w:tblPrEx><w:tblCellMar><w:top w:w="0" w:type="dxa"/><w:bottom w:w="0" w:type="dxa"/></w:tblCellMar></w:tblPrEx>';
        $xml .= $this->createItemCell((string)$index, 'center');
        $xml .= $this->createItemCell($name, 'left');
        $xml .= $this->createItemCell($unit, 'center');
        $xml .= $this->createItemCell((string)$qty, 'center');
        $xml .= $this->createItemCell($this->numberToWordsDigit($qty), 'center');
        $xml .= $this->createItemCell(number_format($price, 0, ',', '.'), 'center');
        $xml .= $this->createItemCell(number_format($total, 0, ',', '.'), 'center');
        $xml .= $this->createItemCell('', 'left');
        $xml .= '</w:tr>';
        return $xml;
    }

    private function createItemCell(string $text, string $align): string
    {
        return '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/><w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="'.$align.'"/></w:pPr>'
            . '<w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr>'
            . '<w:t xml:space="preserve">'.htmlspecialchars($text, ENT_XML1, 'UTF-8').'</w:t></w:r></w:p></w:tc>';
    }

    private function getRawSkeleton(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblInd w:w="10" w:type="dxa"/><w:tblCellMar><w:left w:w="10" w:type="dxa"/><w:right w:w="10" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid><w:gridCol w:w="4500"/><w:gridCol w:w="5500"/></w:tblGrid><w:tr><w:tc><w:tcPr><w:tcW w:w="4500" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>KEPOLISIAN NEGARA REPUBLIK INDONESIA</w:t></w:r></w:p><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>DAERAH NUSA TENGGARA BARAT</w:t></w:r></w:p><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/><w:pBorders><w:bottom w:val="single" w:sz="6" w:space="1" w:color="000000"/></w:pBorders></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>BIRO LOGISTIK</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/></w:tcPr><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Bentuk : 007/LOG/POLRI</w:t></w:r></w:p><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="right"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Lembar ke ......................</w:t></w:r></w:p></w:tc></w:tr></w:tbl><w:p/><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr><w:t>SURAT PERINTAH PENGELUARAN MATERIIL</w:t></w:r></w:p><w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:b/><w:sz w:val="24"/><w:szCs w:val="24"/></w:rPr><w:t>(S.P.P.M)</w:t></w:r></w:p><w:p><w:pPr><w:spacing w:before="120" w:after="160"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:t>Nomor: {TEMPLATE_SPPM_NUMBER}</w:t></w:r></w:p><w:p/>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="4500"/><w:tab w:val="left" w:pos="4700"/></w:tabs><w:spacing w:after="160"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:t xml:space="preserve">Kepada Kepala Gudang Materiil Golongan</w:t></w:r><w:r><w:tab/><w:t xml:space="preserve"> : </w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:tab/><w:t>BIRO LOGISTIK POLDA NTB</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="4500"/><w:tab w:val="left" w:pos="4700"/></w:tabs><w:spacing w:after="160"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:t xml:space="preserve">Diperintahkan untuk mengeluarkan kepada</w:t></w:r><w:r><w:tab/><w:t xml:space="preserve"> : </w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:tab/><w:t>{TEMPLATE_SATKER_NAME}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="4500"/><w:tab w:val="left" w:pos="4700"/></w:tabs><w:spacing w:after="160"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:t xml:space="preserve">Berdasarkan</w:t></w:r><w:r><w:tab/><w:t xml:space="preserve"> : </w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:tab/><w:t xml:space="preserve">{TEMPLATE_DOC_NUMBER}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="4700"/></w:tabs><w:spacing w:after="160"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr><w:tab/><w:t>TANGGAL {TEMPLATE_DOC_DATE}</w:t></w:r></w:p>'
        . '<w:p/><w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblInd w:w="10" w:type="dxa"/><w:tblBorders><w:top w:val="single" w:sz="4" w:color="000000"/><w:left w:val="single" w:sz="4" w:color="000000"/><w:bottom w:val="single" w:sz="4" w:color="000000"/><w:right w:val="single" w:sz="4" w:color="000000"/><w:insideH w:val="single" w:sz="4" w:color="000000"/><w:insideV w:val="single" w:sz="4" w:color="000000"/></w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="447"/><w:gridCol w:w="3778"/><w:gridCol w:w="1060"/><w:gridCol w:w="953"/><w:gridCol w:w="826"/><w:gridCol w:w="1204"/><w:gridCol w:w="1204"/><w:gridCol w:w="520"/></w:tblGrid><w:tr><w:tc><w:tcPr><w:vMerge w:val="restart"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>No</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:vMerge w:val="restart"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Kode dan Nama Materiil</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:vMerge w:val="restart"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Satuan</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:gridSpan w:val="2"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Banyaknya</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:gridSpan w:val="2"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Harga (Rp)</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:vMerge w:val="restart"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Ket</w:t></w:r></w:p></w:tc></w:tr><w:tr><w:tc><w:tcPr><w:vMerge/></w:tcPr><w:p/></w:tc><w:tc><w:tcPr><w:vMerge/></w:tcPr><w:p/></w:tc><w:tc><w:tcPr><w:vMerge/></w:tcPr><w:p/></w:tc><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Angka</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Huruf</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Satuan</w:t></w:r></w:p></w:tc><w:tc><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Jumlah</w:t></w:r></w:p></w:tc><w:tc><w:tcPr><w:vMerge/></w:tcPr><w:p/></w:tc></w:tr>{TEMPLATE_ITEM_ROWS}</w:tbl><w:p/>'
        . '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblInd w:w="10" w:type="dxa"/></w:tblPr><w:tblGrid><w:gridCol w:w="5148"/><w:gridCol w:w="4844"/></w:tblGrid>'
        . '<w:tr>'
        // LEFT COLUMN: Untuk Penerima (Spacious for Handwriting)
        . '<w:tc><w:tcPr><w:tcW w:w="5148" w:type="dxa"/></w:tcPr>'
        . '<w:p><w:pPr><w:spacing w:after="280"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Untuk Penerima :</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="1152"/><w:tab w:val="left" w:pos="1440"/></w:tabs><w:spacing w:after="280"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Nama</w:t></w:r><w:r><w:tab/><w:t>:</w:t></w:r><w:r><w:tab/><w:t>.........................................</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="1152"/><w:tab w:val="left" w:pos="1440"/></w:tabs><w:spacing w:after="280"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Pangkat</w:t></w:r><w:r><w:tab/><w:t xml:space="preserve">:</w:t></w:r><w:r><w:tab/><w:t>.........................................</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="1152"/><w:tab w:val="left" w:pos="1440"/></w:tabs><w:spacing w:after="280"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>NRP/NIP</w:t></w:r><w:r><w:tab/><w:t>:</w:t></w:r><w:r><w:tab/><w:t>.........................................</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="1152"/><w:tab w:val="left" w:pos="1440"/></w:tabs><w:spacing w:after="280"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Jabatan</w:t></w:r><w:r><w:tab/><w:t>:</w:t></w:r><w:r><w:tab/><w:t>.........................................</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:before="400" w:after="40"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>......................................., Tgl. .............. {TEMPLATE_PARTIAL_DATE}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="40"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Titik Pembekalan (POLRES/SATKER POLDA)</w:t></w:r></w:p>'
        . '</w:tc>'
        // RIGHT COLUMN: Penanda Tangan (Tight Spacing)
        . '<w:tc><w:tcPr><w:tcW w:w="4844" w:type="dxa"/></w:tcPr>'
        . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>Mataram,</w:t></w:r><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve">  {TEMPLATE_FULL_DATE}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t xml:space="preserve">{TEMPLATE_SIG_SATKER}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>{TEMPLATE_SIG_JABATAN}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>{TEMPLATE_SIG_ATRIBUT}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>{TEMPLATE_SIG_WAKIL}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:before="600" w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/><w:u w:val="single"/></w:rPr><w:t>{TEMPLATE_SIG_NAME}</w:t></w:r></w:p>'
        . '<w:p><w:pPr><w:spacing w:after="0"/><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/><w:szCs w:val="20"/></w:rPr><w:t>{TEMPLATE_SIG_RANK_LINE}</w:t></w:r></w:p>'
        . '</w:tc>'
        . '</w:tr>'
        . '</w:tbl><w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/><w:cols w:space="720"/></w:sectPr></w:body></w:document>';
        return $xml;
    }

    private function createDocxFromXml(string $filePath, string $documentXml): void
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Gagal membuat file SPPM.');
        }

        // [Content_Types].xml
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/_rels/document.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '</Types>');

        // _rels/.rels
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>');

        // word/_rels/document.xml.rels
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '</Relationships>');

        // word/document.xml
        $zip->addFromString('word/document.xml', $documentXml);

        $zip->close();
    }

    private function formatDateIndonesian(string $date): string
    {
        try {
            $parsed = Carbon::parse($date);
            $months = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
            return $parsed->day . ' ' . $months[$parsed->month] . ' ' . $parsed->year;
        } catch (\Exception $e) {
            return $date;
        }
    }

    private function numberToWordsDigit(int $number): string
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

        $strNum = (string) $number;
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
