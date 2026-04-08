<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Import sinkronisasi data satker.
 *
 * Workflow ini dipakai admin satker untuk:
 * - menyesuaikan jabatan
 * - menyesuaikan bagian/fungsi
 * - mengisi keterangan (keterangan_1)
 * - menambah personel baru jika memang belum ada
 *
 * Field master seperti ukuran dan keterangan_2/3/4 sengaja diabaikan di sini.
 */
class PersonnelUpdateImport implements SkipsUnknownSheets, ToCollection, WithMultipleSheets, WithStartRow
{
    protected int $satkerId;

    public function __construct(int $satkerId)
    {
        $this->satkerId = $satkerId;
    }

    public function sheets(): array
    {
        return [0 => $this, 1 => $this, 2 => $this];
    }

    public function onUnknownSheet($sheetName): void {}

    public function startRow(): int
    {
        return 11;
    }

    public function collection(Collection $rows): void {}

    /**
     * @return array{
     *   personnel: Personnel|null,
     *   match_by: 'nrp'|'name_add_nrp'|'name'|'none',
     *   confidence: 'high'|'medium'|'none'
     * }
     */
    protected function findMatch(
        string $nrp,
        string $fullName,
        Collection $existingByNrp,
        Collection $existingByName,
    ): array {
        $notFound = ['personnel' => null, 'match_by' => 'none', 'confidence' => 'none'];

        if (! empty($nrp)) {
            $byNrp = $existingByNrp->get($nrp);
            if ($byNrp) {
                return ['personnel' => $byNrp, 'match_by' => 'nrp', 'confidence' => 'high'];
            }
        }

        if (! empty($nrp)) {
            $byName = $existingByName->get(strtolower(trim($fullName)));
            if ($byName) {
                return ['personnel' => $byName, 'match_by' => 'name_add_nrp', 'confidence' => 'high'];
            }
        }

        if (empty($nrp)) {
            $byName = $existingByName->get(strtolower(trim($fullName)));
            if ($byName) {
                return ['personnel' => $byName, 'match_by' => 'name', 'confidence' => 'high'];
            }
        }

        return $notFound;
    }

