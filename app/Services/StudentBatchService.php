<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Satker;
use App\Models\StudentBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class StudentBatchService
{
    public function create(array $data, ?int $createdBy): StudentBatch
    {
        $satker = Satker::query()->where('code', 'POLDA-NTB')->first();

        if ($satker === null) {
            throw new RuntimeException('Satker Polda NTB dengan kode POLDA-NTB belum tersedia.');
        }

        return DB::transaction(function () use ($data, $createdBy, $satker): StudentBatch {
            $group = strtoupper((string) $data['procurement_group']);
            $year = (int) $data['fiscal_year'];
            $sequence = StudentBatch::query()
                ->where('fiscal_year', $year)
                ->where('procurement_group', $group)
                ->lockForUpdate()
                ->count() + 1;
            $groupCode = $this->groupCode($group);
            $batchCode = sprintf('SISWA-%d-%s-%03d', $year, $groupCode, $sequence);

            $batch = StudentBatch::create([
                'code' => $batchCode,
                'name' => trim((string) $data['name']),
                'fiscal_year' => $year,
                'satker_id' => $satker->id,
                'procurement_group' => $group,
                'default_rank_id' => $data['default_rank_id'] ?? null,
                'default_jabatan' => trim((string) ($data['default_jabatan'] ?? 'SISWA')) ?: 'SISWA',
                'default_bagian' => trim((string) ($data['default_bagian'] ?? 'SISWA')) ?: 'SISWA',
                'requested_male_count' => (int) $data['male_count'],
                'requested_female_count' => (int) $data['female_count'],
                'status' => StudentBatch::STATUS_ACTIVE,
                'notes' => filled($data['notes'] ?? null) ? trim((string) $data['notes']) : null,
                'created_by' => $createdBy,
            ]);

            $this->insertStudents($batch, 'L', (int) $data['male_count']);
            $this->insertStudents($batch, 'P', (int) $data['female_count']);

            return $batch->load('satker');
        });
    }

    public function addStudents(StudentBatch $batch, int $maleCount, int $femaleCount): int
    {
        return DB::transaction(function () use ($batch, $femaleCount, $maleCount): int {
            $lockedBatch = StudentBatch::query()->lockForUpdate()->findOrFail($batch->id);

            $this->insertStudents($lockedBatch, 'L', $maleCount);
            $this->insertStudents($lockedBatch, 'P', $femaleCount);

            $lockedBatch->update([
                'requested_male_count' => $lockedBatch->students()->where('gender', 'L')->count(),
                'requested_female_count' => $lockedBatch->students()->where('gender', 'P')->count(),
            ]);

            return $maleCount + $femaleCount;
        });
    }

    private function insertStudents(StudentBatch $batch, string $gender, int $count): void
    {
        if ($count < 1) {
            return;
        }

        $genderCode = $gender === 'P' ? 'W' : 'P';
        $genderLabel = $gender === 'P' ? 'WANITA' : 'PRIA';
        $rows = [];
        $now = now();
        $lastCode = $batch->students()
            ->where('student_code', 'like', $batch->code.'-'.$genderCode.'-%')
            ->orderByDesc('student_code')
            ->value('student_code');
        $startSequence = $lastCode === null ? 1 : ((int) substr($lastCode, -5)) + 1;

        for ($offset = 0; $offset < $count; $offset++) {
            $sequence = $startSequence + $offset;
            $studentCode = sprintf('%s-%s-%05d', $batch->code, $genderCode, $sequence);
            $rows[] = [
                'user_id' => null,
                'student_batch_id' => $batch->id,
                'student_code' => $studentCode,
                'nrp' => $studentCode,
                'full_name' => sprintf('SISWA %s %s %05d', $batch->procurement_group, $genderLabel, $sequence),
                'gender' => $gender,
                'personnel_type' => 'Polri',
                'procurement_group' => $batch->procurement_group,
                'rank_id' => $batch->default_rank_id,
                'golongan' => null,
                'jabatan' => $batch->default_jabatan ?: 'SISWA',
                'bagian' => $batch->default_bagian ?: 'SISWA',
                'keterangan' => 'SISWA',
                'keterangan_2' => 'SISWA '.$batch->procurement_group,
                'satker_id' => $batch->satker_id,
                'is_active' => true,
                'verification_status' => 'approved',
                'kapor_sizes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) === 500) {
                Personnel::query()->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            Personnel::query()->insert($rows);
        }
    }

    private function groupCode(string $group): string
    {
        return match ($group) {
            'TAMTAMA' => 'TA',
            'BINTARA' => 'BA',
            'PAMA' => 'PAMA',
            'PAMEN' => 'PAMEN',
            default => Str::upper(Str::substr($group, 0, 5)),
        };
    }
}
