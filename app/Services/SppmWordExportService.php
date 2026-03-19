<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\InvoiceSetting;
use App\Models\Satker;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SppmWordExportService
{
    private const TEMPLATE_PATH = 'resources/templates/sppm/format_sppm_polda.docx';

    private const BOTTOM_MARGIN_TWIP = '1800';

    private const FOOTER_MARGIN_TWIP = '900';

    private BudgetCalculationService $calcService;

    public function __construct(BudgetCalculationService $calcService)
    {
        $this->calcService = $calcService;
    }

    /**
     * Generate one SPPM Word document for the whole package.
     */
    public function generateForPackage(BudgetPackage $package, array $suratData): ?string
    {
        $data = $this->calcService->calculatePackage($package);
        $settings = InvoiceSetting::getSettings();
        $templatePath = base_path(self::TEMPLATE_PATH);

        if (! file_exists($templatePath)) {
            throw new RuntimeException('Template SPPM tidak ditemukan: '.$templatePath);
        }

        $satkerData = [];

        foreach ($data['items'] as $item) {
            foreach ($item['recipients'] as $recipient) {
                if ($recipient['matched_count'] <= 0) {
                    continue;
                }

                $satkerId = $recipient['satker_id'];

                if (! isset($satkerData[$satkerId])) {
                    $satkerData[$satkerId] = [
                        'satker' => Satker::find($satkerId),
                        'items' => [],
                    ];
                }

                $satkerData[$satkerId]['items'][] = [
                    'item_name' => $item['item_name'],
                    'unit' => $item['unit'],
                    'price' => $item['price'],
                    'qty' => $recipient['matched_count'],
                    'total' => $recipient['subtotal'],
                ];
            }
        }

        $sections = [];

        foreach ($satkerData as $sd) {
            if (! $sd['satker']) {
                continue;
            }

            $sections[] = $this->populateDocumentXml(
                $this->readTemplateDocumentXml($templatePath),
                $sd['satker'],
                $sd['items'],
                $suratData,
                $settings,
            );
        }

        if ($sections === []) {
            return null;
        }

        $combinedDocumentXml = $this->mergeSectionDocuments($sections);
        $tempFile = tempnam(sys_get_temp_dir(), 'SPPM_');

        if ($tempFile === false || ! copy($templatePath, $tempFile)) {
            throw new RuntimeException('Gagal menyiapkan file template SPPM.');
        }

        $zip = new ZipArchive;

        if ($zip->open($tempFile) !== true) {
            throw new RuntimeException('Gagal membuka template SPPM.');
        }

        $zip->addFromString('word/document.xml', $combinedDocumentXml);
        $zip->close();

        return $tempFile;
    }

    private function readTemplateDocumentXml(string $templatePath): string
    {
        $zip = new ZipArchive;

        if ($zip->open($templatePath) !== true) {
            throw new RuntimeException('Gagal membuka template SPPM.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($documentXml === false) {
            throw new RuntimeException('Isi document.xml pada template SPPM tidak ditemukan.');
        }

        return $documentXml;
    }

    private function mergeSectionDocuments(array $sections): string
    {
        $baseDocument = new DOMDocument('1.0', 'UTF-8');
        $baseDocument->preserveWhiteSpace = true;
        $baseDocument->formatOutput = false;
        $baseDocument->loadXML($sections[0]);

        $baseXPath = new DOMXPath($baseDocument);
        $baseXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $body = $baseXPath->query('//w:body')->item(0);
        $sectionProperties = $baseXPath->query('./w:sectPr', $body)->item(0);

        if (! $body instanceof DOMElement || ! $sectionProperties instanceof DOMElement) {
            throw new RuntimeException('Struktur body pada template SPPM tidak valid.');
        }

        for ($index = 1; $index < count($sections); $index++) {
            $body->insertBefore($this->createPageBreakParagraph($baseDocument), $sectionProperties);

            $sectionDocument = new DOMDocument('1.0', 'UTF-8');
            $sectionDocument->preserveWhiteSpace = true;
            $sectionDocument->formatOutput = false;
            $sectionDocument->loadXML($sections[$index]);

            $sectionXPath = new DOMXPath($sectionDocument);
            $sectionXPath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

            $sectionBody = $sectionXPath->query('//w:body')->item(0);

            if (! $sectionBody instanceof DOMElement) {
                continue;
            }

            $this->trimEmptyBoundaryParagraphs($sectionBody, $sectionXPath);

            foreach ($sectionBody->childNodes as $childNode) {
                if (! $childNode instanceof DOMElement || $childNode->tagName === 'w:sectPr') {
                    continue;
                }

                $body->insertBefore($baseDocument->importNode($childNode, true), $sectionProperties);
            }
        }

        $this->applyPageMargins($baseXPath);

        return $baseDocument->saveXML();
    }

    private function createPageBreakParagraph(DOMDocument $document): DOMElement
    {
        $paragraph = $document->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:p');
        $run = $document->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:r');
        $break = $document->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:br');
        $break->setAttribute('w:type', 'page');

        $run->appendChild($break);
        $paragraph->appendChild($run);

        return $paragraph;
    }

    private function trimEmptyBoundaryParagraphs(DOMElement $body, DOMXPath $xpath): void
    {
        while (true) {
            $firstContent = $this->findBoundaryContentNode($body, true);

            if (! $firstContent instanceof DOMElement || $firstContent->tagName !== 'w:p' || ! $this->isParagraphEmpty($xpath, $firstContent)) {
                break;
            }

            $body->removeChild($firstContent);
        }

        while (true) {
            $lastContent = $this->findBoundaryContentNode($body, false);

            if (! $lastContent instanceof DOMElement || $lastContent->tagName !== 'w:p' || ! $this->isParagraphEmpty($xpath, $lastContent)) {
                break;
            }

            $body->removeChild($lastContent);
        }
    }

    private function findBoundaryContentNode(DOMElement $body, bool $fromStart): ?DOMNode
    {
        $node = $fromStart ? $body->firstChild : $body->lastChild;

        while ($node) {
            if ($node instanceof DOMElement && $node->tagName !== 'w:sectPr') {
                return $node;
            }

            $node = $fromStart ? $node->nextSibling : $node->previousSibling;
        }

        return null;
    }

    private function isParagraphEmpty(DOMXPath $xpath, DOMElement $paragraph): bool
    {
        if ($xpath->query('.//w:br', $paragraph)->length > 0) {
            return false;
        }

        if ($xpath->query('.//w:drawing', $paragraph)->length > 0) {
            return false;
        }

        return trim(str_replace("\xc2\xa0", '', $this->getNodeText($xpath, $paragraph))) === '';
    }

    private function applyPageMargins(DOMXPath $xpath): void
    {
        foreach ($xpath->query('//w:sectPr/w:pgMar') as $pageMargin) {
            if (! $pageMargin instanceof DOMElement) {
                continue;
            }

            $pageMargin->setAttribute('w:bottom', self::BOTTOM_MARGIN_TWIP);
            $pageMargin->setAttribute('w:footer', self::FOOTER_MARGIN_TWIP);
        }
    }

    private function populateDocumentXml(
        string $documentXml,
        Satker $satker,
        array $itemsData,
        array $suratData,
        InvoiceSetting $settings,
    ): string {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->preserveWhiteSpace = true;
        $document->formatOutput = false;
        $document->loadXML($documentXml);

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');
        $xpath->registerNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
        $xpath->registerNamespace('wps', 'http://schemas.microsoft.com/office/word/2010/wordprocessingShape');
        $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');

        $dateParts = $this->extractDateParts($suratData['date'] ?? '');
        $signatureDate = $this->buildSignatureDate($settings->location, $dateParts);
        $organizationName = strtoupper($settings->organization_name ?: 'KEPALA BIRO LOGISTIK POLDA NTB');
        $signatoryTitle = $settings->signatory_title ?: '................................';
        $signatoryName = strtoupper($settings->signatory_name ?: '................................');
        $signatoryRankLine = trim(trim($settings->signatory_rank).' NRP '.trim($settings->signatory_nrp));

        $this->replaceParagraphRuns(
            $xpath,
            'Nomor:',
            ['Nomor: '.($suratData['sppm_number'] ?? '')],
        );
        $this->replaceParagraphRuns(
            $xpath,
            'Diperintahkan untuk mengeluarkan kepada',
            [
                'Diperintahkan untuk mengeluarkan kepada',
                ': ',
                $this->formatRecipientName($satker),
            ],
        );
        $this->replaceParagraphRuns(
            $xpath,
            'Berdasarkan',
            [
                'Berdasarkan',
                ': ',
                $suratData['sprin_number'] ?? '',
            ],
        );
        $this->replaceParagraphRuns(
            $xpath,
            'TANGGAL',
            [
                ' ',
                'TANGGAL '.$dateParts['display_upper'],
            ],
        );

        $this->replaceAllExactText($xpath, 'LOKASI,         BULAN    TAHUN', $signatureDate);
        $this->replaceAllExactText($xpath, 'a.n. NAMA ORGANISASI', 'a.n. '.$organizationName);
        $this->replaceAllExactText($xpath, 'JABATAN PENANDATANGAN', $signatoryTitle);
        $this->replaceAllExactText($xpath, 'NAMA PENANDATANGAN', $signatoryName);
        $this->replaceAllMathText($xpath, 'PANGKAT / NRP PENANDATANGAN ', $signatoryRankLine === 'NRP' ? '' : $signatoryRankLine.' ');

        $this->replaceParagraphRuns(
            $xpath,
            '.............................., Tgl.',
            [
                '.............................., ',
                'Tgl',
                '. .......... ',
                $dateParts['month_title'],
                ' '.$dateParts['year'],
            ],
        );

        $this->replaceItemsTable($xpath, $itemsData);
        $this->neutralizeFloatingTextboxes($document, $xpath);
        $this->applyPageMargins($xpath);

        return $document->saveXML();
    }

    private function replaceItemsTable(DOMXPath $xpath, array $itemsData): void
    {
        $table = $xpath->query('//w:tbl[1]')->item(0);

        if (! $table instanceof DOMElement) {
            throw new RuntimeException('Tabel materiil pada template SPPM tidak ditemukan.');
        }

        $rows = $xpath->query('./w:tr', $table);

        if ($rows->length < 3) {
            throw new RuntimeException('Baris tabel materiil pada template SPPM tidak valid.');
        }

        $templateRow = $rows->item(2)?->cloneNode(true);

        if (! $templateRow instanceof DOMElement) {
            throw new RuntimeException('Baris contoh materiil pada template SPPM tidak ditemukan.');
        }

        for ($rowIndex = $rows->length - 1; $rowIndex >= 2; $rowIndex--) {
            $row = $rows->item($rowIndex);

            if ($row instanceof DOMNode) {
                $table->removeChild($row);
            }
        }

        foreach (array_values($itemsData) as $index => $item) {
            $row = $templateRow->cloneNode(true);

            $cells = $xpath->query('./w:tc', $row);
            $values = [
                (string) ($index + 1),
                strtoupper((string) $item['item_name']),
                strtoupper((string) $item['unit']),
                (string) (int) $item['qty'],
                $this->numberToWordsDigit((int) $item['qty']),
                number_format((float) $item['price'], 0, ',', '.'),
                number_format((float) $item['total'], 0, ',', '.'),
                '',
            ];

            foreach ($values as $cellIndex => $value) {
                $cell = $cells->item($cellIndex);

                if ($cell instanceof DOMElement) {
                    $this->replaceNodeTexts($xpath, $cell, [$value]);
                }
            }

            $table->appendChild($row);
        }
    }

    private function replaceParagraphRuns(DOMXPath $xpath, string $contains, array $values): void
    {
        foreach ($xpath->query('//w:body/w:p') as $paragraph) {
            if (! $paragraph instanceof DOMElement) {
                continue;
            }

            $text = $this->getNodeText($xpath, $paragraph);

            if (! str_contains($text, $contains)) {
                continue;
            }

            $this->replaceNodeTexts($xpath, $paragraph, $values);

            return;
        }
    }

    private function replaceAllExactText(DOMXPath $xpath, string $search, string $replacement): void
    {
        foreach ($xpath->query('//w:t') as $textNode) {
            if ($textNode->nodeValue !== $search) {
                continue;
            }

            $textNode->nodeValue = $replacement;
            $this->applyXmlSpace($textNode, $replacement);
        }
    }

    private function replaceAllMathText(DOMXPath $xpath, string $search, string $replacement): void
    {
        foreach ($xpath->query('//m:t') as $textNode) {
            if ($textNode->nodeValue !== $search) {
                continue;
            }

            $textNode->nodeValue = $replacement;
            $this->applyXmlSpace($textNode, $replacement);
        }
    }

    private function replaceNodeTexts(DOMXPath $xpath, DOMElement $context, array $values): void
    {
        $textNodes = $xpath->query('.//w:t', $context);
        $values = array_values($values);
        $limit = max($textNodes->length, count($values));

        for ($index = 0; $index < $limit; $index++) {
            $node = $textNodes->item($index);
            $value = $values[$index] ?? '';

            if ($node instanceof DOMElement) {
                $node->nodeValue = $value;
                $this->applyXmlSpace($node, $value);
            }
        }
    }

    private function neutralizeFloatingTextboxes(DOMDocument $document, DOMXPath $xpath): void
    {
        foreach ($xpath->query('//wp:anchor[descendant::wps:txbx]') as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $anchor->setAttribute('behindDoc', '1');
            $anchor->setAttribute('distT', '0');
            $anchor->setAttribute('distB', '0');
            $anchor->setAttribute('distL', '0');
            $anchor->setAttribute('distR', '0');

            foreach ($xpath->query('./wp:wrapSquare | ./wp:wrapTight | ./wp:wrapThrough | ./wp:wrapTopAndBottom | ./wp:wrapNone', $anchor) as $wrapNode) {
                $anchor->removeChild($wrapNode);
            }

            $wrapNone = $document->createElementNS(
                'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
                'wp:wrapNone'
            );

            $docPr = $xpath->query('./wp:docPr', $anchor)->item(0);

            if ($docPr instanceof DOMNode) {
                $anchor->insertBefore($wrapNone, $docPr);
            } else {
                $anchor->appendChild($wrapNone);
            }
        }

        foreach ($xpath->query('//v:shape[v:textbox]') as $shape) {
            if (! $shape instanceof DOMElement) {
                continue;
            }

            $style = $shape->getAttribute('style');
            if ($style === '') {
                continue;
            }

            $updatedStyle = preg_replace('/mso-wrap-style:[^;]+;?/i', 'mso-wrap-style:none;', $style) ?? $style;

            if ($updatedStyle !== $style) {
                $shape->setAttribute('style', $updatedStyle);
            }
        }
    }

    private function applyXmlSpace(DOMElement $node, string $value): void
    {
        if ($value !== trim($value)) {
            $node->setAttribute('xml:space', 'preserve');

            return;
        }

        if ($node->hasAttribute('xml:space')) {
            $node->removeAttribute('xml:space');
        }
    }

    private function getNodeText(DOMXPath $xpath, DOMElement $node): string
    {
        $parts = [];

        foreach ($xpath->query('.//w:t', $node) as $textNode) {
            $parts[] = $textNode->nodeValue ?? '';
        }

        return implode('', $parts);
    }

    private function formatRecipientName(Satker $satker): string
    {
        $name = strtoupper(trim($satker->name));

        if (str_contains($name, 'POLDA NTB') || str_starts_with($name, 'POLRES')) {
            return $name;
        }

        return $name.' POLDA NTB';
    }

    private function buildSignatureDate(?string $location, array $dateParts): string
    {
        return trim(($location ?: 'Mataram').',         '.$dateParts['month_title'].'    '.$dateParts['year']);
    }

    private function extractDateParts(string $date): array
    {
        $normalized = strtoupper(trim(preg_replace('/\s+/', ' ', $date))) ?: '........ 2025';
        $monthMap = [
            'JANUARI' => 'Januari',
            'JANUARY' => 'Januari',
            'FEBRUARI' => 'Februari',
            'FEBRUARY' => 'Februari',
            'MARET' => 'Maret',
            'MARCH' => 'Maret',
            'APRIL' => 'April',
            'MEI' => 'Mei',
            'MAY' => 'Mei',
            'JUNI' => 'Juni',
            'JUNE' => 'Juni',
            'JULI' => 'Juli',
            'JULY' => 'Juli',
            'AGUSTUS' => 'Agustus',
            'AUGUST' => 'Agustus',
            'SEPTEMBER' => 'September',
            'OKTOBER' => 'Oktober',
            'OCTOBER' => 'Oktober',
            'NOVEMBER' => 'November',
            'DESEMBER' => 'Desember',
            'DECEMBER' => 'Desember',
        ];

        $monthTitle = 'Juli';
        $year = '2025';

        if (preg_match('/\b(19|20)\d{2}\b/', $normalized, $yearMatch) === 1) {
            $year = $yearMatch[0];
        }

        foreach ($monthMap as $search => $title) {
            if (! str_contains($normalized, $search)) {
                continue;
            }

            $monthTitle = $title;
            break;
        }

        $monthUpper = strtoupper($monthTitle);
        $day = trim(preg_replace('/\D/', '', preg_replace('/\b(19|20)\d{2}\b/', '', $normalized)));

        if ($day === '') {
            $day = '........';
        }

        return [
            'display_upper' => trim($day.' '.$monthUpper.' '.$year),
            'month_title' => $monthTitle,
            'year' => $year,
        ];
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