    /**
     * Parse file Excel menjadi array preview.
     */
    public function generatePreview(Collection $rows): array
    {
        $ranks = Rank::all()->keyBy(fn ($rank) => strtoupper($rank->name));

        $allNrpData = Personnel::whereNotNull('nrp')
            ->where('nrp', '!=', '')
            ->with('satker:id,name')
            ->get(['id', 'nrp', 'full_name', 'satker_id'])
            ->keyBy('nrp');

        $existingByNrp = Personnel::with('rank')
            ->where('satker_id', $this->satkerId)
            ->whereNotNull('nrp')
            ->where('nrp', '!=', '')
            ->get()
            ->keyBy('nrp');

        $existingByName = Personnel::with('rank')
            ->where('satker_id', $this->satkerId)
            ->orderByRaw('COALESCE((SELECT sort_order FROM ranks WHERE ranks.id = personnels.rank_id), 999)')
            ->get()
            ->keyBy(fn ($personnel) => strtolower(trim($personnel->full_name)));

        $processedNrp = [];
        $preview = [];

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            $fullNameRaw = str_replace('*', '', $row[1] ?? '');
            $fullName = trim(preg_replace('/\s+/', ' ', $fullNameRaw));
            $rankInputRaw = str_replace('*', '', $row[2] ?? '');
            $rankInput = trim(preg_replace('/\s+/', ' ', $rankInputRaw));
            $golongan = trim($row[3] ?? '');

            $nrpRaw = $row[4] ?? '';
            if (is_numeric($nrpRaw) && (is_float($nrpRaw) || stripos((string) $nrpRaw, 'E') !== false)) {
                $nrp = number_format((float) $nrpRaw, 0, '', '');
            } else {
                $nrp = trim((string) $nrpRaw);
            }

            $jabatan = trim($row[5] ?? '');
            $bagian = trim($row[6] ?? '');
            $genderRaw = strtoupper(trim($row[7] ?? ''));
            $religion = trim($row[8] ?? '');
            $keterangan = trim($row[9] ?? '');
            $no = trim($row[0] ?? '');

            if (empty($fullName)) {
                continue;
            }

            if (empty($rankInput) && ! is_numeric($no)) {
                continue;
            }

            $nameLower = strtolower($fullName);
            if (str_starts_with($fullName, '=') || in_array($nameLower, ['jumlah', 'total'], true)) {
                continue;
            }

            if (strlen($fullName) < 2 || is_numeric(str_replace([' ', '.', ','], '', $fullName))) {
                continue;
            }

            $gender = $genderRaw === 'W' ? 'P' : 'L';
            $rankResult = PersonnelImport::findRankWithCorrection($rankInput, $ranks, $golongan);

            $match = $this->findMatch($nrp, $fullName, $existingByNrp, $existingByName);
            $existingPersonnel = $match['personnel'];
            $matchBy = $match['match_by'];

            $isDuplicate = false;
            if (! empty($nrp) && isset($processedNrp[$nrp])) {
                $isDuplicate = true;

                $prevIdx = $processedNrp[$nrp]['preview_idx'];
                if (isset($preview[$prevIdx])) {
                    $preview[$prevIdx]['duplicate_nrp'] = true;
                }
            } elseif (! empty($nrp)) {
                $processedNrp[$nrp] = ['row_num' => $rowIndex + 11, 'preview_idx' => count($preview)];
            }

            $dbDuplicate = null;
            if (! empty($nrp) && isset($allNrpData[$nrp])) {
                $existingDatabasePersonnel = $allNrpData[$nrp];
                if (! $existingPersonnel || $existingPersonnel->id !== $existingDatabasePersonnel->id) {
                    $dbDuplicate = [
                        'personnel_id' => $existingDatabasePersonnel->id,
                        'full_name' => $existingDatabasePersonnel->full_name,
                        'satker_name' => $existingDatabasePersonnel->satker->name ?? 'Satker tidak diketahui',
                        'satker_id' => $existingDatabasePersonnel->satker_id,
                        'same_satker' => $existingDatabasePersonnel->satker_id == $this->satkerId,
                    ];
                }
            }

            $diff = [];
            if ($existingPersonnel && ! $isDuplicate) {
                $diff = $this->computeDiff($existingPersonnel, $jabatan, $bagian, $keterangan);

                if ($matchBy === 'name_add_nrp' && ! empty($nrp)) {
                    $diff = array_merge([
                        'NRP/NIP (baru)' => ['from' => '(kosong)', 'to' => $nrp],
                    ], $diff);
                }
            }

            $skipReason = null;
            $errorType = null;
            $action = $existingPersonnel ? 'update' : 'new';

            if ($isDuplicate) {
                $status = 'error';
                $errorType = 'duplicate';
                $skipReason = "NRP {$nrp} sudah muncul di baris sebelumnya dalam file ini";
            } elseif (! $rankResult['rank'] && ! empty($rankInput)) {
                $status = 'error';
                $errorType = 'unknown_rank';
                $skipReason = "Pangkat '{$rankInput}' tidak dikenali";
            } elseif ($existingPersonnel) {
                if (empty($diff)) {
                    $status = 'no_change';
                } elseif ($rankResult['corrected'] || $matchBy === 'name_add_nrp') {
                    $status = 'corrected';
                } else {
                    $status = 'update';
                }
            } else {
                $status = $rankResult['corrected'] ? 'corrected' : 'new';
            }

            $preview[] = [
                'row_num' => $rowIndex + 11,
                'action' => $action,
                'match_by' => $matchBy,
                'nrp' => $nrp,
                'personnel_id' => $existingPersonnel?->id,
                'full_name' => $fullName,
                'rank_input' => $rankInput,
                'rank_corrected' => $rankResult['corrected_to'],
                'rank_id' => $rankResult['rank']?->id,
                'rank_name' => $rankResult['rank']?->name,
                'golongan' => $golongan,
                'jabatan' => $jabatan,
                'bagian' => $bagian,
                'gender' => $gender,
                'gender_raw' => $genderRaw,
                'religion' => $religion,
                'keterangan' => $keterangan,
                'status' => $status,
                'error_type' => $errorType,
                'skip_reason' => $skipReason,
                'duplicate_nrp' => $isDuplicate,
                'db_duplicate' => $dbDuplicate,
                'existing_name' => $existingPersonnel?->full_name,
                'existing_rank_name' => $existingPersonnel?->rank?->name ?? $existingPersonnel?->golongan,
                'existing_jabatan' => $existingPersonnel?->jabatan,
                'existing_bagian' => $existingPersonnel?->bagian,
                'existing_gender' => $existingPersonnel?->gender,
                'existing_keterangan' => $existingPersonnel?->keterangan,
                'diff' => $diff,
            ];
        }

