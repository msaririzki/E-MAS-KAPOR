<?php

namespace App\Services;

use App\Models\Satker;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SdmSatkerResolver
{
    /**
     * @return array{
     *   satker_id:int|null,
     *   satker_name:string|null,
     *   matched_alias:string|null,
     *   recipient_scope:string|null,
     *   normalized_jabatan:string
     * }
     */
    public function resolve(?string $jabatan): array
    {
        $normalizedJabatan = $this->normalize((string) $jabatan);
        $condensedJabatan = $this->condense($normalizedJabatan);

        if ($normalizedJabatan === '') {
            return [
                'satker_id' => null,
                'satker_name' => null,
                'matched_alias' => null,
                'recipient_scope' => null,
                'normalized_jabatan' => $normalizedJabatan,
            ];
        }

        $bestMatch = null;

        foreach ($this->satkerAliases() as $candidate) {
            if (! str_contains($condensedJabatan, $candidate['alias_condensed'])) {
                continue;
            }

            if (
                $bestMatch === null
                || $candidate['score'] > $bestMatch['score']
                || ($candidate['score'] === $bestMatch['score'] && $candidate['alias_length'] > $bestMatch['alias_length'])
            ) {
                $bestMatch = $candidate;
            }
        }

        if ($bestMatch === null) {
            return [
                'satker_id' => null,
                'satker_name' => null,
                'matched_alias' => null,
                'recipient_scope' => null,
                'normalized_jabatan' => $normalizedJabatan,
            ];
        }

        return [
            'satker_id' => $bestMatch['satker_id'],
            'satker_name' => $bestMatch['satker_name'],
            'matched_alias' => $bestMatch['alias_original'],
            'recipient_scope' => $bestMatch['recipient_scope'],
            'normalized_jabatan' => $normalizedJabatan,
        ];
    }

    /**
     * @return Collection<int, array{
     *   satker_id:int,
     *   satker_name:string,
     *   recipient_scope:string,
     *   alias_original:string,
     *   alias_condensed:string,
     *   alias_length:int,
     *   score:int
     * }>
     */
    private function satkerAliases(): Collection
    {
        $items = [];

        foreach (Satker::query()->orderBy('sort_order')->orderBy('name')->get() as $satker) {
            foreach ($this->buildAliasesForSatker($satker) as $alias) {
                $normalizedAlias = $this->normalize($alias);
                $condensedAlias = $this->condense($normalizedAlias);

                if ($condensedAlias === '') {
                    continue;
                }

                $items[] = [
                    'satker_id' => $satker->id,
                    'satker_name' => $satker->name,
                    'recipient_scope' => $satker->recipientScope(),
                    'alias_original' => $alias,
                    'alias_condensed' => $condensedAlias,
                    'alias_length' => strlen($condensedAlias),
                    'score' => $this->aliasScore($satker, $alias),
                ];
            }
        }

        return collect($items)
            ->unique(fn (array $item) => $item['satker_id'].'|'.$item['alias_condensed'])
            ->sortByDesc(fn (array $item) => [$item['score'], $item['alias_length']])
            ->values();
    }

    /**
     * @return array<int, string>
     */
    private function buildAliasesForSatker(Satker $satker): array
    {
        $name = $this->normalize($satker->name);
        $code = $this->normalize($satker->code);
        $aliases = [$name];

        if ($code !== '') {
            $aliases[] = $code;
        }

        if (Str::startsWith($name, 'SAT') && ! Str::startsWith($name, 'SAT ')) {
            $aliases[] = 'SAT '.trim(substr($name, 3));
        }

        if ($satker->recipientScope() === 'polda' && ! in_array($name, ['POLDA NTB', 'POLDA-NTB'], true)) {
            $aliases[] = $name.' POLDA NTB';
        }

        if ($satker->recipientScope() === 'polres' && Str::startsWith($name, 'POLRES ')) {
            $aliases[] = str_replace('POLRES ', 'POLRES', $name);
        }

        if ($satker->recipientScope() === 'polres' && Str::startsWith($name, 'POLRESTA ')) {
            $aliases[] = str_replace('POLRESTA ', 'POLRESTA', $name);
        }

        $aliases = array_merge($aliases, $this->deriveStructuralAliases($name));

        return array_values(array_unique(array_filter($aliases)));
    }

    /**
     * @return array<int, string>
     */
    private function deriveStructuralAliases(string $name): array
    {
        $aliases = [];

        if (Str::startsWith($name, 'BIRO ')) {
            $unit = trim(substr($name, 5));
            $aliases[] = $unit;
            $aliases[] = 'KARO '.$unit;
            $aliases[] = 'KEPALA BIRO '.$unit;

            $shortBiroAliases = match ($unit) {
                'LOGISTIK' => ['ROLOG', 'BIRO LOG', 'RO LOG'],
                'SDM' => ['ROSDM', 'RO SDM'],
                'RENA' => ['RORENA', 'RO RENA'],
                'OPS' => ['ROOPS', 'RO OPS'],
                default => [],
            };

            foreach ($shortBiroAliases as $shortAlias) {
                $aliases[] = $shortAlias;
                $aliases[] = 'KARO '.$shortAlias;
                $aliases[] = 'BAMIN '.$shortAlias;
            }
        }

        if (Str::startsWith($name, 'BID ')) {
            $unit = trim(substr($name, 4));
            $aliases[] = $unit;
            $aliases[] = 'KABID '.$unit;
            $aliases[] = 'KEPALA BIDANG '.$unit;
        }

        if (Str::startsWith($name, 'DIT ')) {
            $unit = trim(substr($name, 4));
            $aliases[] = $unit;
            $aliases[] = 'DIR '.$unit;
            $aliases[] = 'DIREKTUR '.$unit;
        }

        if (Str::startsWith($name, 'SAT ')) {
            $unit = trim(substr($name, 4));
            $aliases[] = $unit;
            $aliases[] = 'KASAT '.$unit;
            $aliases[] = 'KEPALA SATUAN '.$unit;
        }

        return $aliases;
    }

    private function aliasScore(Satker $satker, string $alias): int
    {
        $normalizedAlias = $this->normalize($alias);
        $score = strlen($this->condense($normalizedAlias));

        if (str_contains($normalizedAlias, 'POLRES') || str_contains($normalizedAlias, 'POLRESTA')) {
            $score += 1000;
        }

        if (str_contains($normalizedAlias, 'POLDA NTB')) {
            $score += 500;
        }

        if ($satker->code === 'POLDA-NTB') {
            $score -= 800;
        }

        return $score;
    }

    private function normalize(string $value): string
    {
        $normalized = Str::upper(trim($value));
        $normalized = str_replace(['.', ',', ';', ':', '(', ')'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function condense(string $value): string
    {
        return str_replace([' ', '-', '/', '\\'], '', $value);
    }
}
