<?php

namespace Tests\Unit;

use App\Support\GolonganNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GolonganNormalizerTest extends TestCase
{
    #[DataProvider('golonganProvider')]
    public function test_it_normalizes_pns_golongan_to_numeric_major(string $input, string $expected): void
    {
        $this->assertSame($expected, GolonganNormalizer::major($input));
    }

    public static function golonganProvider(): array
    {
        return [
            ['1', '1'],
            ['I/a', '1'],
            ['GOLONGAN II', '2'],
            ['II.D', '2'],
            ['III/a', '3'],
            ['IIIA', '3'],
            ['gol 3', '3'],
            ['IV-e', '4'],
            ['4/a', '4'],
        ];
    }

    public function test_it_ignores_non_pns_rank_categories(): void
    {
        $this->assertNull(GolonganNormalizer::major('PAMEN'));
        $this->assertNull(GolonganNormalizer::major('PPPK'));
        $this->assertNull(GolonganNormalizer::major(''));
    }
}
