<?php

namespace App\Support;

class GolonganNormalizer
{
    /**
     * Ubah golongan PNS seperti III/a, IIIA, GOLONGAN III, atau 3/a menjadi 3.
     */
    public static function major(mixed $value): ?string
    {
        $normalized = strtoupper(trim((string) $value));

        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/^GOL(?:ONGAN)?\.?\s*/', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        if (preg_match('/^([1-4])(?:[\.\/-]?[A-E])?$/', $normalized, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/^(IV|III|II|I)(?:[\.\/-]?[A-E])?$/', $normalized, $matches) !== 1) {
            return null;
        }

        return match ($matches[1]) {
            'I' => '1',
            'II' => '2',
            'III' => '3',
            'IV' => '4',
        };
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public static function majors(array $values): array
    {
        return array_values(array_unique(array_filter(
            array_map(self::major(...), $values),
            static fn (?string $value): bool => $value !== null,
        )));
    }

    /**
     * Nilai historis yang harus tetap cocok sebelum seluruh data lama dinormalisasi.
     *
     * @return array<int, string>
     */
    public static function databaseVariants(string $major): array
    {
        $roman = match ($major) {
            '1' => 'I',
            '2' => 'II',
            '3' => 'III',
            '4' => 'IV',
            default => null,
        };

        if ($roman === null) {
            return [];
        }

        $variants = [$major, $roman, "GOL {$major}", "GOLONGAN {$major}", "GOL {$roman}", "GOLONGAN {$roman}"];

        foreach (range('A', 'E') as $suffix) {
            foreach ([$major, $roman] as $prefix) {
                $variants[] = "{$prefix}{$suffix}";
                $variants[] = "{$prefix}/{$suffix}";
                $variants[] = "{$prefix}.{$suffix}";
                $variants[] = "{$prefix}-{$suffix}";
            }
        }

        return array_values(array_unique(array_map('strtolower', $variants)));
    }
}