        return $preview;
    }

    protected function computeDiff(
        Personnel $existing,
        string $jabatan,
        string $bagian,
        string $keterangan,
    ): array {
        $diff = [];

        if (! empty($jabatan) && $jabatan !== $existing->jabatan) {
            $diff['Jabatan'] = ['from' => $existing->jabatan ?? '(kosong)', 'to' => $jabatan];
        }

        if (! empty($bagian) && $bagian !== $existing->bagian) {
            $diff['Bagian/Fungsi'] = ['from' => $existing->bagian ?? '(kosong)', 'to' => $bagian];
        }

        if ($keterangan !== '' && $keterangan !== $existing->keterangan) {
            $diff['Keterangan'] = ['from' => $existing->keterangan ?? '(kosong)', 'to' => $keterangan];
        }

        return $diff;
    }

    /**
     * @return array{success_count:int, new_count:int, no_change_count:int, error_count:int, errors:array}
     */
    public function saveUpdateFromPreview(array $rows): array
    {
        $successCount = 0;
        $newCount = 0;
        $noChangeCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $data) {
                if (($data['status'] ?? null) === 'no_change') {
                    $noChangeCount++;

                    continue;
                }

                if (($data['status'] ?? null) === 'error' && empty($data['rank_id'])) {
                    $errorCount++;
                    $errors[] = "Baris {$data['row_num']} ({$data['full_name']}): pangkat tidak dikenali.";

                    continue;
                }

                try {
                    if (($data['action'] ?? null) === 'update' && ! empty($data['personnel_id'])) {
                        $personnel = Personnel::find($data['personnel_id']);
                        if (! $personnel) {
                            $errorCount++;
                            $errors[] = "Baris {$data['row_num']}: Personel ID {$data['personnel_id']} tidak ditemukan.";

                            continue;
                        }

                        $updateData = [];
                        if (! empty($data['jabatan'])) {
                            $updateData['jabatan'] = $data['jabatan'];
                        }
                        if (! empty($data['bagian'])) {
                            $updateData['bagian'] = $data['bagian'];
                        }
                        if (($data['keterangan'] ?? '') !== '') {
                            $updateData['keterangan'] = $data['keterangan'];
                        }

                        if (($data['match_by'] ?? '') === 'name_add_nrp' && ! empty($data['nrp'])) {
                            $updateData['nrp'] = $data['nrp'];

                            if (! $personnel->user) {
                                $user = User::create([
                                    'name' => $personnel->full_name,
                                    'nrp_nip' => $data['nrp'],
                                    'email' => $data['nrp'].'@polda-ntb.local',
                                    'password' => Hash::make($data['nrp']),
                                    'satker_id' => $personnel->satker_id,
                                    'is_active' => true,
                                ]);
                                $user->assignRole('personil');
                                $updateData['user_id'] = $user->id;
                            } else {
                                $personnel->user->update([
                                    'nrp_nip' => $data['nrp'],
                                ]);
                            }
                        }

                        $hasNrpIssue = ! empty($data['db_duplicate']) || ! empty($data['duplicate_nrp']);
                        if ($hasNrpIssue) {
                            $updateData['nrp_issue_note'] = $this->buildNrpIssueNote($data);
                        } else {
                            $updateData['nrp_issue_note'] = null;
                            $updateData['nrp_issue_resolved_at'] = null;
                        }

                        $personnel->update($updateData);
                        $successCount++;

                        continue;
                    }

                    $nrp = $data['nrp'] ?: null;
                    $password = $nrp ?? ('kapor'.date('Y'));
                    $rank = ! empty($data['rank_id']) ? Rank::find($data['rank_id']) : null;

                    $user = null;
                    if ($nrp) {
                        $user = User::firstOrCreate(
                            ['nrp_nip' => $nrp],
                            [
                                'name' => $data['full_name'],
                                'email' => $nrp.'@polda-ntb.local',
                                'password' => Hash::make($password),
                                'satker_id' => $this->satkerId,
                                'is_active' => true,
                            ]
                        );

                        if (! $user->hasRole('personil')) {
                            $user->assignRole('personil');
                        }
                    }

                    Personnel::create([
                        'user_id' => $user?->id,
                        'satker_id' => $this->satkerId,
                        'rank_id' => $data['rank_id'] ?? null,
                        'full_name' => $data['full_name'],
                        'nrp' => $nrp,
                        'jabatan' => $data['jabatan'] ?: null,
                        'bagian' => $data['bagian'] ?: null,
                        'golongan' => $data['golongan'] ?: null,
                        'gender' => $data['gender'] ?: 'L',
                        'religion' => $data['religion'] ?: null,
                        'personnel_type' => $rank?->category === 'PNS' ? 'PNS' : 'Polri',
                        'keterangan' => $data['keterangan'] ?: null,
                        'is_active' => true,
                        'verification_status' => 'approved',
                        'nrp_issue_note' => $this->buildNrpIssueNote($data),
                    ]);

                    $newCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Baris {$data['row_num']} ({$data['full_name']}): ".$e->getMessage();
                }
            }

            DB::commit();
            PersonnelImport::recalculateSatkerCount($this->satkerId);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'success_count' => $successCount,
            'new_count' => $newCount,
            'no_change_count' => $noChangeCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    private function buildNrpIssueNote(array $data): ?string
    {
        $notes = [];

        if (! empty($data['db_duplicate'])) {
            $db = $data['db_duplicate'];
            $location = $db['same_satker'] ? 'satker ini' : $db['satker_name'];
            $notes[] = "NRP duplikat dengan {$db['full_name']} di {$location}";
        }

        if (! empty($data['duplicate_nrp'])) {
            $notes[] = 'NRP duplikat dalam file import';
        }

        return $notes !== [] ? implode('; ', $notes) : null;
    }
}
