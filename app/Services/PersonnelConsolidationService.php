<?php

namespace App\Services;

use App\Imports\PersonnelImport;
use App\Models\Personnel;
use App\Models\PersonnelTransferRequest;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class PersonnelConsolidationService
{
    private const SIZE_KEYS = [
        'topi',
        'kemeja',
        'celana',
        'olahraga',
        'sepatu_dinas',
        'sepatu_olahraga',
        'jaket',
        'sabuk',
        'jilbab',
    ];

    public function ensureSyncTokens(int $satkerId): void
    {
        Personnel::query()
            ->where('satker_id', $satkerId)
            ->whereNull('sync_token')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function (Collection $personnels): void {
                foreach ($personnels as $personnel) {
                    $personnel->updateQuietly(['sync_token' => (string) Str::uuid()]);
                }
            });
    }

    public function buildPreview(string $path, Satker $targetSatker, string $sourceFile): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($path);
        $parsedRows = [];
        $fileWarnings = [];
        $unreadableSheets = [];
        $ranks = Rank::all()->keyBy(fn (Rank $rank) => Str::upper($rank->name));

        try {
            foreach ($spreadsheet->getWorksheetIterator() as $sheetIndex => $sheet) {
                if (Str::lower($sheet->getTitle()) === 'petunjuk') {
                    continue;
                }

                // getHighestDataRow() scans the complete cell index, so calculate it once per sheet.
                $highestDataRow = $sheet->getHighestDataRow();
                $header = $this->detectHeader($sheet, $highestDataRow);
                if ($header === null) {
                    if ($this->isExpectedPersonnelSheet($sheet, $highestDataRow)) {
                        $unreadableSheets[] = $sheet->getTitle();
                    }

                    continue;
                }
                foreach ($header['warnings'] as $warning) {
                    $fileWarnings[] = "Sheet {$sheet->getTitle()}: {$warning}";
                }

                for ($rowNumber = $header['row'] + 1; $rowNumber <= $highestDataRow; $rowNumber++) {
                    $row = $this->parseRow($sheet, $sheetIndex, $rowNumber, $header, $targetSatker, $ranks);

                    if ($row !== null) {
                        $parsedRows[] = $row;
                    }
                }
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        if ($unreadableSheets !== []) {
            throw new RuntimeException(
                'Sheet '.implode(', ', $unreadableSheets)
                .' tidak dapat dibaca. Pastikan judul kolom pada baris 8 tidak dihapus atau dipindahkan. '
                .'Tidak ada data yang diproses agar personel tidak keliru dianggap hilang.'
            );
        }

        if ($parsedRows === []) {
            throw new RuntimeException('Tidak ada baris personel yang dapat dibaca. Pastikan judul NAMA dan NRP/NIP tidak dihapus.');
        }

        $previewRows = $this->matchRows($parsedRows, $targetSatker);

        return $this->refreshPreviewSummary([
            'version' => 1,
            'satker_id' => $targetSatker->id,
            'satker_name' => $targetSatker->name,
            'source_file' => $sourceFile,
            'created_at' => now()->toIso8601String(),
            'warnings' => array_values(array_unique($fileWarnings)),
        ], $previewRows, $targetSatker);
    }

    public function fixPreviewRow(array $preview, array $input): array
    {
        $targetSatker = Satker::query()->findOrFail((int) $preview['satker_id']);
        $selectedRank = Rank::query()->findOrFail((int) $input['rank_id']);
        $ranks = Rank::query()->get(['id', 'name', 'category'])->keyBy('id');
        $targetFound = false;

        $parsedRows = collect($preview['rows'] ?? [])
            ->map(function (array $previewRow, int $index) use ($input, $selectedRank, $ranks, &$targetFound): array {
                $data = $previewRow['data'] ?? [];
                $isTarget = $previewRow['sheet'] === $input['sheet']
                    && (int) $previewRow['row_number'] === (int) $input['row_number'];

                if ($isTarget) {
                    $targetFound = true;
                    $data = array_merge($data, [
                        'full_name' => trim((string) $input['full_name']),
                        'nrp' => User::normalizeLoginIdentifier($input['nrp']),
                        'rank_id' => $selectedRank->id,
                        'rank_name' => $selectedRank->name,
                        'golongan' => trim((string) ($input['golongan'] ?? '')),
                        'jabatan' => trim((string) ($input['jabatan'] ?? '')),
                        'bagian' => trim((string) ($input['bagian'] ?? '')),
                        'gender' => $input['gender'],
                        'religion' => trim((string) ($input['religion'] ?? '')),
                        'keterangan' => trim((string) ($input['keterangan'] ?? '')),
                    ]);
                }

                $rank = $ranks->get((int) ($data['rank_id'] ?? 0));
                $fullName = trim((string) ($data['full_name'] ?? $previewRow['full_name'] ?? ''));
                $nrp = User::normalizeLoginIdentifier($data['nrp'] ?? $previewRow['nrp'] ?? '');
                $gender = in_array(($data['gender'] ?? null), ['L', 'P'], true) ? $data['gender'] : '';
                $errors = [];

                if ($fullName === '') {
                    $errors[] = 'Nama wajib diisi.';
                }
                if ($nrp === '') {
                    $errors[] = 'NRP/NIP wajib diisi.';
                } elseif (strlen($nrp) < 4) {
                    $errors[] = 'NRP/NIP terlalu pendek.';
                }
                if ($gender === '') {
                    $errors[] = 'Jenis kelamin harus diisi P (pria) atau W (wanita).';
                }
                if ($rank === null) {
                    $errors[] = 'Pangkat wajib dipilih dari referensi aplikasi.';
                }
                if (trim((string) ($data['keterangan'] ?? '')) === '') {
                    $errors[] = 'Keterangan wajib diisi.';
                }

                $sizes = array_merge(
                    array_fill_keys(self::SIZE_KEYS, ''),
                    is_array($data['sizes'] ?? null) ? $data['sizes'] : [],
                );

                return [
                    '_index' => $index.'_'.sha1($previewRow['sheet'].'|'.$previewRow['row_number']),
                    'sheet' => $previewRow['sheet'],
                    'row_number' => (int) $previewRow['row_number'],
                    'full_name' => $fullName,
                    'nrp' => $nrp,
                    'rank_id' => $rank?->id,
                    'rank_name' => $rank?->name ?? trim((string) ($data['rank_name'] ?? $previewRow['rank_name'] ?? '')),
                    'golongan' => trim((string) ($data['golongan'] ?? $previewRow['golongan'] ?? '')),
                    'jabatan' => trim((string) ($data['jabatan'] ?? $previewRow['jabatan'] ?? '')),
                    'bagian' => trim((string) ($data['bagian'] ?? $previewRow['bagian'] ?? '')),
                    'gender' => $gender,
                    'religion' => trim((string) ($data['religion'] ?? '')),
                    'keterangan' => trim((string) ($data['keterangan'] ?? '')),
                    'personnel_type' => PersonnelImport::resolvePersonnelType(
                        $rank,
                        trim((string) ($data['golongan'] ?? $previewRow['golongan'] ?? '')),
                    ),
                    'sizes' => $sizes,
                    'has_sizes' => (bool) ($data['has_sizes'] ?? false),
                    'system_code' => trim((string) ($data['system_code'] ?? '')),
                    'errors' => $errors,
                ];
            })
            ->values()
            ->all();

        if (! $targetFound) {
            throw new RuntimeException('Baris yang akan diperbaiki tidak ditemukan dalam pratinjau.');
        }

        return $this->refreshPreviewSummary(
            $preview,
            $this->matchRows($parsedRows, $targetSatker),
            $targetSatker,
        );
    }

    private function refreshPreviewSummary(array $preview, array $previewRows, Satker $targetSatker): array
    {
        $matchedIds = collect($previewRows)
            ->pluck('matched_personnel_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        $missingRows = Personnel::query()
            ->with(['rank:id,name', 'user:id,is_active'])
            ->where('satker_id', $targetSatker->id)
            ->where('is_active', true)
            ->whereNotIn('id', $matchedIds)
            ->orderBy('bagian')
            ->orderBy('full_name')
            ->get()
            ->map(fn (Personnel $personnel): array => [
                'personnel_id' => $personnel->id,
                'full_name' => $personnel->full_name,
                'nrp' => $personnel->nrp,
                'rank_name' => $personnel->rank?->name,
                'jabatan' => $personnel->jabatan,
                'bagian' => $personnel->bagian,
                'status' => 'missing',
            ])
            ->values()
            ->all();

        $stats = collect($previewRows)->countBy('status')->all();
        $stats = array_merge([
            'total' => count($previewRows),
            'update' => 0,
            'new' => 0,
            'no_change' => 0,
            'transfer' => 0,
            'duplicate_ignored' => 0,
            'error' => 0,
            'missing' => count($missingRows),
        ], $stats);
        $stats['actionable'] = $stats['update'] + $stats['new'] + $stats['transfer'];

        $preview['rows'] = $previewRows;
        $preview['missing_rows'] = $missingRows;
        $preview['stats'] = $stats;

        return $preview;
    }

    public function applyPreview(array $preview, array $deactivateIds, User $actor): array
    {
        $errorCount = collect($preview['rows'] ?? [])
            ->where('status', 'error')
            ->count();
        if ($errorCount > 0) {
            throw new RuntimeException(
                "{$errorCount} baris masih perlu diperbaiki. Tidak ada data yang dapat disimpan sebelum seluruh kesalahan diselesaikan."
            );
        }

        $satkerId = (int) $preview['satker_id'];
        $results = [
            'updated' => 0,
            'created' => 0,
            'unchanged' => 0,
            'transfer_approved' => 0,
            'deactivated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($preview['rows'] as $row) {
            try {
                match ($row['status']) {
                    'update' => DB::transaction(function () use ($row, $satkerId, &$results): void {
                        $personnel = Personnel::query()
                            ->whereKey($row['matched_personnel_id'])
                            ->where('satker_id', $satkerId)
                            ->lockForUpdate()
                            ->firstOrFail();
                        $this->applyPayload($personnel, $row['data'], $satkerId);
                        $results['updated']++;
                    }),
                    'new' => DB::transaction(function () use ($row, $satkerId, &$results): void {
                        $this->createPersonnel($row['data'], $satkerId);
                        $results['created']++;
                    }),
                    'transfer' => DB::transaction(function () use ($row, $satkerId, $actor, $preview, &$results): void {
                        $personnel = Personnel::query()
                            ->whereKey($row['matched_personnel_id'])
                            ->lockForUpdate()
                            ->firstOrFail();

                        PersonnelTransferRequest::query()
                            ->where('personnel_id', $personnel->id)
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'superseded',
                                'review_note' => 'Ditutup otomatis karena mutasi baru telah diproses.',
                                'reviewed_at' => now(),
                            ]);
                        $transfer = PersonnelTransferRequest::create([
                            'personnel_id' => $personnel->id,
                            'from_satker_id' => $personnel->satker_id,
                            'to_satker_id' => $satkerId,
                            'requested_by' => $actor->id,
                            'source_file' => $preview['source_file'],
                            'source_sheet' => $row['sheet'],
                            'source_row' => $row['row_number'],
                            'payload' => $row['data'],
                            'status' => 'approved',
                            'review_note' => 'Disetujui otomatis berdasarkan pembaruan data admin satker.',
                            'reviewed_at' => now(),
                        ]);
                        $this->applyPayload($personnel, $row['data'], $satkerId);
                        PersonnelImport::recalculateSatkerCount($transfer->from_satker_id);
                        PersonnelImport::recalculateSatkerCount($satkerId);
                        $results['transfer_approved']++;
                    }),
                    'no_change' => $results['unchanged']++,
                    default => $results['skipped']++,
                };
            } catch (\Throwable $exception) {
                $results['errors'][] = "Baris {$row['row_number']} ({$row['full_name']}): {$exception->getMessage()}";
            }
        }

        $allowedMissingIds = collect($preview['missing_rows'])
            ->pluck('personnel_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $selectedIds = collect($deactivateIds)
            ->map(fn ($id) => (int) $id)
            ->intersect($allowedMissingIds)
            ->unique()
            ->values();

        foreach ($selectedIds as $personnelId) {
            try {
                DB::transaction(function () use ($personnelId, $satkerId, &$results): void {
                    $personnel = Personnel::query()
                        ->with('user')
                        ->whereKey($personnelId)
                        ->where('satker_id', $satkerId)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $personnel) {
                        return;
                    }

                    $personnel->update(['is_active' => false]);
                    $personnel->user?->update(['is_active' => false]);
                    $results['deactivated']++;
                });
            } catch (\Throwable $exception) {
                $results['errors'][] = "Personel ID {$personnelId} tidak dapat dinonaktifkan: {$exception->getMessage()}";
            }
        }

        PersonnelImport::recalculateSatkerCount($satkerId);

        return $results;
    }

    public function applyPayload(Personnel $personnel, array $data, int $satkerId): Personnel
    {
        $nrp = User::normalizeLoginIdentifier($data['nrp'] ?? '');
        if ($nrp === '') {
            throw new RuntimeException('NRP/NIP wajib diisi.');
        }

        $personnelConflict = Personnel::query()
            ->where('nrp', $nrp)
            ->whereKeyNot($personnel->id)
            ->exists();
        if ($personnelConflict) {
            throw new RuntimeException("NRP/NIP {$nrp} sudah digunakan personel lain.");
        }

        $user = $personnel->user;
        $conflictingUser = User::query()
            ->where('nrp_nip', $nrp)
            ->when($user, fn ($query) => $query->whereKeyNot($user->id))
            ->first();
        if ($conflictingUser) {
            throw new RuntimeException("Akun login NRP/NIP {$nrp} sudah digunakan.");
        }

        $user = User::createOrUpdatePersonnelImportAccount(
            $user,
            $nrp,
            $data['full_name'],
            $satkerId,
            true,
        );

        $attributes = [
            'user_id' => $user->id,
            'satker_id' => $satkerId,
            'rank_id' => $data['rank_id'],
            'nrp' => $nrp,
            'full_name' => $data['full_name'],
            'golongan' => $data['golongan'] ?: null,
            'jabatan' => $data['jabatan'] ?: null,
            'bagian' => $data['bagian'] ?: null,
            'gender' => $data['gender'],
            'religion' => $data['religion'] ?: null,
            'keterangan' => $data['keterangan'] ?: null,
            'personnel_type' => $data['personnel_type'],
            'is_active' => true,
            'verification_status' => 'approved',
        ];

        if ($data['has_sizes']) {
            $attributes['kapor_sizes'] = $data['sizes'];
        }

        $personnel->update($attributes);

        return $personnel->refresh();
    }

    private function createPersonnel(array $data, int $satkerId): Personnel
    {
        $nrp = User::normalizeLoginIdentifier($data['nrp'] ?? '');
        if ($nrp === '') {
            throw new RuntimeException('NRP/NIP wajib diisi.');
        }

        if (Personnel::query()->where('nrp', $nrp)->exists()) {
            throw new RuntimeException("NRP/NIP {$nrp} sudah digunakan personel lain.");
        }

        $user = User::query()->where('nrp_nip', $nrp)->first();
        if ($user && Personnel::query()->where('user_id', $user->id)->exists()) {
            throw new RuntimeException("Akun login NRP/NIP {$nrp} sudah terhubung ke personel lain.");
        }
        if ($user && $user->satker_id !== null && (int) $user->satker_id !== $satkerId) {
            throw new RuntimeException("Akun login NRP/NIP {$nrp} masih tercatat pada satker lain.");
        }

        $user = User::createOrUpdatePersonnelImportAccount(
            $user,
            $nrp,
            $data['full_name'],
            $satkerId,
            true,
        );

        return Personnel::create([
            'user_id' => $user->id,
            'satker_id' => $satkerId,
            'rank_id' => $data['rank_id'],
            'nrp' => $nrp,
            'full_name' => $data['full_name'],
            'golongan' => $data['golongan'] ?: null,
            'jabatan' => $data['jabatan'] ?: null,
            'bagian' => $data['bagian'] ?: null,
            'gender' => $data['gender'],
            'religion' => $data['religion'] ?: null,
            'keterangan' => $data['keterangan'] ?: null,
            'personnel_type' => $data['personnel_type'],
            'kapor_sizes' => $data['has_sizes'] ? $data['sizes'] : [],
            'is_active' => true,
            'verification_status' => 'approved',
        ]);
    }

    private function matchRows(array $parsedRows, Satker $targetSatker): array
    {
        $personnels = Personnel::query()
            ->with(['rank:id,name,category', 'satker:id,name', 'user:id,nrp_nip,satker_id'])
            ->get();
        $byToken = $personnels
            ->filter(fn (Personnel $personnel) => filled($personnel->sync_token))
            ->keyBy(fn (Personnel $personnel) => Str::lower($personnel->sync_token));
        $byNrp = $personnels
            ->filter(fn (Personnel $personnel) => filled($personnel->nrp))
            ->groupBy(fn (Personnel $personnel) => User::normalizeLoginIdentifier($personnel->nrp));
        $byNameInTarget = $personnels
            ->where('satker_id', $targetSatker->id)
            ->groupBy(fn (Personnel $personnel) => $this->normalizeName($personnel->full_name));
        $usersByNrp = User::query()
            ->whereNotNull('nrp_nip')
            ->get(['id', 'nrp_nip', 'satker_id'])
            ->keyBy(fn (User $user) => User::normalizeLoginIdentifier($user->nrp_nip));

        $duplicateNrpGroups = collect($parsedRows)
            ->filter(fn (array $row) => $row['nrp'] !== '')
            ->groupBy('nrp')
            ->filter(fn (Collection $rows) => $rows->count() > 1);
        $duplicateCodeGroups = collect($parsedRows)
            ->filter(fn (array $row) => $row['system_code'] !== '')
            ->groupBy(fn (array $row) => Str::lower($row['system_code']))
            ->filter(fn (Collection $rows) => $rows->pluck('nrp')->unique()->count() > 1);

        $ignoredDuplicateIndexes = [];
        $conflictingDuplicateIndexes = [];
        foreach ($duplicateNrpGroups as $rows) {
            $signatures = $rows->map(fn (array $row) => $this->rowSignature($row))->unique();
            if ($signatures->count() === 1) {
                $rows->slice(1)->each(function (array $row) use (&$ignoredDuplicateIndexes): void {
                    $ignoredDuplicateIndexes[$row['_index']] = true;
                });
            } else {
                $rows->each(function (array $row) use (&$conflictingDuplicateIndexes): void {
                    $conflictingDuplicateIndexes[$row['_index']] = true;
                });
            }
        }
        foreach ($duplicateCodeGroups as $rows) {
            $rows->each(function (array $row) use (&$conflictingDuplicateIndexes): void {
                $conflictingDuplicateIndexes[$row['_index']] = true;
            });
        }

        $preview = [];
        foreach ($parsedRows as $row) {
            $errors = $row['errors'];
            $warnings = [];
            $matched = null;
            $matchMethod = null;

            if (isset($ignoredDuplicateIndexes[$row['_index']])) {
                $preview[] = $this->previewRow($row, 'duplicate_ignored', null, 'nrp', [], [
                    'Baris sama persis sudah ditemukan sebelumnya dan akan diabaikan otomatis.',
                ]);

                continue;
            }

            if (isset($conflictingDuplicateIndexes[$row['_index']])) {
                $errors[] = 'NRP/NIP atau KODE DATA muncul lebih dari sekali dengan isi yang berbeda.';
            }

            if ($row['system_code'] !== '') {
                $matched = $byToken->get(Str::lower($row['system_code']));
                if ($matched) {
                    $matchMethod = 'system_code';
                } else {
                    $warnings[] = 'KODE DATA tidak dikenali. Sistem mencoba mencocokkan melalui NRP/NIP.';
                }
            }

            $nrpMatches = $row['nrp'] !== '' ? $byNrp->get($row['nrp'], collect()) : collect();
            if (! $matched && $nrpMatches->count() === 1) {
                $matched = $nrpMatches->first();
                $matchMethod = 'nrp';
            } elseif (! $matched && $nrpMatches->count() > 1) {
                $errors[] = 'NRP/NIP sudah memiliki lebih dari satu data personel di database. Perlu dibersihkan oleh superadmin.';
            }

            if ($matched && $nrpMatches->isNotEmpty() && ! $nrpMatches->contains('id', $matched->id)) {
                $errors[] = 'KODE DATA dan NRP/NIP menunjuk ke personel yang berbeda.';
            }

            if (! $matched && $row['nrp'] !== '') {
                $sameName = $byNameInTarget->get($this->normalizeName($row['full_name']), collect());
                if ($sameName->count() === 1 && $sameName->first()->nrp !== $row['nrp']) {
                    $matched = $sameName->first();
                    $matchMethod = 'name_conflict';
                    $errors[] = 'Nama sudah ada pada satker ini tetapi NRP/NIP berbeda. Gunakan file terbaru yang memiliki KODE DATA atau periksa NRP/NIP.';
                }
            }

            if ($errors !== []) {
                $preview[] = $this->previewRow($row, 'error', $matched, $matchMethod, $errors, $warnings);

                continue;
            }

            if ($matched) {
                if ((int) $matched->satker_id !== (int) $targetSatker->id) {
                    $warnings[] = "NRP/NIP saat ini tercatat pada {$matched->satker?->name}. Data akan dikirim sebagai permintaan mutasi.";
                    $preview[] = $this->previewRow($row, 'transfer', $matched, $matchMethod, [], $warnings);

                    continue;
                }

                $diff = $this->buildDiff($matched, $row);
                $status = $diff === [] ? 'no_change' : 'update';
                $item = $this->previewRow($row, $status, $matched, $matchMethod, [], $warnings);
                $item['diff'] = $diff;
                $preview[] = $item;

                continue;
            }

            $existingUser = $usersByNrp->get($row['nrp']);
            if ($existingUser && $existingUser->satker_id !== null && (int) $existingUser->satker_id !== (int) $targetSatker->id) {
                $errors[] = 'NRP/NIP sudah menjadi akun login pada satker lain, tetapi data personelnya tidak ditemukan.';
                $preview[] = $this->previewRow($row, 'error', null, 'account', $errors, $warnings);

                continue;
            }

            $preview[] = $this->previewRow($row, 'new', null, $existingUser ? 'inactive_account' : 'new', [], $warnings);
        }

        return $preview;
    }

    private function previewRow(
        array $row,
        string $status,
        ?Personnel $matched,
        ?string $matchMethod,
        array $errors,
        array $warnings,
    ): array {
        return [
            'sheet' => $row['sheet'],
            'row_number' => $row['row_number'],
            'status' => $status,
            'match_method' => $matchMethod,
            'matched_personnel_id' => $matched?->id,
            'from_satker_name' => $matched?->satker?->name,
            'full_name' => $row['full_name'],
            'nrp' => $row['nrp'],
            'rank_name' => $row['rank_name'],
            'golongan' => $row['golongan'],
            'jabatan' => $row['jabatan'],
            'bagian' => $row['bagian'],
            'personnel_type' => $row['personnel_type'],
            'gender_label' => $row['gender'] === 'P' ? 'Wanita' : 'Pria',
            'system_code_present' => $row['system_code'] !== '',
            'errors' => $errors,
            'warnings' => $warnings,
            'diff' => [],
            'data' => $this->payloadFromRow($row),
        ];
    }

    private function payloadFromRow(array $row): array
    {
        return collect($row)->only([
            'full_name',
            'nrp',
            'rank_id',
            'rank_name',
            'golongan',
            'jabatan',
            'bagian',
            'gender',
            'religion',
            'keterangan',
            'personnel_type',
            'sizes',
            'has_sizes',
            'system_code',
        ])->all();
    }

    private function buildDiff(Personnel $personnel, array $row): array
    {
        $fields = [
            'Nama' => [$personnel->full_name, $row['full_name']],
            'NRP/NIP' => [User::normalizeLoginIdentifier($personnel->nrp), $row['nrp']],
            'Pangkat' => [$personnel->rank?->name, $row['rank_name']],
            'Golongan' => [$personnel->golongan, $row['golongan']],
            'Jabatan' => [$personnel->jabatan, $row['jabatan']],
            'Bag/Fungsi' => [$personnel->bagian, $row['bagian']],
            'Jenis Kelamin' => [$personnel->gender, $row['gender']],
            'Agama' => [$personnel->religion, $row['religion']],
            'Keterangan' => [$personnel->keterangan, $row['keterangan']],
        ];
        $diff = [];

        foreach ($fields as $label => [$from, $to]) {
            if ($this->comparable($from) !== $this->comparable($to)) {
                $diff[$label] = ['from' => $from ?: '(kosong)', 'to' => $to ?: '(kosong)'];
            }
        }

        if ($row['has_sizes']) {
            $existingSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];
            foreach (self::SIZE_KEYS as $key) {
                $from = trim((string) ($existingSizes[$key] ?? ''));
                $to = trim((string) ($row['sizes'][$key] ?? ''));
                if ($from !== $to) {
                    $diff['Ukuran '.$key] = ['from' => $from ?: '(kosong)', 'to' => $to ?: '(kosong)'];
                }
            }
        }

        return $diff;
    }

    private function detectHeader($sheet, int $highestDataRow): ?array
    {
        $highestColumnIndex = min(
            30,
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestColumn())
        );

        for ($row = 1; $row <= min(15, $highestDataRow); $row++) {
            $values = [];
            for ($column = 1; $column <= $highestColumnIndex; $column++) {
                $values[$column] = Str::upper(trim($this->cellValue($sheet->getCell([$column, $row]))));
            }

            $hasName = collect($values)->contains(fn (string $value) => $value === 'NAMA' || $value === 'NAMA LENGKAP');
            $hasNrp = collect($values)->contains(fn (string $value) => str_contains($value, 'NRP'));
            $matchesFixedTemplate = ($values[3] ?? '') === 'PANGKAT'
                && str_contains($values[5] ?? '', 'NRP')
                && ($values[6] ?? '') === 'JABATAN'
                && (str_contains($values[7] ?? '', 'BAG') || str_contains($values[7] ?? '', 'FUNGSI'))
                && str_contains($values[8] ?? '', 'JENIS KELAMIN');
            if (! $hasNrp || (! $hasName && ! $matchesFixedTemplate)) {
                continue;
            }

            $hasSystemCode = collect($values)->contains(fn (string $value) => str_contains($value, 'KODE DATA'));
            $systemCodeColumn = collect($values)
                ->search(fn (string $value) => str_contains($value, 'KODE DATA'));

            return [
                'row' => $row,
                'layout' => $hasSystemCode
                    ? (((int) $systemCodeColumn <= 11) ? 'consolidation_simple' : 'consolidation')
                    : ($highestColumnIndex >= 18 ? 'monitoring' : 'legacy'),
                'warnings' => $hasName
                    ? []
                    : ["judul kolom NAMA berubah menjadi '{$values[2]}'. Sistem tetap membaca kolom B sebagai NAMA."],
            ];
        }

        return null;
    }

    private function isExpectedPersonnelSheet($sheet, int $highestDataRow): bool
    {
        $title = Str::lower($sheet->getTitle());

        return $highestDataRow >= 8
            && Str::contains($title, ['polri', 'pns', 'personel']);
    }

    private function parseRow(
        $sheet,
        int $sheetIndex,
        int $rowNumber,
        array $header,
        Satker $targetSatker,
        Collection &$ranks,
    ): ?array {
        $value = fn (int $column): string => trim($this->cellValue($sheet->getCell([$column, $rowNumber])));
        $name = $this->cleanText($value(2));
        $nrpCellRawValue = $sheet->getCell([5, $rowNumber])->getValue();
        $nrpRaw = $value(5);
        $nrp = User::normalizeLoginIdentifier($nrpRaw);
        $nrpWasUnsafeNumeric = (is_int($nrpCellRawValue) || is_float($nrpCellRawValue))
            && abs((float) $nrpCellRawValue) >= 1_000_000_000_000_000;

        if ($name === '' && $nrp === '') {
            return null;
        }
        if (in_array(Str::lower($name), ['jumlah', 'total'], true)) {
            return null;
        }

        $layout = $header['layout'];
        $hasSizes = in_array($layout, ['consolidation', 'monitoring'], true);
        $genderRaw = Str::upper($value(8));
        $gender = match ($genderRaw) {
            'W', 'WANITA', 'PEREMPUAN' => 'P',
            'P', 'L', 'LAKI-LAKI', 'PRIA' => 'L',
            default => '',
        };

        $sizes = array_fill_keys(self::SIZE_KEYS, '');
        if ($hasSizes) {
            foreach (array_values(self::SIZE_KEYS) as $offset => $key) {
                $sizes[$key] = $value(10 + $offset);
            }
        }

        $religion = $layout === 'monitoring' ? '' : $value(9);
        $keterangan = match ($layout) {
            'consolidation' => $value(19),
            'monitoring' => $value(18),
            default => $value(10),
        };
        $systemCode = match ($layout) {
            'consolidation' => Str::lower($value(20)),
            'consolidation_simple' => Str::lower($value(11)),
            default => '',
        };

        $errors = [];
        if ($name === '') {
            $errors[] = 'Nama wajib diisi.';
        }
        if ($nrp === '') {
            $errors[] = 'NRP/NIP wajib diisi.';
        } elseif ($nrpWasUnsafeNumeric || preg_match('/[Ee][+-]?\d+/', $nrpRaw) === 1) {
            $errors[] = 'NRP/NIP terbaca sebagai angka ilmiah Excel. Ubah kolom NRP/NIP menjadi format Teks lalu isi ulang nilainya.';
        } elseif (strlen($nrp) < 4) {
            $errors[] = 'NRP/NIP terlalu pendek.';
        }
        if ($gender === '') {
            $errors[] = 'Jenis kelamin harus diisi P (pria) atau W (wanita).';
        }

        $rankInput = $this->cleanText($value(3));
        $golongan = $this->cleanText($value(4));
        $rank = null;
        $rankName = $rankInput;
        if ($rankInput === '') {
            $errors[] = 'Pangkat wajib diisi.';
        } else {
            $rankResult = PersonnelImport::findRankWithCorrection($rankInput, $ranks, $golongan, $targetSatker->code);
            $rank = $rankResult['rank'];
            $rankName = $rank?->name ?? $rankInput;
            if (! $rank) {
                $errors[] = "Pangkat '{$rankInput}' tidak ditemukan pada referensi aplikasi.";
            }
        }
        if ($keterangan === '') {
            $errors[] = 'Keterangan wajib diisi.';
        }

        return [
            '_index' => $sheetIndex.'_'.$rowNumber,
            'sheet' => $sheet->getTitle(),
            'row_number' => $rowNumber,
            'full_name' => $name,
            'nrp' => $nrp,
            'rank_id' => $rank?->id,
            'rank_name' => $rankName,
            'golongan' => $golongan,
            'jabatan' => $this->cleanText($value(6)),
            'bagian' => $this->cleanText($value(7)),
            'gender' => $gender,
            'religion' => $this->cleanText($religion),
            'keterangan' => $this->cleanText($keterangan),
            'personnel_type' => PersonnelImport::resolvePersonnelType($rank, $golongan),
            'sizes' => $sizes,
            'has_sizes' => $hasSizes,
            'system_code' => $systemCode,
            'errors' => $errors,
        ];
    }

    private function rowSignature(array $row): string
    {
        return hash('sha256', json_encode($this->payloadFromRow($row), JSON_UNESCAPED_UNICODE));
    }

    private function cellValue(Cell $cell): string
    {
        $value = $cell->getValue();
        if ($cell->isFormula()) {
            $cachedValue = $cell->getOldCalculatedValue();
            $value = $cachedValue ?? $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
        }

        return trim((string) $value);
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace('*', '', (string) $value)));
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii($value))) ?? '';
    }

    private function comparable(mixed $value): string
    {
        return Str::upper(trim((string) $value));
    }
}
