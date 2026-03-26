<?php

namespace App\Services;

class PersonnelKeteranganService
{
    public function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(preg_replace('/\s+/', ' ', (string) $value));

        if ($normalized === '') {
            return null;
        }

        return $this->looksInvalid($normalized) ? null : $normalized;
    }

    public function looksInvalid(string $value): bool
    {
        $normalized = strtoupper(trim($value));

        if ($normalized === '') {
            return true;
        }

        // Nilai seperti "4|14,5|15|..." jelas berasal dari rekap ukuran, bukan keterangan personel.
        if (str_contains($normalized, '|')) {
            return true;
        }

        // Angka tunggal atau deretan angka/pemisah ukuran juga bukan keterangan personel.
        if (preg_match('/^[0-9]+$/', $normalized)) {
            return true;
        }

        if (preg_match('/^[0-9.,\/-]+$/', $normalized)) {
            return true;
        }

        return false;
    }
}
