<?php

namespace App\Imports;

use App\Models\Personnel;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStartRow;

/**
 * Import khusus untuk Super Admin (Data SDM).
 * Hanya membaca data pokok: Nama, NRP/NIP, Pangkat, Golongan, Jenis Kelamin, Agama.
 * Kolom lainnya (Jabatan, Bagian, Keterangan, Ukuran Kapor) akan dikosongkan secara paksa.
 */
class PersonnelSdmImport extends PersonnelImport
{
    /**
     * Override generatePreview untuk memaksa kolom spesifik kosong.
     * Mengasumsikan urutan standar SDM Excel: 
     * 0=NO, 1=NAMA, 2=NRP/NIP, 3=PANGKAT, 4=GOLONGAN, 5=JENIS KELAMIN, 6=AGAMA.
     */
    public function startRow(): int
    {
        // Template SDM Super Admin diasumsikan hanya punya 1 baris header, data mulai dari baris 2
        return 2;
    }

    public function generatePreview(Collection $rows): array
    {
        $ranks = Rank::all()->keyBy(fn ($r) => strtoupper($r->name));
        $preview = [];
        $seenNrps = [];

        $isSiswaSatker = false;
        if ($this->satkerId) {
            $satker = Satker::find($this->satkerId);
            if ($satker && strtoupper($satker->code) === 'SISWA') {
                $isSiswaSatker = true;
            }
        }

        // Untuk import SDM, format sudah baku (tidak ada double-NO offset)
        $offset = 0;

        foreach ($rows as $rowIndex => $row) {
            if ($row instanceof Collection) {
                $row = $row->toArray();
            }

            $colName = 1 + $offset;
            $colNrp = 2 + $offset;
            $colRank = 3 + $offset;
            $colGol = 4 + $offset;
            $colGender = 5 + $offset;
            $colAgama = 6 + $offset;

            $fullNameRtn = str_replace('*', '', $row[$colName] ?? '');
            $fullName = trim(preg_replace('/\s+/', ' ', $fullNameRtn));

            $rankInputRtn = str_replace('*', '', $row[$colRank] ?? '');
            $rankInput = trim(preg_replace('/\s+/', ' ', $rankInputRtn));

            $golongan = trim($row[$colGol] ?? '');
            $nrpRaw = $row[$colNrp] ?? '';

            // NRP Precision fix copy
            if ((is_int($nrpRaw) || is_float($nrpRaw)) && $nrpRaw >= 1000000000000000) {
                $strNrp = sprintf('%.15g', (float) $nrpRaw);
                if (stripos($strNrp, 'e') !== false) {
                    $parts = explode('e', strtolower($strNrp));
                    $base = str_replace('.', '', $parts[0]);
                    $exp = (int) $parts[1];
                    $nrp = str_pad($base, $exp + 1, '0', STR_PAD_RIGHT);
                } else {
                    $nrp = str_replace('.', '', $strNrp);
                }
            } else {
                $trimNrp = trim((string) $nrpRaw);
                if (is_numeric($trimNrp) && stripos($trimNrp, 'E') !== false) {
                    $nrp = number_format((float) $trimNrp, 0, '', '');
                } else {
                    $nrp = $trimNrp;
                }
            }

            // SDM: Paksa kosongkan Jabatan, Bagian, Keterangan, Ukuran
            $jabatan = '';
            $bagian = '';
            $keterangan = '';
            $sizes = [];

            $genderRaw = strtoupper(trim($row[$colGender] ?? ''));
            $agamaRaw = trim($row[$colAgama] ?? '');

            $no = trim($row[0] ?? '');

            if (empty($fullName)) {
                continue;
            }

            $nameLower = strtolower($fullName);
            if (str_starts_with($fullName, '=')) continue;
            if ($nameLower === 'jumlah' || $nameLower === 'total' || $nameLower === 'dst' || $nameLower === 'dst.') continue;
            if ($nameLower === 'nama' || $nameLower === 'nama lengkap' || $nameLower === 'nama personel') continue;

            $golLower = strtolower($golongan);
            if ($golLower === 'jumlah' || $golLower === 'total' || $golLower === 'sub total' || $golLower === 'sub jumlah') continue;

            $rankIsPlaceholder = empty($rankInput) || preg_match('/^[\-\.]+$/', $rankInput) || (is_numeric($rankInput) && strlen($rankInput) <= 3);
            if (empty($nrp) && $rankIsPlaceholder && empty($golongan) && empty($genderRaw)) continue;

            if (strlen($fullName) < 2 || is_numeric(str_replace([' ', '.', ','], '', $fullName))) continue;

            if (preg_match('/^[\-\.]+$/', $nrp)) {
                $nrp = '';
            }
            if (!empty($nrp)) {
                $nrpLower = strtolower($nrp);
                if (str_starts_with($nrp, '=')) continue;
                if ($nrpLower === 'jumlah' || $nrpLower === 'total') continue;
                if (strlen($nrp) < 4) $nrp = '';
            }

            $gender = ($genderRaw === 'W') ? 'P' : 'L';
            $rankResult = parent::findRankWithCorrection($rankInput, $ranks, $golongan, $isSiswaSatker);

            $status = 'ok';
            $incompleteFields = [];

            if (!$rankResult['rank'] && !empty($rankInput) && !$rankIsPlaceholder) {
                $status = 'error';
            } elseif (!$rankResult['rank']) {
                $incompleteFields[] = 'Pangkat';
            }
            if (empty($nrp)) {
                $incompleteFields[] = 'NRP/NIP';
            }

            $isDuplicateNrp = false;
            if (!empty($nrp)) {
                if (isset($seenNrps[$nrp])) {
                    $isDuplicateNrp = true;
                    if ($status !== 'error') $status = 'corrected';
                    $dupeRow = $seenNrps[$nrp];
                    $dupeLabel = "NRP duplikat dengan baris #{$dupeRow}";
                    if ($rankResult['corrected_to']) {
                        $rankResult['corrected_to'] .= ' | ' . $dupeLabel;
                    } else {
                        $rankResult['corrected'] = true;
                        $rankResult['corrected_to'] = $dupeLabel;
                    }
                }
                $seenNrps[$nrp] = $rowIndex + 11;
            }

            if ($rankResult['corrected'] && $status !== 'error') {
                $status = 'corrected';
            }

            if (!empty($incompleteFields) && $status !== 'error') {
                $status = 'corrected';
                $missingLabel = '— (Belum Lengkap: ' . implode(', ', $incompleteFields) . ')';
                if ($rankResult['corrected_to']) {
                    $rankResult['corrected_to'] .= ' | ' . implode(', ', $incompleteFields) . ' kosong';
                } else {
                    $rankResult['corrected'] = true;
                    $rankResult['corrected_to'] = $missingLabel;
                }
            }

            $preview[] = [
                'row_num' => $rowIndex + $this->startRow(),
                'full_name' => $fullName,
                'rank_input' => $rankInput,
                'rank_corrected' => $rankResult['corrected_to'],
                'rank_id' => $rankResult['rank']?->id,
                'rank_name' => $rankResult['rank']?->name,
                'golongan' => $golongan,
                'nrp' => $nrp,
                'jabatan' => $jabatan,
                'bagian' => $bagian,
                'keterangan' => $keterangan,
                'gender' => $gender,
                'gender_raw' => $genderRaw,
                'religion' => $agamaRaw, // Tambahan field Agama
                'sizes' => $sizes,
                'status' => $status,
                'incomplete_fields' => $incompleteFields,
                'duplicate_nrp' => $isDuplicateNrp,
            ];
        }

        return $preview;
    }

