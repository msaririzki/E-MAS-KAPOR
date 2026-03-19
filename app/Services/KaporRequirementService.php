<?php

namespace App\Services;

use App\Models\BudgetPackage;
use App\Models\KaporItem;
use App\Models\Personnel;
use Illuminate\Database\Eloquent\Builder;

class KaporRequirementService
{
    public function applyRecipientFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['personnel_type'])) {
            $mappedTypes = array_map(fn ($type) => match (strtolower((string) $type)) {
                'polri' => 'Polri',
                'pns' => 'PNS',
                'pppk' => 'PPPK',
                default => $type,
            }, $filters['personnel_type']);

            $query->whereIn('personnel_type', $mappedTypes);
        }

        if (! empty($filters['gender'])) {
            $query->whereIn('gender', $filters['gender']);
        }

        if (! empty($filters['rank_categories'])) {
            $query->whereHas('rank', fn ($rankQuery) => $rankQuery->whereIn('category', $filters['rank_categories']));
        }

        if (! empty($filters['keterangan'])) {
            $this->applyKeteranganFilter($query, $filters['keterangan']);
        }

        if (! empty($filters['golongan'])) {
            $query->whereIn('golongan', $filters['golongan']);
        }

        return $query;
    }

    public function buildPackageSizeWarnings(BudgetPackage $budgetPackage): array
    {
        if (! $budgetPackage->relationLoaded('items')) {
            $budgetPackage->load([
                'items.kaporItem.sizes',
                'items.recipients.satker',
            ]);
        }

        $warnings = [];

        foreach ($budgetPackage->items as $item) {
            $kaporItem = $item->kaporItem;
            if ($kaporItem === null) {
                continue;
            }

            $sizeKey = $this->resolveSizeKey($kaporItem->item_name);
            if ($sizeKey === null) {
                continue;
            }

            $sizeCollection = $kaporItem->relationLoaded('sizes')
                ? $kaporItem->sizes
                : $kaporItem->sizes()->get();

            if ($sizeCollection->isEmpty()) {
                continue;
            }

            $itemTotal = 0;
            $itemValid = 0;
            $bySatker = [];

            foreach ($item->recipients as $recipient) {
                $satker = $recipient->satker;
                if ($satker === null) {
                    continue;
                }

                $query = Personnel::query()
                    ->where('satker_id', $satker->id)
                    ->where('is_active', true);

                $this->applyRecipientFilters($query, $recipient->recipient_filters ?? []);

                $personnels = $query->get([
                    'gender',
                    'kapor_sizes',
                    'keterangan',
                    'keterangan_2',
                    'keterangan_3',
                    'keterangan_4',
                ]);

                $satkerTotal = 0;
                $satkerValid = 0;

                foreach ($personnels as $personnel) {
                    if (! $this->personnelRequiresSizeKey($personnel, $sizeKey)) {
                        continue;
                    }

                    $satkerTotal++;

                    if ($this->personnelHasValidSizeForItem($personnel, $kaporItem, $sizeKey)) {
                        $satkerValid++;
                    }
                }

                $satkerMissing = $satkerTotal - $satkerValid;
                $itemTotal += $satkerTotal;
                $itemValid += $satkerValid;

                if ($satkerMissing > 0) {
                    $bySatker[] = [
                        'satker_id' => $satker->id,
                        'satker_name' => $satker->name,
                        'total' => $satkerTotal,
                        'valid' => $satkerValid,
                        'missing' => $satkerMissing,
                    ];
                }
            }

            $itemMissing = $itemTotal - $itemValid;

            if ($itemMissing > 0) {
                $warnings[] = [
                    'kapor_item_id' => $kaporItem->id,
                    'item_name' => $kaporItem->item_name,
                    'size_key' => $sizeKey,
                    'size_label' => $this->sizeLabel($sizeKey),
                    'total' => $itemTotal,
                    'valid' => $itemValid,
                    'missing' => $itemMissing,
                    'by_satker' => $bySatker,
                ];
            }
        }

        return $warnings;
    }

    public function resolveSizeKey(string $itemName): ?string
    {
        $name = strtoupper($itemName);

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
        if (str_contains($name, 'OLAHRAGA') || str_contains($name, 'T-SHIRT') || str_contains($name, 'T SHIRT')) {
            return 'olahraga';
        }
        if (str_contains($name, 'SABUK')) {
            return 'sabuk';
        }

        return 'kemeja';
    }

    public function sizeLabel(string $sizeKey): string
    {
        return match ($sizeKey) {
            'topi' => 'Ukuran Topi',
            'kemeja' => 'Ukuran Kemeja',
            'celana' => 'Ukuran Celana/Rok',
            'olahraga' => 'Ukuran Olahraga',
            'sepatu_dinas' => 'Ukuran Sepatu Dinas',
            'sepatu_olahraga' => 'Ukuran Sepatu Olahraga',
            'jaket' => 'Ukuran Jaket',
            'sabuk' => 'Ukuran Sabuk',
            'jilbab' => 'Ukuran Jilbab',
            default => 'Ukuran Kapor',
        };
    }

    public function personnelRequiresSizeKey(Personnel $personnel, string $sizeKey): bool
    {
        if ($sizeKey !== 'jilbab') {
            return true;
        }

        return $personnel->gender === 'P' && $this->personnelHasHijabStatus($personnel);
    }

    public function requiredSizeKeysForPersonnel(Personnel $personnel): array
    {
        $required = ['topi', 'kemeja', 'celana', 'olahraga', 'sepatu_dinas', 'sepatu_olahraga', 'jaket', 'sabuk'];

        if ($this->personnelRequiresSizeKey($personnel, 'jilbab')) {
            $required[] = 'jilbab';
        }

        return $required;
    }

    public function personnelMissingRequiredSizeKeys(Personnel $personnel): array
    {
        $sizes = $this->extractPersonnelSizes($personnel);
        $missing = [];

        foreach ($this->requiredSizeKeysForPersonnel($personnel) as $sizeKey) {
            if ($this->sanitizeSizeValue($sizeKey, $sizes[$sizeKey] ?? null) === null) {
                $missing[] = $sizeKey;
            }
        }

        return $missing;
    }

    public function personnelHasAllRequiredSizes(Personnel $personnel): bool
    {
        return $this->personnelMissingRequiredSizeKeys($personnel) === [];
    }

    public function sanitizeSubmittedSizes(array $sizes, ?string $gender = null): array
    {
        $cleaned = [];

        foreach ($sizes as $sizeKey => $value) {
            if (! is_string($sizeKey)) {
                continue;
            }

            $sanitized = $this->sanitizeSizeValue($sizeKey, $value);
            if ($sanitized !== null) {
                $cleaned[$sizeKey] = $sanitized;
            }
        }

        if ($gender === 'L') {
            unset($cleaned['jilbab']);
        }

        return $cleaned;
    }

    public function sanitizeSizeValue(string $sizeKey, mixed $value): ?string
    {
        $normalized = $this->normalizeRawSizeValue($value);
        if ($normalized === null) {
            return null;
        }

        return match ($sizeKey) {
            'topi', 'sepatu_dinas', 'sepatu_olahraga', 'sabuk' => $this->normalizeIntegerSize($normalized),
            'kemeja' => $this->normalizeKemejaSize($normalized),
            'celana' => $this->normalizeCelanaSize($normalized),
            'olahraga', 'jaket' => $this->normalizeAlphaSize($normalized, $this->alphaClothingSizes()),
            'jilbab' => $this->normalizeAlphaSize($normalized, ['K', 'SD', 'B']),
            default => $this->normalizeValue($normalized),
        };
    }

    public function personnelHasValidSizeForItem(Personnel $personnel, KaporItem $kaporItem, ?string $sizeKey = null): bool
    {
        $sizeKey ??= $this->resolveSizeKey($kaporItem->item_name);

        if ($sizeKey === null || ! $this->personnelRequiresSizeKey($personnel, $sizeKey)) {
            return true;
        }

        $sizes = is_array($personnel->kapor_sizes)
            ? $personnel->kapor_sizes
            : (json_decode((string) $personnel->kapor_sizes, true) ?: []);

        $sizeValue = $this->normalizeValue($this->sanitizeSizeValue($sizeKey, $sizes[$sizeKey] ?? null));
        if ($sizeValue === null) {
            return false;
        }

        $validSizes = $this->validSizeLabelsForItem($kaporItem, $personnel->gender);
        if ($validSizes === []) {
            return false;
        }

        return in_array($sizeValue, $validSizes, true);
    }

    public function personnelMissingSizeForItem(Personnel $personnel, KaporItem $kaporItem, ?string $sizeKey = null): bool
    {
        $sizeKey ??= $this->resolveSizeKey($kaporItem->item_name);

        if ($sizeKey === null || ! $this->personnelRequiresSizeKey($personnel, $sizeKey)) {
            return false;
        }

        return ! $this->personnelHasValidSizeForItem($personnel, $kaporItem, $sizeKey);
    }

    public function personnelHasHijabStatus(Personnel $personnel): bool
    {
        foreach (['keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4'] as $field) {
            $normalized = $this->normalizeToken($personnel->{$field} ?? null);

            if ($normalized !== null && in_array($normalized, $this->hijabTokens(), true)) {
                return true;
            }
        }

        return false;
    }

    public function applyKeteranganFilter(Builder $query, array $values): Builder
    {
        $values = array_values(array_filter($values, fn ($value) => filled($value)));

        if ($values === []) {
            return $query;
        }

        return $query->where(function ($keteranganQuery) use ($values) {
            $keteranganQuery->whereIn('keterangan', $values)
                ->orWhereIn('keterangan_2', $values)
                ->orWhereIn('keterangan_3', $values)
                ->orWhereIn('keterangan_4', $values);
        });
    }

    public function applyHijabStatusConstraint(Builder $query): Builder
    {
        $tokens = $this->hijabTokens();

        return $query->where(function ($keteranganQuery) use ($tokens) {
            foreach (['keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4'] as $index => $field) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';

                $keteranganQuery->{$method}(
                    "UPPER(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$field}, ''), ' ', ''), '.', ''), '-', ''), '_', '')) IN (?, ?, ?, ?, ?, ?)",
                    $tokens
                );
            }
        });
    }

    public function validSizeLabelsForItem(KaporItem $kaporItem, ?string $gender): array
    {
        $sizeCollection = $kaporItem->relationLoaded('sizes')
            ? $kaporItem->sizes
            : $kaporItem->sizes()->get();

        $filtered = $sizeCollection
            ->filter(function ($size) use ($gender) {
                if ($gender === null) {
                    return true;
                }

                return $size->gender === null || $size->gender === $gender;
            })
            ->pluck('size_label')
            ->map(fn ($label) => $this->normalizeValue($label))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($filtered !== []) {
            return $filtered;
        }

        return $sizeCollection
            ->pluck('size_label')
            ->map(fn ($label) => $this->normalizeValue($label))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));
        $normalized = str_replace(',', '.', $normalized);

        return ($normalized === '' || $normalized === '-' || $normalized === 'NULL') ? null : $normalized;
    }

    private function normalizeToken(mixed $value): ?string
    {
        $normalized = $this->normalizeValue($value);

        return $normalized === null
            ? null
            : str_replace([' ', '.', '-', '_'], '', $normalized);
    }

    private function hijabTokens(): array
    {
        return ['BERJILBAB', 'BERJILBAP', 'BERHIJAB', 'BERHIJAP', 'HIJAB', 'JILBAB'];
    }

    private function normalizeRawSizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) $value));
        if ($normalized === '' || $normalized === '-' || $normalized === '0' || $normalized === 'NULL') {
            return null;
        }

        $normalized = ltrim($normalized, "'");
        $normalized = ltrim($normalized, '=');
        $normalized = str_replace(' ', '', $normalized);

        return ($normalized === '' || $normalized === '-' || $normalized === '0' || $normalized === 'NULL')
            ? null
            : $normalized;
    }

    private function normalizeIntegerSize(string $value): ?string
    {
        $candidate = str_replace(',', '.', $value);
        if (! preg_match('/^\d{1,3}(\.0+)?$/', $candidate)) {
            return null;
        }

        return (string) (int) $candidate;
    }

    private function normalizeKemejaSize(string $value): ?string
    {
        $alpha = $this->normalizeAlphaSize($value, $this->alphaClothingSizes());
        if ($alpha !== null) {
            return $alpha;
        }

        return $this->normalizeDecimalSize($value);
    }

    private function normalizeCelanaSize(string $value): ?string
    {
        $alpha = $this->normalizeAlphaSize($value, $this->alphaClothingSizes());
        if ($alpha !== null) {
            return $alpha;
        }

        return $this->normalizeIntegerSize($value);
    }

    private function normalizeDecimalSize(string $value): ?string
    {
        $candidate = str_replace(',', '.', $value);
        if (! preg_match('/^\d{1,3}(\.\d+)?$/', $candidate)) {
            return null;
        }

        return $value;
    }

    private function normalizeAlphaSize(string $value, array $allowed): ?string
    {
        $candidate = strtoupper(str_replace(' ', '', $value));

        return in_array($candidate, $allowed, true) ? $candidate : null;
    }

    private function alphaClothingSizes(): array
    {
        return ['K', 'SD', 'B', 'EB', 'EEB', 'EEEB', 'EEEEB'];
    }

    private function extractPersonnelSizes(Personnel $personnel): array
    {
        return is_array($personnel->kapor_sizes)
            ? $personnel->kapor_sizes
            : (json_decode((string) $personnel->kapor_sizes, true) ?: []);
    }
}
