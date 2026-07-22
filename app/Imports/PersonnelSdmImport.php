<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use App\Services\SdmSatkerResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PersonnelSdmImport extends PersonnelImport
{
    public function __construct(
        private readonly ?SdmSatkerResolver $satkerResolver = null
    ) {
        parent::__construct(null);
    }

    public function sheets(): array
    {
        return collect(range(0, 99))
            ->mapWithKeys(fn (int $index) => [$index => $this])
            ->all();
    }

    public function startRow(): int
    {
        return 3;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function generatePreview(Collection $rows, string|int|null $sheetName = null): array
    {
        $ranks = Rank::all()->keyBy(fn ($rank) => strtoupper($rank->name));
        $preview = [];
        $seenNrps = [];
        $resolver = $this->satkerResolver ?? app(SdmSatkerResolver::class);

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            $fullName = $this->normalizeText($row[1] ?? '');
            $rankInput = $this->normalizeText($row[2] ?? '');
            $nrp = $this->normalizeNrp($row[3] ?? '');
            $jabatan = $this->normalizeText($row[4] ?? '');
            $genderRaw = $this->normalizeText($row[5] ?? '');
            $religionRaw = $this->normalizeText($row[6] ?? '');

            if ($fullName === '') {
                continue;
            }

            $nameLower = strtolower($fullName);
            if (
                str_starts_with($fullName, '=')
                || in_array($nameLower, ['jumlah', 'total', 'dst', 'dst.', 'nama', 'nama lengkap', 'nama personel'], true)
            ) {
                continue;
            }

            $rankIsPlaceholder = $rankInput === ''
                || preg_match('/^[\-\.]+$/', $rankInput) === 1
                || (is_numeric($rankInput) && strlen($rankInput) <= 3);

            if ($nrp === '' && $rankIsPlaceholder && $jabatan === '' && $genderRaw === '' && $religionRaw === '') {
                continue;
            }

            if (strlen($fullName) < 2 || is_numeric(str_replace([' ', '.', ','], '', $fullName))) {
                continue;
            }

            $gender = $this->normalizeGender($genderRaw);
            $religion = $this->normalizeReligion($religionRaw);
            $rankResult = parent::findRankWithCorrection($rankInput, $ranks);
            $resolvedSatker = $resolver->resolve($jabatan);
            $golongan = $this->deriveGolongan($rankResult['rank']);

            $status = 'ok';
            $statusNotes = [];
            $incompleteFields = [];
            $fatalError = false;
            $requiresManualSatker = false;
            $requiresManualRank = false;

            if ($rankResult['rank'] === null && ! $rankIsPlaceholder) {
                $status = 'error';
                $requiresManualRank = true;
                $statusNotes[] = 'Pangkat tidak dikenali';
            } elseif ($rankResult['rank'] === null) {
                $status = 'corrected';
                $incompleteFields[] = 'Pangkat';
            }

            if ($jabatan === '') {
                $status = 'error';
                $fatalError = true;
                $statusNotes[] = 'Jabatan kosong';
            }

            if ($resolvedSatker['satker_id'] === null && $jabatan !== '') {
                $status = 'error';
                $requiresManualSatker = true;
                $statusNotes[] = 'Satker dari jabatan tidak dikenali';
            }

            if ($gender === null) {
                $status = 'error';
                $fatalError = true;
                $statusNotes[] = 'Jenis kelamin tidak valid';
            }

            if ($religion === null) {
                $status = 'error';
                $fatalError = true;
                $statusNotes[] = 'Agama tidak valid';
            }

            if ($nrp === '') {
                if ($status !== 'error') {
                    $status = 'corrected';
                }
                $incompleteFields[] = 'NRP/NIP';
                $statusNotes[] = 'NRP/NIP kosong';
            }

            $isDuplicateNrp = false;
            if ($nrp !== '') {
                if (isset($seenNrps[$nrp])) {
                    $isDuplicateNrp = true;
                    $status = 'error';
                    $fatalError = true;
                    $statusNotes[] = 'NRP/NIP duplikat dalam file';
                } else {
                    $seenNrps[$nrp] = true;
                }
            }

            if ($rankResult['corrected'] && $status !== 'error') {
                $status = 'corrected';
            }

            if ($status === 'corrected' && $incompleteFields !== []) {
                $statusNotes[] = 'Belum lengkap: '.implode(', ', $incompleteFields);
            }

            $preview[] = [
                'row_num' => $rowIndex + $this->startRow(),
                'sheet_name' => $sheetName,
                'full_name' => $fullName,
                'nrp' => $nrp,
                'rank_input' => $rankInput,
                'rank_id' => $rankResult['rank']?->id,
                'rank_name' => $rankResult['rank']?->name,
                'rank_corrected' => $rankResult['corrected_to'],
                'golongan' => $golongan,
                'gender' => $gender,
                'gender_raw' => $genderRaw,
                'religion' => $religion,
                'religion_raw' => $religionRaw,
                'jabatan' => $jabatan,
                'satker_id' => $resolvedSatker['satker_id'],
                'satker_name' => $resolvedSatker['satker_name'],
                'satker_match' => $resolvedSatker['matched_alias'],
                'personnel_type' => $this->resolvePersonnelTypeFromRank($rankResult['rank']),
                'status' => $status,
                'status_notes' => array_values(array_unique($statusNotes)),
                'incomplete_fields' => $incompleteFields,
                'duplicate_nrp' => $isDuplicateNrp,
                'fatal_error' => $fatalError,
                'requires_manual_satker' => $requiresManualSatker,
                'requires_manual_rank' => $requiresManualRank,
            ];
        }

        return $preview;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{success_count:int,error_count:int,errors:array<int, string>}
     */
    public function saveFromPreviewData(array $rows, int $satkerId = 0): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $errorRows = [];
        $touchedSatkers = [];

        $ranksById = Rank::all()->keyBy('id');
        $satkersById = Satker::all()->keyBy('id');
        $allNrp = collect($rows)
            ->pluck('nrp')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existingPersonnel = Personnel::whereIn('nrp', $allNrp)->get()->keyBy('nrp');
        $existingUsers = User::whereIn('nrp_nip', $allNrp)->get()->keyBy('nrp_nip');

        DB::transaction(function () use (
            $rows,
            $ranksById,
            $satkersById,
            $existingPersonnel,
            $existingUsers,
            &$successCount,
            &$errorCount,
            &$errors,
            &$touchedSatkers
        ) {
            $processedNrps = [];

            foreach ($rows as $idx => $data) {
                $fullName = trim((string) ($data['full_name'] ?? ''));
                $nrp = trim((string) ($data['nrp'] ?? ''));
                $rankId = (int) ($data['rank_id'] ?? 0);
                $satkerId = (int) ($data['satker_id'] ?? 0);
                $jabatan = trim((string) ($data['jabatan'] ?? ''));
                $gender = $data['gender'] ?? null;
                $religion = trim((string) ($data['religion'] ?? ''));
                $golongan = trim((string) ($data['golongan'] ?? ''));
                $rowReference = trim((string) ($data['sheet_name'] ?? '')) !== ''
                    ? ($data['sheet_name'].' / baris '.($data['row_num'] ?? $idx))
                    : ('baris '.($data['row_num'] ?? $idx));

                if ($fullName === '') {
                    $errorCount++;
                    $message = "Baris {$rowReference}: Nama kosong, dilewati.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                if (
                    ($data['status'] ?? 'ok') === 'error'
                    || (bool) ($data['fatal_error'] ?? false)
                    || (bool) ($data['duplicate_nrp'] ?? false)
                ) {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): Preview masih mengandung error dan harus diperbaiki lebih dulu.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                if ($nrp !== '' && isset($processedNrps[$nrp])) {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): NRP/NIP {$nrp} muncul lebih dari sekali pada data unggahan.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                $rank = $rankId > 0 ? $ranksById->get($rankId) : null;
                if ($rankId > 0 && $rank === null) {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): Pangkat tidak ditemukan.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                $satker = $satkersById->get($satkerId);
                if ($satker === null) {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): Satker tidak ditemukan.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                if (! in_array($gender, ['L', 'P'], true)) {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): Jenis kelamin tidak valid.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                if ($religion === '') {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): Agama kosong.";
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];

                    continue;
                }

                try {
                    $effectiveNrp = $nrp;
                    $isEmptyNrp = $nrp === '';
                    $isDuplicateNrp = (bool) ($data['duplicate_nrp'] ?? false);

                    if ($isEmptyNrp || $isDuplicateNrp) {
                        $effectiveNrp = 'TEMP-'.strtoupper(substr(md5($fullName.$idx.time()), 0, 8));
                    }

                    $personnel = ($isEmptyNrp || $isDuplicateNrp) ? null : $existingPersonnel->get($effectiveNrp);
                    $user = $existingUsers->get($effectiveNrp);

                    if ($personnel?->user !== null) {
                        $user = $personnel->user;
                    }

                    if ($user === null) {
                        try {
                            $user = User::create([
                                'name' => $fullName,
                                'nrp_nip' => $effectiveNrp,
                                'password' => password_hash($effectiveNrp, PASSWORD_BCRYPT, ['cost' => 4]),
                                'satker_id' => $satker->id,
                                'is_active' => true,
                            ]);
                            if (! $user->hasRole('personil')) {
                                $user->assignRole('personil');
                            }
                            $existingUsers->put($effectiveNrp, $user);
                        } catch (QueryException $exception) {
                            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                                throw $exception;
                            }

                            $user = User::query()
                                ->where('nrp_nip', $effectiveNrp)
                                ->first();

                            if ($user === null) {
                                throw $exception;
                            }

                            $user->update([
                                'name' => $fullName,
                                'satker_id' => $satker->id,
                                'is_active' => true,
                            ]);

                            if (! $user->hasRole('personil')) {
                                $user->assignRole('personil');
                            }

                            $existingUsers->put($effectiveNrp, $user);
                        }
                    } else {
                        $user->update([
                            'name' => $fullName,
                            'satker_id' => $satker->id,
                            'is_active' => true,
                        ]);

                        if (! $user->hasRole('personil')) {
                            $user->assignRole('personil');
                        }
                    }

                    $payload = [
                        'user_id' => $user->id,
                        'full_name' => $fullName,
                        'rank_id' => $rank?->id,
                        'satker_id' => $satker->id,
                        'phone' => $user->phone,
                        'jabatan' => $jabatan,
                        'bagian' => null,
                        'personnel_type' => $this->resolvePersonnelTypeFromRank($rank),
                        'gender' => $gender,
                        'golongan' => $golongan !== '' ? $golongan : $this->deriveGolongan($rank),
                        'religion' => $religion,
                        'is_active' => true,
                    ];

                    if ($personnel === null) {
                        $payload['nrp'] = $isEmptyNrp ? null : ($isDuplicateNrp ? $nrp : $effectiveNrp);
                        $payload['kapor_sizes'] = [];
                        $payload['keterangan'] = null;
                        $payload['keterangan_2'] = null;
                        $payload['keterangan_3'] = null;
                        $payload['keterangan_4'] = null;

                        $personnel = Personnel::create($payload);
                        if (! $isDuplicateNrp) {
                            $existingPersonnel->put($effectiveNrp, $personnel);
                        }
                    } else {
                        // Setelah baseline SDM masuk, field lapangan tetap menjadi ranah edit manual.
                        $payload['jabatan'] = $personnel->jabatan;
                        $payload['bagian'] = $personnel->bagian;
                        $payload['keterangan'] = $personnel->keterangan;
                        $payload['kapor_sizes'] = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];

                        $personnel->update($payload);
                    }

                    $touchedSatkers[] = $satker->id;
                    $successCount++;
                    if ($nrp !== '') {
                        $processedNrps[$nrp] = true;
                    }
                } catch (\Throwable $throwable) {
                    $errorCount++;
                    $message = "Baris {$rowReference} ({$fullName}): ".$throwable->getMessage();
                    $errors[] = $message;
                    $errorRows[] = [
                        'row_reference' => $rowReference,
                        'full_name' => $fullName,
                        'message' => $message,
                    ];
                }
            }
        });

        foreach (array_unique($touchedSatkers) as $satkerId) {
            parent::recalculateSatkerCount((int) $satkerId);
        }

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
            'error_rows' => $errorRows,
        ];
    }

    private function normalizeText(mixed $value): string
    {
        $text = str_replace('*', '', trim((string) $value));

        return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    }

    private function normalizeNrp(mixed $nrpRaw): string
    {
        $nrp = User::normalizeLoginIdentifier($nrpRaw);

        if ($nrp === '' || preg_match('/^[\-\.]+$/', $nrp) === 1) {
            return '';
        }

        if (str_starts_with($nrp, '=') || in_array(strtolower($nrp), ['jumlah', 'total'], true)) {
            return '';
        }

        return strlen($nrp) < 4 ? '' : $nrp;
    }

    private function normalizeGender(string $genderRaw): ?string
    {
        $normalized = strtoupper(trim($genderRaw));

        return match ($normalized) {
            'P', 'WANITA', 'PEREMPUAN', 'W' => 'P',
            'L', 'PRIA', 'LAKI-LAKI', 'LAKI LAKI' => 'L',
            default => null,
        };
    }

    private function normalizeReligion(string $religionRaw): ?string
    {
        $normalized = strtoupper(trim($religionRaw));

        return match ($normalized) {
            'ISLAM' => 'Islam',
            'KRISTEN', 'KRISTEN PROTESTAN', 'PROTESTAN' => 'Kristen',
            'KATOLIK', 'KATHOLIK', 'CATHOLIC' => 'Katolik',
            'HINDU' => 'Hindu',
            'BUDDHA', 'BUDHA', 'BUDHAA' => 'Buddha',
            'KONGHUCU', 'KHONGHUCU' => 'Konghucu',
            default => null,
        };
    }

    private function resolvePersonnelTypeFromRank(?Rank $rank): string
    {
        return $rank?->category === 'PNS' ? 'PNS' : 'Polri';
    }

    private function deriveGolongan(?Rank $rank): string
    {
        if ($rank === null) {
            return '';
        }

        if ($rank->category !== 'PNS') {
            return $rank->category ?? '';
        }

        return match ($rank->name) {
            'Juru Muda',
            'Juru Muda Tingkat I',
            'Juru',
            'Juru Tingkat I' => '1',
            'Pengatur Muda',
            'Pengatur Muda Tingkat I',
            'Pengatur',
            'Pengatur Tingkat I' => '2',
            'Penata Muda',
            'Penata Muda Tingkat I',
            'Penata',
            'Penata Tingkat I' => '3',
            'Pembina',
            'Pembina Tingkat I',
            'Pembina Utama Muda',
            'Pembina Utama Madya',
            'Pembina Utama' => '4',
            'PPPK' => 'PPPK',
            default => '',
        };
    }
}