    /**
     * Override saveFromPreviewData untuk memasukkan field agama yang baru.
     */
    public function saveFromPreviewData(array $rows, int $satkerId): array
    {
        set_time_limit(0);
        ini_set('memory_limit', '2G');

        $satker = Satker::findOrFail($satkerId);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        $ranksById = Rank::all()->keyBy('id');
        $allNrp = collect($rows)->pluck('nrp')->map(fn ($v) => trim($v))->filter()->unique()->values()->all();
        $existingPersonnel = Personnel::whereIn('nrp', $allNrp)->get()->keyBy('nrp');
        $existingUsers = User::whereIn('nrp_nip', $allNrp)->get()->keyBy('nrp_nip');

        DB::transaction(function () use (
            $rows, $satker, $ranksById, $existingPersonnel, $existingUsers,
            &$successCount, &$errorCount, &$errors
        ) {
            foreach ($rows as $idx => $data) {
                $nrp = trim($data['nrp'] ?? '');
                $fullName = trim($data['full_name'] ?? '');
                $rankId = (int) ($data['rank_id'] ?? 0);
                $gender = $data['gender'] ?? 'L';
                $jabatan = ''; // SDM form kosong
                $bagian = '';
                $golongan = trim($data['golongan'] ?? '');
                $keterangan = '';
                $religion = trim($data['religion'] ?? '');

                if (empty($fullName)) {
                    $errorCount++;
                    $errors[] = "Baris {$idx}: Nama kosong, dilewati.";
                    continue;
                }

                $rank = $rankId ? $ranksById->get($rankId) : null;
                if ($rankId && !$rank) {
                    $errorCount++;
                    $errors[] = "Baris {$idx}: Pangkat ID={$rankId} tidak ditemukan (NRP: {$nrp}).";
                    continue;
                }

                try {
                    $effectiveNrp = $nrp;
                    $isEmptyNrp = empty($nrp);
                    $isDuplicateNrp = !empty($data['duplicate_nrp']);

                    if ($isEmptyNrp || $isDuplicateNrp) {
                        $effectiveNrp = 'TEMP-' . strtoupper(substr(md5($fullName . $idx . time()), 0, 8));
                    }

                    $personnel = ($isEmptyNrp || $isDuplicateNrp) ? null : $existingPersonnel->get($effectiveNrp);

                    if (!$personnel) {
                        $user = $existingUsers->get($effectiveNrp);
                        if (!$user) {
                            $user = User::create([
                                'name' => $fullName,
                                'nrp_nip' => $effectiveNrp,
                                'password' => password_hash($effectiveNrp, PASSWORD_BCRYPT, ['cost' => 4]),
                                'satker_id' => $satker->id,
                                'is_active' => true,
                            ]);
                            $user->assignRole('personil');
                            $existingUsers->put($effectiveNrp, $user);
                        }

                        $personnelType = 'Polri';
                        if ($rank && $rank->category === 'PNS') {
                            $personnelType = 'PNS';
                        } elseif (!$rank && !empty($golongan)) {
                            $personnelType = 'PNS';
                        }

                        $saveNrp = $isEmptyNrp ? null : ($isDuplicateNrp ? $nrp : $effectiveNrp);

                        $personnel = Personnel::create([
                            'user_id' => $user->id,
                            'nrp' => $saveNrp,
                            'full_name' => $fullName,
                            'rank_id' => $rank ? $rank->id : null,
                            'satker_id' => $satker->id,
                            'jabatan' => $jabatan,
                            'bagian' => $bagian,
                            'personnel_type' => $personnelType,
                            'gender' => $gender,
                            'golongan' => $golongan,
                            'religion' => $religion, // AGAMA
                            'keterangan' => $keterangan,
                            'kapor_sizes' => [], // KOSONGKAN UKURAN
                            'is_active' => true,
                        ]);
                        if (!$isDuplicateNrp) {
                            $existingPersonnel->put($effectiveNrp, $personnel);
                        }
                    } else {
                        $updateData = [
                            'full_name' => $fullName,
                            'satker_id' => $satker->id,
                            'golongan' => $golongan,
                            'gender' => $gender,
                            'religion' => $religion, // Tambahkan agama ke update
                        ];
                        if ($rank) {
                            $updateData['rank_id'] = $rank->id;
                        }
                        // Update tanpa menimpa ukuran yang sudah diisi personil/admin_satker
                        $personnel->update($updateData);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    $errors[] = "Baris {$idx} (NRP: {$nrp}): " . $e->getMessage();
                }
            }
        });

        // Pakai scope parent jika ini class murni. self:: merujuk ke class ini (PersonnelSdmImport), 
        // tapi since recalculateSatkerCount exists on parent, call it explicitly per static bindings.
        parent::recalculateSatkerCount($satkerId);

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }
}
