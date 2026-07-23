<?php

namespace App\Services;

use App\Imports\StudentBatchRowsImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\StudentBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class StudentBatchImportService
{
    private const SIZE_COLUMNS = [
        9 => 'topi',
        10 => 'kemeja',
        11 => 'celana',
        12 => 'olahraga',
        13 => 'sepatu_dinas',
        14 => 'sepatu_olahraga',
        15 => 'jaket',
        16 => 'sabuk',
        17 => 'jilbab',
    ];

    public function __construct(private readonly KaporRequirementService $kaporRequirementService) {}

    public function preview(StudentBatch $batch, UploadedFile $file): array
    {
        $sheets = Excel::toCollection(new StudentBatchRowsImport, $file);
        $rows = $sheets->first() ?? collect();
        $students = $batch->students()->get()->keyBy('student_code');
        $ranks = Rank::query()
            ->where('category', '!=', 'PNS')
            ->get(['id', 'name'])
            ->keyBy(fn (Rank $rank) => Str::upper(trim($rank->name)));
        $preview = [];
        $seenCodes = [];
        $seenNrps = [];

        foreach ($rows as $rowIndex => $row) {
            $values = $row instanceof \Illuminate\Support\Collection ? $row->toArray() : (array) $row;
            $code = trim((string) ($values[1] ?? ''));

            if ($code === '' && trim((string) ($values[2] ?? '')) === '') {
                continue;
            }

            $errors = [];
            $student = $students->get($code);
            $name = trim(preg_replace('/\s+/', ' ', (string) ($values[2] ?? '')) ?? '');
            $rankName = trim((string) ($values[3] ?? ''));
            $rank = $ranks->get(Str::upper($rankName));
            $group = strtoupper(trim((string) ($values[4] ?? '')));
            $nrp = User::normalizeLoginIdentifier($values[5] ?? '');
            $jabatan = trim((string) ($values[6] ?? '')) ?: 'SISWA';
            $bagian = trim((string) ($values[7] ?? '')) ?: 'SISWA';
            $gender = $this->normalizeGender($values[8] ?? null);

            if ($student === null) {
                $errors[] = 'Kode sistem tidak ditemukan pada angkatan ini.';
            }
            if ($code !== '' && isset($seenCodes[$code])) {
                $errors[] = 'Kode sistem muncul lebih dari sekali dalam file.';
            }
            if ($name === '') {
                $errors[] = 'Nama tidak boleh kosong.';
            }
            if ($rank === null) {
                $errors[] = 'Pangkat tidak ditemukan pada master pangkat aplikasi.';
            }
            if (! array_key_exists($group, StudentBatch::GROUPS)) {
                $errors[] = 'Kelompok siswa tidak valid.';
            }
            if ($gender === null) {
                $errors[] = 'Jenis kelamin harus PRIA atau WANITA.';
            }
            if ($nrp !== '' && isset($seenNrps[$nrp]) && $seenNrps[$nrp] !== $code) {
                $errors[] = 'NRP/NIP muncul lebih dari sekali dalam file.';
            }

            $seenCodes[$code] = true;
            if ($nrp !== '') {
                $seenNrps[$nrp] = $code;
            }

            $sizes = [];
            foreach (self::SIZE_COLUMNS as $column => $sizeKey) {
                $raw = trim((string) ($values[$column] ?? ''));
                if ($raw === '' || $raw === '-') {
                    continue;
                }

                if ($gender === 'L' && $sizeKey === 'jilbab') {
                    continue;
                }

                $sanitized = $this->kaporRequirementService->sanitizeSizeValue($sizeKey, $raw);
                if ($sanitized === null) {
                    $errors[] = 'Ukuran '.$this->sizeLabel($sizeKey).' tidak valid.';
                } else {
                    $sizes[$sizeKey] = $sanitized;
                }
            }

            $preview[] = [
                'row_number' => $rowIndex + 5,
                'personnel_id' => $student?->id,
                'student_code' => $code,
                'name' => $name,
                'nrp' => $nrp !== '' ? $nrp : $code,
                'rank_id' => $rank?->id,
                'rank_name' => $rank?->name ?? $rankName,
                'procurement_group' => $group,
                'jabatan' => $jabatan,
                'bagian' => $bagian,
                'gender' => $gender,
                'gender_label' => $gender === 'P' ? 'Wanita' : 'Pria',
                'sizes' => $sizes,
                'religion' => trim((string) ($values[18] ?? '')) ?: null,
                'keterangan' => trim((string) ($values[19] ?? '')) ?: 'SISWA',
                'keterangan_2' => trim((string) ($values[20] ?? '')) ?: 'SISWA '.$group,
                'errors' => $errors,
                'status' => $errors === [] && $student !== null
                    ? ($this->hasChanges($student, [
                        'full_name' => $name,
                        'nrp' => $nrp !== '' ? $nrp : $code,
                        'rank_id' => $rank?->id,
                        'procurement_group' => $group,
                        'jabatan' => $jabatan,
                        'bagian' => $bagian,
                        'gender' => $gender,
                        'religion' => trim((string) ($values[18] ?? '')) ?: null,
                        'keterangan' => trim((string) ($values[19] ?? '')) ?: 'SISWA',
                        'keterangan_2' => trim((string) ($values[20] ?? '')) ?: 'SISWA '.$group,
                        'kapor_sizes' => $sizes,
                    ]) ? 'update' : 'no_change')
                    : 'error',
            ];
        }

        $this->markDatabaseNrpConflicts($preview);

        $previewCollection = collect($preview);

        return [
            'rows' => $preview,
            'stats' => [
                'total' => count($preview),
                'update' => $previewCollection->where('status', 'update')->count(),
                'no_change' => $previewCollection->where('status', 'no_change')->count(),
                'error' => $previewCollection->where('status', 'error')->count(),
            ],
        ];
    }

    public function save(StudentBatch $batch, array $rows): array
    {
        $updated = 0;
        $unchanged = 0;

        DB::transaction(function () use ($batch, $rows, &$updated, &$unchanged): void {
            foreach ($rows as $row) {
                if (($row['status'] ?? null) === 'error') {
                    continue;
                }

                if (($row['status'] ?? null) === 'no_change') {
                    $unchanged++;

                    continue;
                }

                $student = $batch->students()->whereKey($row['personnel_id'])->first();
                if ($student === null) {
                    continue;
                }

                $student->update([
                    'full_name' => $row['name'],
                    'nrp' => $row['nrp'],
                    'rank_id' => $row['rank_id'],
                    'procurement_group' => $row['procurement_group'],
                    'jabatan' => $row['jabatan'],
                    'bagian' => $row['bagian'],
                    'gender' => $row['gender'],
                    'religion' => $row['religion'],
                    'keterangan' => $row['keterangan'],
                    'keterangan_2' => $row['keterangan_2'],
                    'kapor_sizes' => $row['sizes'] !== [] ? $row['sizes'] : null,
                ]);
                $updated++;
            }

            $batch->update([
                'requested_male_count' => $batch->students()->where('gender', 'L')->count(),
                'requested_female_count' => $batch->students()->where('gender', 'P')->count(),
            ]);
        });

        return compact('updated', 'unchanged');
    }

    private function markDatabaseNrpConflicts(array &$preview): void
    {
        $nrps = collect($preview)
            ->whereNotNull('personnel_id')
            ->pluck('nrp')
            ->filter()
            ->unique()
            ->values();

        if ($nrps->isEmpty()) {
            return;
        }

        $existingByNrp = Personnel::query()
            ->whereIn('nrp', $nrps)
            ->get(['id', 'nrp', 'full_name'])
            ->groupBy('nrp');

        foreach ($preview as &$row) {
            if (($row['status'] ?? null) === 'error') {
                continue;
            }

            $conflict = $existingByNrp->get($row['nrp'], collect())
                ->first(fn (Personnel $personnel): bool => $personnel->id !== (int) $row['personnel_id']);

            if ($conflict !== null) {
                $row['errors'][] = 'NRP/NIP sudah digunakan oleh '.$conflict->full_name.'.';
                $row['status'] = 'error';
            }
        }
        unset($row);
    }

    private function hasChanges(Personnel $student, array $values): bool
    {
        return $student->full_name !== $values['full_name']
            || $student->nrp !== $values['nrp']
            || (int) $student->rank_id !== (int) $values['rank_id']
            || $student->procurement_group !== $values['procurement_group']
            || $student->jabatan !== $values['jabatan']
            || $student->bagian !== $values['bagian']
            || $student->gender !== $values['gender']
            || $student->religion !== $values['religion']
            || ($student->kapor_sizes ?? []) !== $values['kapor_sizes']
            || $student->keterangan !== $values['keterangan']
            || $student->keterangan_2 !== $values['keterangan_2'];
    }

    private function normalizeGender(mixed $value): ?string
    {
        return match (strtoupper(trim((string) $value))) {
            'L', 'P', 'PRIA', 'LAKI-LAKI', 'LAKI LAKI' => 'L',
            'W', 'WANITA', 'PEREMPUAN' => 'P',
            default => null,
        };
    }

    private function sizeLabel(string $key): string
    {
        return match ($key) {
            'topi' => 'topi',
            'kemeja' => 'kemeja',
            'celana' => 'celana/rok',
            'olahraga' => 'kaos olahraga',
            'sepatu_dinas' => 'sepatu dinas',
            'sepatu_olahraga' => 'sepatu olahraga',
            'jaket' => 'jaket',
            'sabuk' => 'sabuk',
            'jilbab' => 'jilbab',
            default => $key,
        };
    }
}
