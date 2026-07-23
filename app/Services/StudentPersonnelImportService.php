<?php

namespace App\Services;

use App\Imports\StudentPersonnelRowsImport;
use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\StudentBatch;
use App\Models\User;
use App\Support\GolonganNormalizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class StudentPersonnelImportService
{
    private const SIZE_COLUMNS = [
        13 => 'topi',
        14 => 'kemeja',
        15 => 'celana',
        16 => 'olahraga',
        17 => 'sepatu_dinas',
        18 => 'sepatu_olahraga',
        19 => 'jaket',
        20 => 'sabuk',
        21 => 'jilbab',
    ];

    public function __construct(private readonly KaporRequirementService $kaporRequirementService) {}

    public function preview(UploadedFile $file, int $satkerId): array
    {
        $satker = Satker::query()->findOrFail($satkerId);
        $sheets = Excel::toCollection(new StudentPersonnelRowsImport, $file);
        $rows = $sheets->first() ?? collect();
        $ranks = Rank::query()
            ->get(['id', 'name', 'category'])
            ->keyBy(fn (Rank $rank): string => Str::upper(trim($rank->name)));

        $prepared = $this->prepareRows($rows);
        $nrpCounts = $prepared->pluck('nrp')->filter()->countBy();
        $nrps = $nrpCounts->keys()->values();
        $personnelByNrp = Personnel::query()
            ->whereIn('nrp', $nrps)
            ->get(['id', 'student_batch_id', 'student_code', 'nrp', 'full_name', 'gender', 'personnel_type', 'procurement_group', 'rank_id', 'golongan', 'jabatan', 'bagian', 'religion', 'keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4', 'satker_id', 'is_active', 'kapor_sizes'])
            ->groupBy('nrp');
        $userNrps = User::query()->whereIn('nrp_nip', $nrps)->pluck('nrp_nip')->flip();

        $preview = $prepared->map(function (array $row) use ($nrpCounts, $personnelByNrp, $ranks, $satker, $userNrps): array {
            $errors = [];
            $rank = $ranks->get(Str::upper($row['rank_input']));
            $gender = $this->normalizeGender($row['gender_input']);
            $existing = null;
            $existingMatches = $row['nrp'] !== '' ? $personnelByNrp->get($row['nrp'], collect()) : collect();

            if ($row['name'] === '') {
                $errors[] = 'Nama wajib diisi.';
            }
            if ($rank === null) {
                $errors[] = 'Pangkat tidak ditemukan pada referensi aplikasi.';
            }
            if ($row['nrp'] === '') {
                $errors[] = 'NRP/NIP wajib diisi.';
            }
            if ($row['jabatan'] === '') {
                $errors[] = 'Jabatan wajib diisi.';
            }
            if ($row['bagian'] === '') {
                $errors[] = 'Bag/fungsi wajib diisi.';
            }
            if ($gender === null) {
                $errors[] = 'Jenis kelamin harus P untuk pria atau W untuk wanita.';
            }
            if ($row['nrp'] !== '' && ($nrpCounts->get($row['nrp'], 0) > 1)) {
                $errors[] = 'NRP/NIP muncul lebih dari sekali dalam file.';
            }
            if ($row['nrp'] !== '' && $userNrps->has($row['nrp'])) {
                $errors[] = 'NRP/NIP sudah terdaftar sebagai akun login personel.';
            }
            if ($existingMatches->count() > 1) {
                $errors[] = 'NRP/NIP memiliki lebih dari satu data personel di database.';
            } elseif ($existingMatches->count() === 1) {
                $existing = $existingMatches->first();
                if ($existing->student_batch_id === null) {
                    $errors[] = 'NRP/NIP sudah dipakai oleh personel biasa '.$existing->full_name.'.';
                }
            }

            $golongan = trim($row['golongan']);
            if ($rank?->category === 'PNS') {
                $golongan = GolonganNormalizer::major($golongan) ?? '';
                if ($golongan === '') {
                    $errors[] = 'Golongan PNS harus diisi dengan angka 1, 2, 3, atau 4.';
                }
            }

            [$sizes, $sizeErrors] = $this->parseSizes($row['size_inputs'], $gender);
            $errors = array_merge($errors, $sizeErrors);
            $values = [
                'full_name' => $row['name'],
                'nrp' => $row['nrp'],
                'rank_id' => $rank?->id,
                'rank_name' => $rank?->name ?? $row['rank_input'],
                'golongan' => $golongan !== '' ? $golongan : null,
                'jabatan' => $row['jabatan'],
                'bagian' => $row['bagian'],
                'gender' => $gender,
                'gender_label' => $gender === 'P' ? 'Wanita' : ($gender === 'L' ? 'Pria' : '-'),
                'personnel_type' => $rank?->category === 'PNS' ? 'PNS' : 'Polri',
                'procurement_group' => $this->procurementGroup($rank),
                'religion' => $row['religion'] !== '' ? $row['religion'] : null,
                'keterangan' => $row['keterangan'] !== '' ? $row['keterangan'] : null,
                'keterangan_2' => $row['keterangan_2'] !== '' ? $row['keterangan_2'] : null,
                'keterangan_3' => $row['keterangan_3'] !== '' ? $row['keterangan_3'] : null,
                'keterangan_4' => $row['keterangan_4'] !== '' ? $row['keterangan_4'] : null,
                'satker_id' => $satker->id,
                'satker_name' => $satker->name,
                'sizes' => $sizes,
            ];

            $status = 'error';
            if ($errors === []) {
                if ($existing === null) {
                    $status = 'create';
                } else {
                    $status = $this->hasChanges($existing, $values) ? 'update' : 'no_change';
                }
            }

            return array_merge($values, [
                'row_number' => $row['row_number'],
                'personnel_id' => $existing?->id,
                'student_code' => $existing?->student_code,
                'existing_name' => $existing?->full_name,
                'errors' => $errors,
                'status' => $status,
            ]);
        })->values();

        return [
            'satker_id' => $satker->id,
            'satker_name' => $satker->name,
            'source_file' => $file->getClientOriginalName(),
            'rows' => $preview->all(),
            'stats' => [
                'total' => $preview->count(),
                'create' => $preview->where('status', 'create')->count(),
                'update' => $preview->where('status', 'update')->count(),
                'no_change' => $preview->where('status', 'no_change')->count(),
                'error' => $preview->where('status', 'error')->count(),
            ],
        ];
    }

    public function save(array $rows, int $satkerId, ?int $createdBy, ?string $sourceFile = null): array
    {
        $actionRows = collect($rows)->whereIn('status', ['create', 'update'])->values();
        $unchanged = collect($rows)->where('status', 'no_change')->count();

        if ($actionRows->isEmpty()) {
            return ['created' => 0, 'updated' => 0, 'unchanged' => $unchanged, 'batch_id' => null];
        }

        return DB::transaction(function () use ($actionRows, $createdBy, $satkerId, $sourceFile, $unchanged): array {
            $satker = Satker::query()->lockForUpdate()->findOrFail($satkerId);
            $nrps = $actionRows->pluck('nrp')->filter()->unique()->values();
            $currentPersonnel = Personnel::query()
                ->whereIn('nrp', $nrps)
                ->lockForUpdate()
                ->get(['id', 'student_batch_id', 'student_code', 'nrp'])
                ->groupBy('nrp');

            if (User::query()->whereIn('nrp_nip', $nrps)->exists()) {
                throw new RuntimeException('Terdapat NRP/NIP yang sudah berubah menjadi akun login. Unggah ulang file untuk memperoleh pratinjau terbaru.');
            }

            foreach ($actionRows as $row) {
                $matches = $currentPersonnel->get($row['nrp'], collect());
                if ($row['status'] === 'create' && $matches->isNotEmpty()) {
                    throw new RuntimeException('NRP/NIP '.$row['nrp'].' sudah digunakan setelah pratinjau dibuat.');
                }
                if ($row['status'] === 'update') {
                    $match = $matches->firstWhere('id', (int) $row['personnel_id']);
                    if ($match === null || $match->student_batch_id === null) {
                        throw new RuntimeException('Data siswa '.$row['nrp'].' berubah setelah pratinjau dibuat.');
                    }
                }
            }

            $year = (int) Setting::getValue('fiscal_year', now()->year);
            $batch = StudentBatch::query()->create([
                'code' => $this->newBatchCode($year),
                'name' => 'Unggah Siswa '.$satker->name.' '.now()->translatedFormat('d F Y H:i'),
                'fiscal_year' => $year,
                'satker_id' => $satker->id,
                'procurement_group' => 'CAMPURAN',
                'default_rank_id' => null,
                'default_jabatan' => null,
                'default_bagian' => null,
                'requested_male_count' => $actionRows->where('gender', 'L')->count(),
                'requested_female_count' => $actionRows->where('gender', 'P')->count(),
                'status' => StudentBatch::STATUS_ACTIVE,
                'notes' => filled($sourceFile) ? 'Sumber: '.Str::limit((string) $sourceFile, 900) : null,
                'created_by' => $createdBy,
            ]);

            $now = now();
            $createdRows = [];
            $updatedRows = [];
            $oldBatchIds = [];
            $createSequence = 1;

            foreach ($actionRows as $row) {
                $base = $this->databasePayload($row, $batch->id, $satker->id, $now);

                if ($row['status'] === 'create') {
                    $base['user_id'] = null;
                    $base['student_code'] = sprintf('%s-%05d', $batch->code, $createSequence++);
                    $base['created_at'] = $now;
                    $createdRows[] = $base;

                    continue;
                }

                $existing = $currentPersonnel->get($row['nrp'])->firstWhere('id', (int) $row['personnel_id']);
                if ($existing?->student_batch_id !== null) {
                    $oldBatchIds[] = $existing->student_batch_id;
                }
                $base['id'] = (int) $row['personnel_id'];
                $updatedRows[] = $base;
            }

            foreach (array_chunk($createdRows, 500) as $chunk) {
                Personnel::query()->insert($chunk);
            }
            foreach (array_chunk($updatedRows, 500) as $chunk) {
                DB::table('personnels')->upsert($chunk, ['id'], [
                    'student_batch_id', 'nrp', 'full_name', 'gender', 'personnel_type',
                    'procurement_group', 'rank_id', 'golongan', 'jabatan', 'bagian',
                    'keterangan', 'keterangan_2', 'keterangan_3', 'keterangan_4',
                    'satker_id', 'religion', 'is_active', 'verification_status',
                    'kapor_sizes', 'updated_at',
                ]);
            }

            $this->refreshBatchCounts(array_values(array_unique(array_merge([$batch->id], $oldBatchIds))));

            return [
                'created' => count($createdRows),
                'updated' => count($updatedRows),
                'unchanged' => $unchanged,
                'batch_id' => $batch->id,
            ];
        });
    }

    private function prepareRows(Collection $rows): Collection
    {
        return $rows->map(function ($row, int $index): ?array {
            $values = $row instanceof Collection ? $row->toArray() : (array) $row;
            $name = $this->cleanText($values[1] ?? null);
            $nrp = User::normalizeLoginIdentifier($values[4] ?? '');

            if ($name === '' && $nrp === '') {
                return null;
            }

            return [
                'row_number' => $index + 11,
                'name' => $name,
                'rank_input' => $this->cleanText($values[2] ?? null),
                'golongan' => $this->cleanText($values[3] ?? null),
                'nrp' => $nrp,
                'jabatan' => $this->cleanText($values[5] ?? null),
                'bagian' => $this->cleanText($values[6] ?? null),
                'gender_input' => $this->cleanText($values[7] ?? null),
                'religion' => $this->cleanText($values[8] ?? null),
                'keterangan' => $this->cleanText($values[9] ?? null),
                'keterangan_2' => $this->cleanText($values[10] ?? null),
                'keterangan_3' => $this->cleanText($values[11] ?? null),
                'keterangan_4' => $this->cleanText($values[12] ?? null),
                'size_inputs' => collect(self::SIZE_COLUMNS)
                    ->mapWithKeys(fn (string $key, int $column): array => [$key => $values[$column] ?? null])
                    ->all(),
            ];
        })->filter()->values();
    }

    private function parseSizes(array $inputs, ?string $gender): array
    {
        $sizes = [];
        $errors = [];

        foreach ($inputs as $key => $value) {
            $raw = trim((string) $value);
            if ($raw === '' || $raw === '-') {
                continue;
            }
            if ($gender === 'L' && $key === 'jilbab') {
                continue;
            }

            $sanitized = $this->kaporRequirementService->sanitizeSizeValue($key, $raw);
            if ($sanitized === null) {
                $errors[] = 'Ukuran '.$this->sizeLabel($key).' tidak valid.';
            } else {
                $sizes[$key] = $sanitized;
            }
        }

        return [$sizes, $errors];
    }

    private function hasChanges(Personnel $personnel, array $values): bool
    {
        $currentSizes = $personnel->kapor_sizes ?? [];
        $incomingSizes = $values['sizes'];
        ksort($currentSizes);
        ksort($incomingSizes);

        return $personnel->full_name !== $values['full_name']
            || $personnel->nrp !== $values['nrp']
            || (int) $personnel->rank_id !== (int) $values['rank_id']
            || $personnel->golongan !== $values['golongan']
            || $personnel->jabatan !== $values['jabatan']
            || $personnel->bagian !== $values['bagian']
            || $personnel->gender !== $values['gender']
            || $personnel->personnel_type !== $values['personnel_type']
            || $personnel->procurement_group !== $values['procurement_group']
            || $personnel->religion !== $values['religion']
            || $personnel->keterangan !== $values['keterangan']
            || $personnel->keterangan_2 !== $values['keterangan_2']
            || $personnel->keterangan_3 !== $values['keterangan_3']
            || $personnel->keterangan_4 !== $values['keterangan_4']
            || (int) $personnel->satker_id !== (int) $values['satker_id']
            || ! $personnel->is_active
            || $currentSizes !== $incomingSizes;
    }

    private function databasePayload(array $row, int $batchId, int $satkerId, mixed $now): array
    {
        return [
            'student_batch_id' => $batchId,
            'nrp' => $row['nrp'],
            'full_name' => $row['full_name'],
            'gender' => $row['gender'],
            'personnel_type' => $row['personnel_type'],
            'procurement_group' => $row['procurement_group'],
            'rank_id' => $row['rank_id'],
            'golongan' => $row['golongan'],
            'jabatan' => $row['jabatan'],
            'bagian' => $row['bagian'],
            'keterangan' => $row['keterangan'],
            'keterangan_2' => $row['keterangan_2'],
            'keterangan_3' => $row['keterangan_3'],
            'keterangan_4' => $row['keterangan_4'],
            'satker_id' => $satkerId,
            'religion' => $row['religion'],
            'is_active' => true,
            'verification_status' => 'approved',
            'kapor_sizes' => $row['sizes'] !== [] ? json_encode($row['sizes'], JSON_THROW_ON_ERROR) : null,
            'updated_at' => $now,
        ];
    }

    private function refreshBatchCounts(array $batchIds): void
    {
        StudentBatch::query()->whereIn('id', $batchIds)->get()->each(function (StudentBatch $batch): void {
            $batch->update([
                'requested_male_count' => $batch->students()->where('gender', 'L')->count(),
                'requested_female_count' => $batch->students()->where('gender', 'P')->count(),
            ]);
        });
    }

    private function procurementGroup(?Rank $rank): ?string
    {
        $category = Str::upper((string) $rank?->category);

        return array_key_exists($category, StudentBatch::GROUPS) ? $category : null;
    }

    private function normalizeGender(mixed $value): ?string
    {
        return match (Str::upper(trim((string) $value))) {
            'P', 'L', 'PRIA', 'LAKI-LAKI', 'LAKI LAKI' => 'L',
            'W', 'WANITA', 'PEREMPUAN' => 'P',
            default => null,
        };
    }

    private function newBatchCode(int $year): string
    {
        do {
            $code = sprintf('SISWA-IMPORT-%d-%s-%s', $year, now()->format('YmdHis'), Str::upper(Str::random(4)));
        } while (StudentBatch::query()->where('code', $code)->exists());

        return $code;
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) $value));
    }

    private function sizeLabel(string $key): string
    {
        return match ($key) {
            'topi' => 'topi',
            'kemeja' => 'kemeja',
            'celana' => 'celana/rok',
            'olahraga' => 'T-shirt/olahraga',
            'sepatu_dinas' => 'sepatu dinas',
            'sepatu_olahraga' => 'sepatu olahraga',
            'jaket' => 'jaket',
            'sabuk' => 'sabuk',
            'jilbab' => 'jilbab',
            default => $key,
        };
    }
}
