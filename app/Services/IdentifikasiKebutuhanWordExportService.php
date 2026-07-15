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

class IdentifikasiKebutuhanWordExportService
{
    private const PAGE_WIDTH = 14700;

    /**
     * @var array<int, int>
     */
    private const MAIN_COLUMNS = [520, 3650, 7850, 1760, 920];

    /**
     * Generate DOCX export for identifikasi kebutuhan.
     *
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data, bool $includeSatkers = false): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8.5);

        $phpWord->addTableStyle('kopTable', [
            'borderSize' => 0,
            'cellMargin' => 0,
            'alignment' => JcTable::START,
        ]);
        $phpWord->addTableStyle('mainExportTable', [
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 70,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);
        $phpWord->addTableStyle('barTable', [
            'borderSize' => 2,
            'borderColor' => '9CA3AF',
            'cellMargin' => 0,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);
        $phpWord->addTableStyle('signatureTable', [
            'borderSize' => 0,
            'cellMargin' => 0,
            'alignment' => JcTable::CENTER,
            'width' => 100 * 50,
            'unit' => TblWidth::PERCENT,
        ]);

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
        $this->addMainTable($section, $data, $includeSatkers);
        $this->addSignature($section, $data);

        $tempFile = tempnam(sys_get_temp_dir(), 'KAPOR_ID_');

        if ($tempFile === false) {
            throw new RuntimeException('Gagal menyiapkan file DOCX identifikasi kebutuhan.');
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return $tempFile;
    }

    private function addHeader(Section $section, int $fiscalYear): void
    {
        $kopTable = $section->addTable('kopTable');
        $kopTable->addRow();
        $kopCell = $kopTable->addCell(4300, [
            'borderBottomSize' => 12,
            'borderBottomColor' => '000000',
            'valign' => VerticalJc::CENTER,
        ]);

        foreach ([
            'KEPOLISIAN NEGARA REPUBLIK INDONESIA',
            'DAERAH NUSA TENGGARA BARAT',
            'BIRO LOGISTIK',
        ] as $line) {
            $kopCell->addText($line, ['bold' => true, 'size' => 10], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 0,
                'lineHeight' => 1.05,
            ]);
        }

        $section->addTextBreak(1);
        $section->addText(
            'HASIL IDENTIFIKASI KEBUTUHAN KAPOR POLRI DAN PNS POLDA NTB T.A. '.$fiscalYear,
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
        $run->addText('Total satker: ', ['size' => 8]);
        $run->addText(number_format((int) $data['totalSatkers']), ['bold' => true, 'size' => 8]);
        $run->addText('  |  Satker mengajukan: ', ['size' => 8]);
        $run->addText(number_format((int) $data['submittedSatkers']), ['bold' => true, 'size' => 8]);
        $run->addText('  |  Total item terpilih: ', ['size' => 8]);
        $run->addText(number_format((int) $data['totalItems']), ['bold' => true, 'size' => 8]);
        $run->addText('  |  Dicetak: '.$data['generatedAt']->translatedFormat('d F Y H:i'), ['size' => 8]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addMainTable(Section $section, array $data, bool $includeSatkers): void
    {
        $table = $section->addTable('mainExportTable');

        $table->addRow(430, ['tblHeader' => true, 'cantSplit' => true]);
        $this->addHeaderCell($table, self::MAIN_COLUMNS[0], 'NO');
        $this->addHeaderCell($table, self::MAIN_COLUMNS[1], 'ITEM', Jc::START);
        $this->addHeaderCell($table, self::MAIN_COLUMNS[2], 'GRAFIK PERSENTASE');
        $this->addHeaderCell($table, self::MAIN_COLUMNS[3], 'SATKER MEMILIH');
        $this->addHeaderCell($table, self::MAIN_COLUMNS[4], 'NILAI');

        $hasRows = false;
        foreach ($data['categoryGroups'] as $category) {
            $items = $category['items'];
            if ($items->isEmpty()) {
                continue;
            }

            $hasRows = true;
            $table->addRow(320, ['cantSplit' => true]);
            $categoryCell = $table->addCell(self::PAGE_WIDTH, [
                'gridSpan' => 5,
                'bgColor' => 'F3F4F6',
                'valign' => VerticalJc::CENTER,
            ]);
            $categoryCell->addText(strtoupper((string) $category['name']), ['bold' => true, 'size' => 8.5], [
                'spaceAfter' => 0,
            ]);

            foreach ($items as $index => $item) {
                $hasSatkers = $includeSatkers && ! empty($item['satkers']);

                $table->addRow($hasSatkers ? 540 : 500, ['cantSplit' => true]);
                $this->addBodyCell($table, self::MAIN_COLUMNS[0], (string) ($index + 1), Jc::CENTER, ['bold' => true]);
                $this->addBodyCell($table, self::MAIN_COLUMNS[1], (string) $item['name'], Jc::START, ['bold' => true]);

                $barCell = $table->addCell(self::MAIN_COLUMNS[2], ['valign' => VerticalJc::CENTER]);
                $this->addPercentageBar($barCell, (int) $item['percentage']);

                $this->addBodyCell(
                    $table,
                    self::MAIN_COLUMNS[3],
                    number_format((int) $item['satker_count']).' / '.number_format((int) $item['eligible_count']),
                    Jc::CENTER,
                    ['bold' => true]
                );
                $this->addBodyCell($table, self::MAIN_COLUMNS[4], number_format((int) $item['percentage']).'%', Jc::CENTER, ['bold' => true]);

                if ($hasSatkers) {
                    $table->addRow(null, ['cantSplit' => true]);
                    $table->addCell(self::MAIN_COLUMNS[0], ['bgColor' => 'FFFFFF'])->addText('', [], ['spaceAfter' => 0]);
                    $detailCell = $table->addCell(self::MAIN_COLUMNS[1] + self::MAIN_COLUMNS[2], [
                        'gridSpan' => 2,
                        'bgColor' => 'FFFFFF',
                    ]);
                    $detailRun = $detailCell->addTextRun(['alignment' => Jc::BOTH, 'spaceAfter' => 60]);
                    $detailRun->addText('Daftar Satker Memilih: ', ['bold' => true, 'size' => 7.5]);
                    $detailRun->addText(implode(', ', $item['satkers']), ['size' => 7.5]);
                    $table->addCell(self::MAIN_COLUMNS[3], ['bgColor' => 'FFFFFF'])->addText('', [], ['spaceAfter' => 0]);
                    $table->addCell(self::MAIN_COLUMNS[4], ['bgColor' => 'FFFFFF'])->addText('', [], ['spaceAfter' => 0]);
                }
            }
        }

        if (! $hasRows) {
            $table->addRow(420);
            $cell = $table->addCell(self::PAGE_WIDTH, ['gridSpan' => 5, 'valign' => VerticalJc::CENTER]);
            $cell->addText('Belum ada data identifikasi kebutuhan.', ['size' => 8.5], [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 0,
            ]);
        }
    }

    private function addPercentageBar(Cell $cell, int $percentage): void
    {
        $score = min(100, max(0, $percentage));
        $filledSegments = (int) round($score / 2.5);
        $bar = $cell->addTable('barTable');

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
        $cell->addText($text, array_merge(['size' => 8.5], $font), [
            'alignment' => $alignment,
            'spaceAfter' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function addSignature(Section $section, array $data): void
    {
        $settings = $data['signatorySettings'] ?? [];
        $location = (string) ($settings['location'] ?? 'Mataram');
        $organizationName = strtoupper((string) ($settings['organization_name'] ?? 'KEPALA BIRO LOGISTIK POLDA NTB'));
        $signatoryTitle = strtoupper((string) ($settings['signatory_title'] ?? 'PEJABAT PEMBUAT KOMITMEN'));
        $signatoryName = strtoupper((string) ($settings['signatory_name'] ?? '.............................'));
        $rank = strtoupper((string) ($settings['signatory_rank'] ?? ''));
        $nrp = (string) ($settings['signatory_nrp'] ?? '');
        $rankLine = trim($rank.($nrp !== '' ? ' NRP/NIP '.$nrp : ''));

        $section->addTextBreak(1);
        $signature = $section->addTable('signatureTable');
        $signature->addRow();
        $signature->addCell((int) (self::PAGE_WIDTH * 0.58))->addText('', [], ['spaceAfter' => 0]);
        $cell = $signature->addCell((int) (self::PAGE_WIDTH * 0.42));

        $paragraph = ['alignment' => Jc::CENTER, 'spaceAfter' => 0, 'lineHeight' => 1.1];
        $cell->addText($location.', '.$data['generatedAt']->translatedFormat('d F Y'), ['size' => 9], $paragraph);
        if ($organizationName !== '') {
            $cell->addText('a.n. '.$organizationName, ['size' => 9], $paragraph);
        }
        $cell->addText($signatoryTitle, ['bold' => true, 'size' => 9], $paragraph);
        $cell->addTextBreak(3);
        $cell->addText($signatoryName, ['bold' => true, 'underline' => 'single', 'size' => 9], $paragraph);
        $cell->addText($rankLine, ['size' => 9], $paragraph);
    }
}
