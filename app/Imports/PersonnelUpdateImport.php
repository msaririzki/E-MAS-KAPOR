<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Import Sinkronisasi Personel — Multi-Strategy Matching.
 *
 * Strategi Pencocokan Berlapis:
 *  1. NRP/NIP cocok di DB                              → UPDATE (via nrp)
 *  2. NRP di Excel ada, tidak di DB + Nama PERSIS sama → UPDATE + simpan NRP baru (via name_add_nrp)
 *  3. NRP kosong + Nama PERSIS sama di satker ini      → UPDATE (via name)
 *  4. Tidak ada yang cocok sama sekali                 → INSERT BARU
 *
 * Mengapa tidak hanya NRP:
 *  Ketika admin download data personel yang belum punya NRP, lalu mengisi NRP di Excel dan
 *  re-upload, NRP itu belum ada di DB sehingga jika hanya cek NRP, sistem akan menganggap
 *  orang tersebut baru dan membuat duplikat. Matching nama mencegah hal ini.
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

    // ─────────────────────────────────────────────────────────────────────
    // CORE: Multi-strategy matching
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Temukan personel yang paling cocok dari DB berdasarkan NRP dan/atau Nama.
     *
     * @return array{
     *   personnel: Personnel|null,
     *   match_by: 'nrp'|'name_add_nrp'|'name'|'none',
     *   confidence: 'high'|'medium'|'none'
     * }
     */
    protected function findMatch(
        string $nrp,
        string $fullName,
        Collection $existingByNrp,   // keyed by nrp
        Collection $existingByName   // keyed by strtolower(full_name), personel di satker ini TANPA nrp (atau semua)
    ): array {

        $notFound = ['personnel' => null, 'match_by' => 'none', 'confidence' => 'none'];

        // ── Strategi 1: NRP exact match (paling andal) ─────────────────
        if (! empty($nrp)) {
            $byNrp = $existingByNrp->get($nrp);
            if ($byNrp) {
                return ['personnel' => $byNrp, 'match_by' => 'nrp', 'confidence' => 'high'];
            }
        }

        // ── Strategi 2: NRP ada di Excel tapi TIDAK ada di DB ──────────
        //    Cek apakah nama persis sama (kemungkinan admin baru isi NRP orang ini)
        if (! empty($nrp)) {
            $byName = $existingByName->get(strtolower(trim($fullName)));
            if ($byName) {
                // Aman untuk update + simpan NRP yang baru diisi ini
                return ['personnel' => $byName, 'match_by' => 'name_add_nrp', 'confidence' => 'high'];
            }
        }

        // ── Strategi 3: NRP kosong, Nama persis sama di satker ─────────
        if (empty($nrp)) {
            $byName = $existingByName->get(strtolower(trim($fullName)));
            if ($byName) {
                return ['personnel' => $byName, 'match_by' => 'name', 'confidence' => 'high'];
            }
        }

        // ── Tidak ada yang cocok → INSERT BARU ─────────────────────────
        return $notFound;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PREVIEW
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Parse file Excel menjadi array preview.
     *
     * Status per baris:
     *  - 'update'     → cocok via NRP, ada field berubah
     *  - 'update_nrp' → cocok via Nama, NRP baru akan disimpan ke record existing
     *  - 'no_change'  → cocok, tidak ada field berubah
     *  - 'new'        → tidak ada kecocokan → insert baru
     *  - 'corrected'  → update/new tapi pangkat dikoreksi
     *  - 'error'      → pangkat tidak dikenali
     */
    public function generatePreview(Collection $rows): array
    {
        $ranks = Rank::all()->keyBy(fn ($r) => strtoupper($r->name));

        // Index 1: Semua personel satker yang punya NRP (untuk NRP match)
        $existingByNrp = Personnel::with('rank')
            ->where('satker_id', $this->satkerId)
            ->whereNotNull('nrp')
            ->where('nrp', '!=', '')
            ->get()
            ->keyBy('nrp');

        // Index 2: Semua personel satker (termasuk yang tanpa NRP) untuk name match
        // Key: lowercase nama → personel
        // Catatan: jika ada nama duplikat, ambil yang pertama (berdasarkan sort_order rank)
        $existingByName = Personnel::with('rank')
            ->where('satker_id', $this->satkerId)
            ->orderByRaw('COALESCE((SELECT sort_order FROM ranks WHERE ranks.id = personnels.rank_id), 999)')
            ->get()
            ->keyBy(fn ($p) => strtolower(trim($p->full_name)));

        // Tracking NRP yang sudah diproses dalam batch ini (cegah duplicate NRP dalam file)
        $processedNrp = [];

        $preview = [];

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            // ── Parsing ────────────────────────────────────────────────
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
            $keterangan = trim($row[17] ?? '');
            $no = trim($row[0] ?? '');

            // ── Validasi baris kosong / header ────────────────────────
            if (empty($fullName)) {
                continue;
            }
            if (empty($rankInput) && ! is_numeric($no)) {
                continue;
            }
            $nameLower = strtolower($fullName);
            if (str_starts_with($fullName, '=') || in_array($nameLower, ['jumlah', 'total'])) {
                continue;
            }
            if (strlen($fullName) < 2 || is_numeric(str_replace([' ', '.', ','], '', $fullName))) {
                continue;
            }

            $gender = ($genderRaw === 'W') ? 'P' : 'L';

            $rankResult = PersonnelImport::findRankWithCorrection($rankInput, $ranks, $golongan);

            $sizes = [
                'topi' => trim($row[8] ?? ''),
                'kemeja' => trim($row[9] ?? ''),
                'celana' => trim($row[10] ?? ''),
                'olahraga' => trim($row[11] ?? ''),
                'sepatu_dinas' => trim($row[12] ?? ''),
                'sepatu_olahraga' => trim($row[13] ?? ''),
                'jaket' => trim($row[14] ?? ''),
                'sabuk' => trim($row[15] ?? ''),
                'jilbab' => trim($row[16] ?? ''),
            ];
            if ($gender === 'L') {
                unset($sizes['jilbab']);
            }

            // ── Multi-strategy matching ────────────────────────────────
            $match = $this->findMatch($nrp, $fullName, $existingByNrp, $existingByName);
            $existingP = $match['personnel'];
            $personnelId = $existingP?->id;
            $matchBy = $match['match_by'];

            // ── Cek duplikat NRP dalam batch ──────────────────────────
            $isDuplicate = false;
            if (! empty($nrp) && isset($processedNrp[$nrp])) {
                $isDuplicate = true;
            }
            if (! $isDuplicate && ! empty($nrp)) {
                $processedNrp[$nrp] = true;
            }

            // ── Hitung diff ────────────────────────────────────────────
            $diff = [];
            if ($existingP && ! $isDuplicate) {
                $diff = $this->computeDiff($existingP, $fullName, $rankResult, $golongan, $jabatan, $bagian, $gender, $keterangan, $sizes);

                // Jika NRP akan ditambahkan ke record existing, tampilkan di diff
                if ($matchBy === 'name_add_nrp' && ! empty($nrp)) {
                    $diff = array_merge(['NRP/NIP (baru)' => ['from' => '(kosong)', 'to' => $nrp]], $diff);
                }
            }

            // ── Tentukan status ────────────────────────────────────────
            $skipReason = null;   // default: tidak ada alasan skip
            $errorType = null;   // default: bukan error
            $action = $existingP ? 'update' : 'new';  // default action

            if ($isDuplicate) {
                $status = 'error';
                $errorType = 'duplicate';            // NRP sama muncul 2x di file
                $skipReason = "NRP {$nrp} sudah muncul di baris sebelumnya dalam file ini";
            } elseif (! $rankResult['rank'] && ! empty($rankInput)) {
                $status = 'error';
                $errorType = 'unknown_rank';         // Pangkat tidak dikenali
                $skipReason = "Pangkat '{$rankInput}' tidak dikenali";
            } elseif ($existingP) {
                $errorType = null;
                if (empty($diff)) {
                    $status = 'no_change';
                } elseif ($rankResult['corrected'] || $matchBy === 'name_add_nrp') {
                    $status = 'corrected';
                } else {
                    $status = 'update';
                }
                $action = 'update';
            } else {
                $errorType = null;
                $status = $rankResult['corrected'] ? 'corrected' : 'new';
                $action = 'new';
            }

            $preview[] = [
                'row_num' => $rowIndex + 11,
                'action' => $action,
                'match_by' => $matchBy, // 'nrp' | 'name_add_nrp' | 'name' | 'none'
                'nrp' => $nrp,
                'personnel_id' => $personnelId,
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
                'keterangan' => $keterangan,
                'sizes' => $sizes,
                'status' => $status,
                'error_type' => $errorType ?? null,  // 'duplicate' | 'unknown_rank' | null
                'skip_reason' => $skipReason,
                'existing_name' => $existingP?->full_name,
                'existing_rank_name' => $existingP?->rank?->name ?? $existingP?->golongan,
                'existing_jabatan' => $existingP?->jabatan,
                'existing_bagian' => $existingP?->bagian,
                'existing_gender' => $existingP?->gender,
                'existing_keterangan' => $existingP?->keterangan,
                'existing_sizes' => is_array($existingP?->kapor_sizes) ? $existingP->kapor_sizes : [],
                'diff' => $diff,
            ];
        }

        return $preview;
    }

    // ─────────────────────────────────────────────────────────────────────
    // DIFF
    // ─────────────────────────────────────────────────────────────────────

    protected function computeDiff(
        Personnel $existing,
        string $fullName,
        array $rankResult,
        string $golongan,
        string $jabatan,
        string $bagian,
        string $gender,
        string $keterangan,
        array $sizes
    ): array {
        $diff = [];

        if (! empty($fullName) && $fullName !== $existing->full_name) {
            $diff['Nama'] = ['from' => $existing->full_name, 'to' => $fullName];
        }
        if ($rankResult['rank'] && $rankResult['rank']->id !== $existing->rank_id) {
            $diff['Pangkat'] = [
                'from' => $existing->rank?->name ?? '(kosong)',
                'to' => $rankResult['rank']->name,
            ];
        }
        if (! empty($golongan) && $golongan !== $existing->golongan) {
            $diff['Golongan'] = ['from' => $existing->golongan ?? '(kosong)', 'to' => $golongan];
        }
        if (! empty($jabatan) && $jabatan !== $existing->jabatan) {
            $diff['Jabatan'] = ['from' => $existing->jabatan ?? '(kosong)', 'to' => $jabatan];
        }
        if (! empty($bagian) && $bagian !== $existing->bagian) {
            $diff['Bagian/Fungsi'] = ['from' => $existing->bagian ?? '(kosong)', 'to' => $bagian];
        }
        $genderLabel = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
        if (! empty($gender) && $gender !== $existing->gender) {
            $diff['Jenis Kelamin'] = [
                'from' => $genderLabel[$existing->gender] ?? $existing->gender,
                'to' => $genderLabel[$gender] ?? $gender,
            ];
        }
        if ($keterangan !== '' && $keterangan !== $existing->keterangan) {
            $diff['Keterangan'] = ['from' => $existing->keterangan ?? '(kosong)', 'to' => $keterangan];
        }

        $existingSizes = is_array($existing->kapor_sizes) ? $existing->kapor_sizes : [];
        $sizeLabels = [
            'topi' => 'Tutup Kepala',
            'kemeja' => 'Kemeja',
            'celana' => 'Celana/Rok',
            'olahraga' => 'T-Shirt/Olahraga',
            'sepatu_dinas' => 'Sepatu Dinas',
            'sepatu_olahraga' => 'Sepatu Olahraga',
            'jaket' => 'Jaket',
            'sabuk' => 'Sabuk',
            'jilbab' => 'Jilbab',
        ];
        foreach ($sizes as $key => $newVal) {
            if ($newVal === '') {
                continue;
            }
            $oldVal = $existingSizes[$key] ?? '';
            if ($newVal !== $oldVal) {
                $diff[$sizeLabels[$key] ?? $key] = ['from' => $oldVal ?: '(kosong)', 'to' => $newVal];
            }
        }

        return $diff;
    }

    // ─────────────────────────────────────────────────────────────────────
    // SAVE
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Simpan preview ke database.
     *
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
            foreach ($rows as $idx => $data) {
                if ($data['status'] === 'no_change') {
                    $noChangeCount++;

                    continue;
                }
                if ($data['status'] === 'error' && empty($data['rank_id'])) {
                    $errorCount++;
                    $errors[] = "Baris {$data['row_num']} ({$data['full_name']}): pangkat tidak dikenali.";

                    continue;
                }

                try {
                    if ($data['action'] === 'update' && ! empty($data['personnel_id'])) {
                        // ── UPDATE ──────────────────────────────────────────
                        $personnel = Personnel::find($data['personnel_id']);
                        if (! $personnel) {
                            $errorCount++;
                            $errors[] = "Baris {$data['row_num']}: Personel ID {$data['personnel_id']} tidak ditemukan.";

                            continue;
                        }

                        $updateData = [];
                        if (! empty($data['full_name'])) {
                            $updateData['full_name'] = $data['full_name'];
                        }
                        if (! empty($data['jabatan'])) {
                            $updateData['jabatan'] = $data['jabatan'];
                        }
                        if (! empty($data['bagian'])) {
                            $updateData['bagian'] = $data['bagian'];
                        }
                        if (! empty($data['golongan'])) {
                            $updateData['golongan'] = $data['golongan'];
                        }
                        if (! empty($data['gender'])) {
                            $updateData['gender'] = $data['gender'];
                        }
                        if ($data['keterangan'] !== '') {
                            $updateData['keterangan'] = $data['keterangan'];
                        }
                        if (! empty($data['rank_id'])) {
                            $updateData['rank_id'] = $data['rank_id'];
                        }

                        // ─ Jika cocok via nama + NRP baru diisi → simpan NRP ─
                        if (($data['match_by'] ?? '') === 'name_add_nrp' && ! empty($data['nrp'])) {
                            $updateData['nrp'] = $data['nrp'];

                            // Buat/update user account dengan NRP sebagai username
                            if (! $personnel->user) {
                                $user = User::create([
                                    'name' => $data['full_name'] ?: $personnel->full_name,
                                    'username' => $data['nrp'],
                                    'email' => $data['nrp'].'@polda-ntb.local',
                                    'password' => Hash::make($data['nrp']),
                                    'role' => 'personil',
                                    'satker_id' => $personnel->satker_id,
                                ]);
                                $updateData['user_id'] = $user->id;
                            } else {
                                $personnel->user->update(['name' => $data['full_name'] ?: $personnel->full_name]);
                            }
                        }

                        // Merge ukuran kapor
                        $existingSizes = is_array($personnel->kapor_sizes) ? $personnel->kapor_sizes : [];
                        $newSizes = array_filter($data['sizes'] ?? [], fn ($v) => $v !== '');
                        $merged = array_merge($existingSizes, $newSizes);
                        if (! empty($merged)) {
                            $updateData['kapor_sizes'] = $merged;
                        }

                        $personnel->update($updateData);

                        if ($personnel->user && ! empty($data['full_name'])) {
                            $personnel->user->update(['name' => $data['full_name']]);
                        }

                        $successCount++;
                    } else {
                        // ── INSERT BARU ─────────────────────────────────────
                        $nrp = $data['nrp'] ?: null;
                        $password = $nrp ?? ('kapor'.date('Y'));

                        $user = null;
                        if ($nrp) {
                            $user = User::firstOrCreate(
                                ['username' => $nrp],
                                [
                                    'name' => $data['full_name'],
                                    'email' => $nrp.'@polda-ntb.local',
                                    'password' => Hash::make($password),
                                    'role' => 'personil',
                                    'satker_id' => $this->satkerId,
                                ]
                            );
                        }

                        Personnel::create([
                            'user_id' => $user?->id,
                            'satker_id' => $this->satkerId,
                            'rank_id' => $data['rank_id'],
                            'full_name' => $data['full_name'],
                            'nrp' => $nrp,
                            'jabatan' => $data['jabatan'] ?: null,
                            'bagian' => $data['bagian'] ?: null,
                            'golongan' => $data['golongan'] ?: null,
                            'gender' => $data['gender'] ?: 'L',
                            'keterangan' => $data['keterangan'] ?: null,
                            'kapor_sizes' => array_filter($data['sizes'] ?? [], fn ($v) => $v !== ''),
                        ]);

                        $newCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Baris {$data['row_num']} ({$data['full_name']}): ".$e->getMessage();
                }
            }

            DB::commit();
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
}
