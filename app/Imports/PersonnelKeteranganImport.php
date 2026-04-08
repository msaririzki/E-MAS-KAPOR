<?php

namespace App\Imports;

use App\Models\Personnel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class PersonnelKeteranganImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows): void {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generatePreview(Collection $rows): array
    {
        $preview = [];
        $seenIds = [];

        foreach ($rows->values() as $index => $row) {
            $rowArray = is_array($row) ? $row : $row->toArray();
            $rowNum = $index + 2;
            $idValue = trim((string) ($rowArray['id'] ?? ''));

            if ($idValue === '') {
                $preview[] = $this->buildErrorRow($rowNum, $rowArray, 'ID personel wajib diisi.');

                continue;
            }

            if (! ctype_digit($idValue) || (int) $idValue <= 0) {
                $preview[] = $this->buildErrorRow($rowNum, $rowArray, 'ID personel tidak valid.');

                continue;
            }

            $personnelId = (int) $idValue;

            if (isset($seenIds[$personnelId])) {
                $preview[] = $this->buildErrorRow($rowNum, $rowArray, 'ID personel duplikat dalam file.');

                continue;
            }

            $seenIds[$personnelId] = true;

            $personnel = Personnel::with(['rank:id,name', 'satker:id,name', 'user:id,nrp_nip'])->find($personnelId);

            if ($personnel === null) {
                $preview[] = $this->buildErrorRow($rowNum, $rowArray, 'Personel dengan ID tersebut tidak ditemukan.');

                continue;
            }

            $newValues = [
                'keterangan_2' => $this->normalizeNullableString($rowArray['keterangan_2'] ?? null),
                'keterangan_3' => $this->normalizeNullableString($rowArray['keterangan_3'] ?? null),
                'keterangan_4' => $this->normalizeNullableString($rowArray['keterangan_4'] ?? null),
            ];

            $existingValues = [
                'keterangan_2' => $this->normalizeNullableString($personnel->keterangan_2),
                'keterangan_3' => $this->normalizeNullableString($personnel->keterangan_3),
                'keterangan_4' => $this->normalizeNullableString($personnel->keterangan_4),
            ];

            $diff = [];
            foreach ($newValues as $field => $value) {
                if ($value !== $existingValues[$field]) {
                    $diff[$field] = [
                        'from' => $existingValues[$field],
                        'to' => $value,
                    ];
                }
            }

            $preview[] = [
                'row_num' => $rowNum,
                'status' => $diff === [] ? 'no_change' : 'update',
                'id' => $personnel->id,
                'full_name' => $personnel->full_name,
                'nrp_nip' => $personnel->user?->nrp_nip ?? $personnel->nrp,
                'satker_name' => $personnel->satker?->name,
                'rank_name' => $personnel->rank?->name,
                'golongan' => $personnel->golongan,
                'gender' => $personnel->gender,
                'religion' => $personnel->religion,
                'jabatan' => $personnel->jabatan,
                'bagian' => $personnel->bagian,
                'keterangan' => $personnel->keterangan,
                'keterangan_2' => $newValues['keterangan_2'],
                'keterangan_3' => $newValues['keterangan_3'],
                'keterangan_4' => $newValues['keterangan_4'],
                'existing_keterangan_2' => $existingValues['keterangan_2'],
                'existing_keterangan_3' => $existingValues['keterangan_3'],
                'existing_keterangan_4' => $existingValues['keterangan_4'],
                'reference_warnings' => $this->buildReferenceWarnings($rowArray, $personnel),
                'diff' => $diff,
                'error_message' => null,
            ];
        }

        return $preview;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{updated_count:int,no_change_count:int,error_count:int,errors:array<int, string>}
     */
    public function saveFromPreviewData(array $rows): array
    {
        $updatedCount = 0;
        $noChangeCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::transaction(function () use ($rows, &$updatedCount, &$noChangeCount, &$errorCount, &$errors) {
            foreach ($rows as $index => $row) {
                $status = $row['status'] ?? 'error';
                $rowLabel = 'Baris '.($row['row_num'] ?? ($index + 2));

                if ($status === 'error') {
                    $errorCount++;
                    $errors[] = $rowLabel.': '.($row['error_message'] ?? 'Data tidak valid.');

                    continue;
                }

                if ($status === 'no_change') {
                    $noChangeCount++;

                    continue;
                }

                $personnelId = (int) ($row['id'] ?? 0);
                $personnel = Personnel::find($personnelId);

                if ($personnel === null) {
                    $errorCount++;
                    $errors[] = $rowLabel.': Personel tidak ditemukan saat konfirmasi.';

                    continue;
                }

                $personnel->update([
                    'keterangan_2' => $this->normalizeNullableString($row['keterangan_2'] ?? null),
                    'keterangan_3' => $this->normalizeNullableString($row['keterangan_3'] ?? null),
                    'keterangan_4' => $this->normalizeNullableString($row['keterangan_4'] ?? null),
                ]);

                $updatedCount++;
            }
        });

        return [
            'updated_count' => $updatedCount,
            'no_change_count' => $noChangeCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function buildErrorRow(int $rowNum, array $row, string $message): array
    {
        return [
            'row_num' => $rowNum,
            'status' => 'error',
            'id' => trim((string) ($row['id'] ?? '')),
            'full_name' => trim((string) ($row['nama'] ?? '')),
            'nrp_nip' => trim((string) ($row['nrp_nip'] ?? '')),
            'satker_name' => trim((string) ($row['satker'] ?? '')),
            'rank_name' => trim((string) ($row['pangkat'] ?? '')),
            'golongan' => trim((string) ($row['golongan'] ?? '')),
            'gender' => trim((string) ($row['jenis_kelamin'] ?? '')),
            'religion' => trim((string) ($row['agama'] ?? '')),
            'jabatan' => trim((string) ($row['jabatan'] ?? '')),
            'bagian' => trim((string) ($row['bag_fungsi'] ?? '')),
            'keterangan' => trim((string) ($row['keterangan_1'] ?? '')),
            'keterangan_2' => $this->normalizeNullableString($row['keterangan_2'] ?? null),
            'keterangan_3' => $this->normalizeNullableString($row['keterangan_3'] ?? null),
            'keterangan_4' => $this->normalizeNullableString($row['keterangan_4'] ?? null),
            'existing_keterangan_2' => null,
            'existing_keterangan_3' => null,
            'existing_keterangan_4' => null,
            'reference_warnings' => [],
            'diff' => [],
            'error_message' => $message,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    private function buildReferenceWarnings(array $row, Personnel $personnel): array
    {
        $referenceMap = [
            'nama' => ['label' => 'Nama', 'actual' => $personnel->full_name],
            'nrp_nip' => ['label' => 'NRP/NIP', 'actual' => $personnel->user?->nrp_nip ?? $personnel->nrp],
            'satker' => ['label' => 'Satker', 'actual' => $personnel->satker?->name],
            'pangkat' => ['label' => 'Pangkat', 'actual' => $personnel->rank?->name],
            'golongan' => ['label' => 'Golongan', 'actual' => $personnel->golongan],
            'jenis_kelamin' => ['label' => 'Jenis Kelamin', 'actual' => $personnel->gender],
            'agama' => ['label' => 'Agama', 'actual' => $personnel->religion],
            'jabatan' => ['label' => 'Jabatan', 'actual' => $personnel->jabatan],
            'bag_fungsi' => ['label' => 'Bag/Fungsi', 'actual' => $personnel->bagian],
            'keterangan_1' => ['label' => 'Keterangan 1', 'actual' => $personnel->keterangan],
        ];

        $warnings = [];

        foreach ($referenceMap as $column => $meta) {
            if (! array_key_exists($column, $row)) {
                continue;
            }

            if ($this->shouldIgnoreReferenceWarning($column, $row[$column] ?? null)) {
                continue;
            }

            $fileValue = $this->normalizeReferenceValue($column, $row[$column] ?? null);
            if ($fileValue === null) {
                continue;
            }

            $actualValue = $this->normalizeReferenceValue($column, $meta['actual']);
            if ($fileValue !== $actualValue) {
                $warnings[] = $meta['label'].' pada file berbeda dari data saat ini.';
            }
        }

        return $warnings;
    }

    private function shouldIgnoreReferenceWarning(string $column, mixed $value): bool
    {
        if ($column !== 'nrp_nip') {
            return false;
        }

        $normalized = $this->normalizeNullableString($value);
        if ($normalized === null) {
            return false;
        }

        $scientificCandidate = str_replace(',', '.', strtoupper($normalized));

        return preg_match('/^([+-]?\d+(?:\.\d+)?)E([+-]?\d+)$/', $scientificCandidate) === 1;
    }

    private function normalizeReferenceValue(string $column, mixed $value): ?string
    {
        if ($column === 'nrp_nip') {
            return $this->normalizeIdentifierString($value);
        }

        return $this->normalizeNullableString($value);
    }

    private function normalizeIdentifierString(mixed $value): ?string
    {
        $normalized = $this->normalizeNullableString($value);

        if ($normalized === null) {
            return null;
        }

        $normalized = ltrim($normalized, "'\t");

        if (preg_match('/^\d+(?:\.0+)?$/', $normalized) === 1) {
            return preg_replace('/\.0+$/', '', $normalized);
        }

        $scientificCandidate = str_replace(',', '.', strtoupper($normalized));
        if (preg_match('/^([+-]?\d+(?:\.\d+)?)E([+-]?\d+)$/', $scientificCandidate, $matches) === 1) {
            return $this->expandScientificNotation($matches[1], (int) $matches[2]);
        }

        return $normalized;
    }

    private function expandScientificNotation(string $mantissa, int $exponent): string
    {
        $mantissa = ltrim($mantissa, '+');
        $negative = str_starts_with($mantissa, '-');
        $mantissa = ltrim($mantissa, '-');

        $parts = explode('.', $mantissa, 2);
        $whole = $parts[0] ?? '0';
        $fraction = $parts[1] ?? '';
        $digits = ltrim($whole.$fraction, '0');
        $digits = $digits === '' ? '0' : $digits;
        $scale = strlen($fraction);

        if ($exponent >= $scale) {
            $result = $digits.str_repeat('0', $exponent - $scale);
        } else {
            $integerLength = strlen($digits) - ($scale - $exponent);
            $result = $integerLength <= 0 ? '0' : substr($digits, 0, $integerLength);
        }

        return $negative ? '-'.$result : $result;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
