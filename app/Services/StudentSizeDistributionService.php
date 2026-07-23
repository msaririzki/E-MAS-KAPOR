<?php

namespace App\Services;

use App\Models\StudentBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentSizeDistributionService
{
    public const SIZE_TYPES = [
        'topi' => 'Topi',
        'kemeja' => 'Kemeja',
        'celana' => 'Celana/Rok',
        'olahraga' => 'Kaos Olahraga',
        'sepatu_dinas' => 'Sepatu Dinas',
        'sepatu_olahraga' => 'Sepatu Olahraga',
        'jaket' => 'Jaket',
        'sabuk' => 'Sabuk',
        'jilbab' => 'Jilbab',
    ];

    public function __construct(private readonly KaporRequirementService $kaporRequirementService) {}

    public function apply(StudentBatch $batch, string $sizeKey, ?string $gender, array $entries): array
    {
        if (! array_key_exists($sizeKey, self::SIZE_TYPES)) {
            throw ValidationException::withMessages(['size_key' => 'Jenis ukuran tidak valid.']);
        }

        if ($sizeKey === 'jilbab') {
            $gender = 'P';
        }

        if (in_array($sizeKey, ['kemeja', 'celana'], true) && ! in_array($gender, ['L', 'P'], true)) {
            throw ValidationException::withMessages([
                'gender' => 'Pilih target Pria atau Wanita karena format ukuran '.$this->sizeTypeLabel($sizeKey).' berbeda.',
            ]);
        }

        $distribution = [];
        foreach ($entries as $entry) {
            $size = $this->kaporRequirementService->sanitizeSizeValue($sizeKey, $entry['size'] ?? null);
            $count = (int) ($entry['count'] ?? 0);

            if ($size === null || $count < 1) {
                continue;
            }

            if (isset($distribution[$size])) {
                throw ValidationException::withMessages(['entries' => 'Ukuran '.$size.' ditulis lebih dari sekali.']);
            }

            $distribution[$size] = $count;
        }

        if ($distribution === []) {
            throw ValidationException::withMessages(['entries' => 'Isi minimal satu ukuran dan jumlah penerima.']);
        }

        $query = $batch->students()->where('is_active', true);
        if (in_array($gender, ['L', 'P'], true)) {
            $query->where('gender', $gender);
        }

        $students = $query->orderBy('student_code')->get(['id', 'kapor_sizes']);
        $targetCount = $students->count();
        $assignedCount = array_sum($distribution);

        if ($assignedCount > $targetCount) {
            throw ValidationException::withMessages([
                'entries' => 'Jumlah kuota '.$assignedCount.' melebihi '.$targetCount.' siswa pada target yang dipilih.',
            ]);
        }

        $queue = [];
        foreach ($distribution as $size => $count) {
            array_push($queue, ...array_fill(0, $count, $size));
        }

        DB::transaction(function () use ($sizeKey, $students, $queue): void {
            $now = now();
            $rows = [];

            foreach ($students as $index => $student) {
                $sizes = is_array($student->kapor_sizes) ? $student->kapor_sizes : [];
                unset($sizes[$sizeKey]);

                if (isset($queue[$index])) {
                    $sizes[$sizeKey] = $queue[$index];
                }

                $rows[] = [
                    'id' => $student->id,
                    'kapor_sizes' => $sizes === [] ? null : json_encode($sizes, JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 250) as $chunk) {
                $cases = [];
                $bindings = [];
                $ids = [];

                foreach ($chunk as $row) {
                    $cases[] = 'WHEN ? THEN ?';
                    $bindings[] = $row['id'];
                    $bindings[] = $row['kapor_sizes'];
                    $ids[] = $row['id'];
                }

                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $bindings[] = $now->toDateTimeString();
                array_push($bindings, ...$ids);

                DB::update(
                    'UPDATE personnels SET kapor_sizes = CASE id '.implode(' ', $cases).' END, updated_at = ? WHERE id IN ('.$placeholders.')',
                    $bindings,
                );
            }
        });

        return [
            'size_key' => $sizeKey,
            'size_label' => self::SIZE_TYPES[$sizeKey],
            'gender' => $gender,
            'target' => $targetCount,
            'assigned' => $assignedCount,
            'remaining' => $targetCount - $assignedCount,
            'distribution' => $distribution,
        ];
    }

    private function sizeTypeLabel(string $sizeKey): string
    {
        return strtolower(self::SIZE_TYPES[$sizeKey] ?? 'ini');
    }
}
