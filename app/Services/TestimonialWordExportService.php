<?php

namespace App\Services;

use PhpOffice\PhpWord\Element\Cell;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\SimpleType\VerticalJc;
use RuntimeException;

class TestimonialWordExportService
{
    private const PAGE_WIDTH = 14700;

    /**
     * @var array<int, int>
     */
    private const ITEM_COLUMNS = [590, 4998, 7938, 1174];

    /**
     * @var array<int, int>
     */
    private const SUMMARY_COLUMNS = [5588, 7938, 1174];

    /**
     * @var array<int, int>
     */
    private const REVIEW_COLUMNS = [1500, 13200];

    /**
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8.5);

        $this->registerStyles($phpWord);

        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'pageSizeW' => 16838,
            'pageSizeH' => 11906,
            'marginTop' => 567,
            'marginBottom' => 567,
            'marginLeft' => 567,
            'marginRight' => 567,
        ]);

        $this->addHeader($section, (int) $data['fiscalYear']);
        $this->addMeta($section, $data);
        $this->addItemPercentageTable($section, $data);
        $this->addCategorySummaryTable($section, $data);
        $this->addReviewTable($section, $data);

        $tempFile = tempnam(sys_get_temp_dir(), 'KAPOR_REVIEW_');

        if ($tempFile === false) {
            throw new RuntimeException('Gagal menyiapkan file DOCX review kapor.');
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return $tempFile;
    }

    private function registerStyles(PhpWord $phpWord): void
    {
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 70,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ];

        $phpWord->addTableStyle('reviewExportTable', $tableStyle);
        $phpWord->addTableStyle('reviewBarTable', [
            'borderSize' => 2,
            'borderColor' => '9CA3AF',
            'cellMargin' => 0,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);
    }

    private function addHeader(Section $section, int $fiscalYear): void
    {
        $kopParagraph = [
            'alignment' => Jc::CENTER,
            'indentation' => ['right' => 8600],
            'spaceAfter' => 0,
            'lineHeight' => 1.05,
        ];

        $section->addText('KEPOLISIAN NEGARA REPUBLIK INDONESIA', ['bold' => true, 'size' => 9.5], $kopParagraph);
        $section->addText('DAERAH NUSA TENGGARA BARAT', ['bold' => true, 'size' => 9.5], $kopParagraph);
        $section->addText('BIRO LOGISTIK', ['bold' => true, 'size' => 9.5], $kopParagraph);
        $section->addText('', ['size' => 1], [
            'indentation' => ['right' => 8600],
            'borderBottomSize' => 6,
            'borderBottomColor' => '000000',
            'spaceAfter' => 180,
            'lineHeight' => 0.1,
        ]);

        $section->addTextBreak(1);
        $section->addText(
            'HASIL REVIEW PENGADAAN KAPOR POLRI DAN PNS POLDA NTB T.A. '.$fiscalYear,
            ['bold' => true, 'underline' => 'single', 'size' => 11],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 180]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addMeta(Section $section, array $data): void
    {
        $run = $section->addTextRun(['spaceAfter' => 120]);
        $run->addText('Total data testimoni: ', ['size' => 8]);
        $run->addText(number_format((int) $data['totalReviews']), ['bold' => true, 'size' => 8]);
        $run->addText('  |  Komentar per bintang: ', ['size' => 8]);
        $run->addText(number_format((int) ($data['commentsPerRating'] ?? 2)), ['bold' => true, 'size' => 8]);
        $run->addText($this->safeText('  |  Dicetak: '.$data['generatedAt']->translatedFormat('d F Y H:i')), ['size' => 8]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addItemPercentageTable(Section $section, array $data): void
    {
        $this->addSectionTitle($section, 'Persentase Per Item');

        $table = $section->addTable('reviewExportTable');
        $table->addRow(430, ['tblHeader' => true, 'cantSplit' => true]);
        $this->addHeaderCell($table, self::ITEM_COLUMNS[0], 'NO');
        $this->addHeaderCell($table, self::ITEM_COLUMNS[1], 'ITEM', Jc::START);
        $this->addHeaderCell($table, self::ITEM_COLUMNS[2], 'GRAFIK PERSENTASE');
        $this->addHeaderCell($table, self::ITEM_COLUMNS[3], 'NILAI');

        $hasRows = false;
        foreach ($data['categoryGroups'] as $category) {
            $items = $category['items'];
            if ($items->isEmpty()) {
                continue;
            }

            $hasRows = true;
            $table->addRow(320, ['cantSplit' => true]);
            $categoryCell = $table->addCell(self::PAGE_WIDTH, [
                'gridSpan' => 4,
                'bgColor' => 'F3F4F6',
                'valign' => VerticalJc::CENTER,
            ]);
            $categoryCell->addText($this->safeText(strtoupper((string) $category['name'])), ['bold' => true, 'size' => 8.5], [
                'spaceAfter' => 0,
            ]);

            foreach ($items as $index => $item) {
                $table->addRow(500, ['cantSplit' => true]);
                $this->addBodyCell($table, self::ITEM_COLUMNS[0], (string) ($index + 1), Jc::CENTER, ['bold' => true]);
                $this->addBodyCell($table, self::ITEM_COLUMNS[1], (string) $item['name'], Jc::START, ['bold' => true]);

                $barCell = $table->addCell(self::ITEM_COLUMNS[2], ['valign' => VerticalJc::CENTER]);
                $this->addPercentageBar($barCell, $item['overall_score']);

                $score = $item['overall_score'] !== null ? number_format((float) $item['overall_score'], 1).'%' : '-';
                $this->addBodyCell($table, self::ITEM_COLUMNS[3], $score, Jc::CENTER, ['bold' => true]);
            }
        }

        if (! $hasRows) {
            $table->addRow(420);
            $cell = $table->addCell(self::PAGE_WIDTH, ['gridSpan' => 4, 'valign' => VerticalJc::CENTER]);
            $cell->addText('Belum ada data testimoni untuk tahun anggaran ini.', ['size' => 8.5], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 0,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addCategorySummaryTable(Section $section, array $data): void
    {
        $section->addTextBreak(1);
        $this->addSectionTitle($section, 'Ringkasan Persentase Per Kategori');

        $table = $section->addTable('reviewExportTable');
        $table->addRow(430, ['tblHeader' => true, 'cantSplit' => true]);
        $this->addHeaderCell($table, self::SUMMARY_COLUMNS[0], 'KATEGORI', Jc::START);
        $this->addHeaderCell($table, self::SUMMARY_COLUMNS[1], 'GRAFIK PERSENTASE');
        $this->addHeaderCell($table, self::SUMMARY_COLUMNS[2], 'NILAI');

        $hasRows = false;
        foreach ($data['categorySummaries'] as $category) {
            $hasRows = true;
            $table->addRow(500, ['cantSplit' => true]);
            $this->addBodyCell($table, self::SUMMARY_COLUMNS[0], (string) $category['name'], Jc::START);

            $barCell = $table->addCell(self::SUMMARY_COLUMNS[1], ['valign' => VerticalJc::CENTER]);
            $this->addPercentageBar($barCell, $category['overall_score']);

            $score = $category['overall_score'] !== null ? number_format((float) $category['overall_score'], 1).'%' : '-';
            $this->addBodyCell($table, self::SUMMARY_COLUMNS[2], $score, Jc::CENTER, ['bold' => true]);
        }

        if (! $hasRows) {
            $table->addRow(420);
            $cell = $table->addCell(self::PAGE_WIDTH, ['gridSpan' => 3, 'valign' => VerticalJc::CENTER]);
            $cell->addText('Belum ada ringkasan kategori.', ['size' => 8.5], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 0,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addReviewTable(Section $section, array $data): void
    {
        $section->addTextBreak(1);
        $this->addSectionTitle($section, 'Review Personel');

        $table = $section->addTable('reviewExportTable');
        $table->addRow(430, ['tblHeader' => true, 'cantSplit' => true]);
        $this->addHeaderCell($table, self::REVIEW_COLUMNS[0], 'BINTANG');
        $this->addHeaderCell($table, self::REVIEW_COLUMNS[1], 'KOMENTAR', Jc::START);

        foreach ($data['commentsByRating'] as $rating => $comments) {
            $rowHeight = $comments->isEmpty() ? 420 : max(520, $comments->count() * 420);
            $table->addRow($rowHeight, ['cantSplit' => true]);
            $this->addBodyCell($table, self::REVIEW_COLUMNS[0], (string) $rating, Jc::CENTER, ['size' => 9]);

            $cell = $table->addCell(self::REVIEW_COLUMNS[1], ['valign' => VerticalJc::TOP]);
            if ($comments->isEmpty()) {
                $cell->addText('Tidak ada komentar.', ['size' => 8.5], ['spaceAfter' => 0]);

                continue;
            }

            foreach ($comments as $index => $comment) {
                $source = strtoupper($comment['personnel'].' - '.$comment['satker'].' - '.$comment['item']);
                $cell->addText($this->safeText($source), [
                    'bold' => true,
                    'size' => 7.8,
                    'color' => '1F2937',
                ], [
                    'spaceAfter' => 20,
                ]);
                $cell->addText($this->safeText('"'.$comment['comment'].'"'), [
                    'italic' => true,
                    'size' => 8.5,
                    'color' => '111827',
                ], [
                    'spaceAfter' => $index === $comments->count() - 1 ? 0 : 140,
                ]);
            }
        }
    }

    private function addSectionTitle(Section $section, string $title): void
    {
        $section->addText(strtoupper($title), ['bold' => true, 'size' => 9], [
            'spaceAfter' => 80,
            'keepNext' => true,
        ]);
    }

    private function addPercentageBar(Cell $cell, mixed $score): void
    {
        if ($score === null) {
            $cell->addText('Belum ada nilai', ['size' => 8.5, 'color' => '555555'], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 0,
            ]);

            return;
        }

        $score = min(100, max(0, (float) $score));
        $filledSegments = (int) round($score / 2.5);
        $bar = $cell->addTable('reviewBarTable');

        $bar->addRow(190, ['exactHeight' => true]);
        for ($segment = 1; $segment <= 40; $segment++) {
            $bar->addCell(180, [
                'bgColor' => $segment <= $filledSegments ? '2563EB' : 'E5E7EB',
                'valign' => VerticalJc::CENTER,
            ])->addText('', [], ['spaceAfter' => 0]);
        }

        $bar->addRow(180, ['exactHeight' => true]);
        foreach ([
            ['0', 5, Jc::START],
            ['25', 5, Jc::END],
            ['50', 10, Jc::END],
            ['75', 10, Jc::END],
            ['100', 10, Jc::END],
        ] as [$label, $span, $align]) {
            $labelCell = $bar->addCell(180 * (int) $span, [
                'gridSpan' => (int) $span,
                'borderSize' => 0,
            ]);
            $labelCell->addText((string) $label, ['size' => 6, 'color' => '555555'], [
                'alignment' => $align,
                'spaceAfter' => 0,
            ]);
        }
    }

    private function addHeaderCell($table, int $width, string $text, string $alignment = Jc::CENTER): void
    {
        $cell = $table->addCell($width, [
            'bgColor' => 'E5E7EB',
            'valign' => VerticalJc::CENTER,
        ]);
        $cell->addText($text, ['bold' => true, 'size' => 8], [
            'alignment' => $alignment,
            'spaceAfter' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $font
     */
    private function addBodyCell($table, int $width, string $text, string $alignment = Jc::START, array $font = []): void
    {
        $cell = $table->addCell($width, ['valign' => VerticalJc::CENTER]);
        $cell->addText($this->safeText($text), array_merge(['size' => 8.5], $font), [
            'alignment' => $alignment,
            'spaceAfter' => 0,
        ]);
    }

    private function safeText(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
