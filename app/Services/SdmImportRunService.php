<?php

namespace App\Services;

use App\Imports\PersonnelSdmImport;
use App\Models\Rank;
use App\Models\Satker;
use App\Models\SdmImportRun;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SdmImportRunService
{
    public function processPreviewRun(SdmImportRun $run): void
    {
        $run->update([
            'status' => 'processing',
            'started_at' => $run->started_at ?? now(),
        ]);

        try {
            $import = new PersonnelSdmImport;
            $preview = [];
            $sourceFiles = collect($run->source_files ?? []);

            foreach ($sourceFiles as $fileMeta) {
                $path = $fileMeta['path'] ?? null;
                if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
                    continue;
                }

                $collection = Excel::toCollection($import, Storage::disk('local')->path($path));
                $fileLabel = $fileMeta['original_name'] ?? basename($path);

                foreach ($collection as $sheetIndex => $sheetRows) {
                    $sheetLabel = is_scalar($sheetIndex) ? 'Sheet '.((int) $sheetIndex + 1) : 'Sheet';
                    $sourceLabel = $fileLabel.' / '.$sheetLabel;
                    $sheetPreview = $import->generatePreview($sheetRows, $sourceLabel);
                    $preview = array_merge($preview, $sheetPreview);
                }
            }

            $stats = $this->buildStats($preview, $sourceFiles->count());
            $previewPath = $this->storePreviewPayload($run, $preview, $stats);
            $errorPath = $this->storeErrorReport($run, $preview);

            $run->update([
                'status' => 'preview_ready',
                'summary' => $stats,
                'preview_payload_path' => $previewPath,
                'error_report_path' => $errorPath,
                'finished_at' => now(),
            ]);

            foreach ($sourceFiles as $fileMeta) {
                $path = $fileMeta['path'] ?? null;
                if (is_string($path) && Storage::disk('local')->exists($path)) {
                    Storage::disk('local')->delete($path);
                }
            }
        } catch (\Throwable $throwable) {
            $run->update([
                'status' => 'failed',
                'finished_at' => now(),
                'summary' => [
                    'error_message' => $throwable->getMessage(),
                ],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $preview
     * @return array<string, int>
     */
    public function buildStats(array $preview, int $fileCount): array
    {
        $collection = collect($preview);

        return [
            'ok' => $collection->where('status', 'ok')->count(),
            'corrected' => $collection->where('status', 'corrected')->count(),
            'error' => $collection->where('status', 'error')->count(),
            'total' => $collection->count(),
            'satker_count' => $collection->pluck('satker_id')->filter()->unique()->count(),
            'file_count' => $fileCount,
            'unresolved_satker_count' => $collection->where('requires_manual_satker', true)->count(),
            'unknown_rank_count' => $collection->where('requires_manual_rank', true)->count(),
            'duplicate_count' => $collection->where('duplicate_nrp', true)->count(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $preview
     */
    public function storePreviewPayload(SdmImportRun $run, array $preview, array $stats): string
    {
        $path = 'import-previews/sdm/run-'.$run->id.'.json';
        $payload = json_encode([
            'preview' => $preview,
            'stats' => $stats,
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            throw new \RuntimeException('Gagal menyusun payload preview SDM.');
        }

        Storage::disk('local')->put($path, $payload);

        return $path;
    }

    /**
     * @param  array<int, array<string, mixed>>  $preview
     * @param  array<int, string>  $processingErrors
     */
    public function storeErrorReport(SdmImportRun $run, array $preview, array $processingErrors = []): ?string
    {
        $rows = [];

        foreach ($preview as $row) {
            if (($row['status'] ?? 'ok') !== 'error') {
                continue;
            }

            $rows[] = [
                $row['row_num'] ?? '',
                $row['sheet_name'] ?? '',
                $row['full_name'] ?? '',
                $row['nrp'] ?? '',
                $row['rank_input'] ?? '',
                $row['jabatan'] ?? '',
                $row['satker_name'] ?? '',
                implode(' | ', $row['status_notes'] ?? []),
            ];
        }

        foreach ($processingErrors as $message) {
            $rows[] = ['', 'runtime', '', '', '', '', '', $message];
        }

        if ($rows === []) {
            return null;
        }

        $path = 'import-previews/sdm/run-'.$run->id.'-errors.csv';
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['row_num', 'sheet_name', 'full_name', 'nrp', 'rank_input', 'jabatan', 'satker_name', 'error_notes']);
        foreach ($rows as $csvRow) {
            fputcsv($handle, $csvRow);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        Storage::disk('local')->put($path, $csv);

        return $path;
    }

    /**
     * @param  array<int, array<string, mixed>>  $preview
     * @return array<int, array<string, mixed>>
     */
    public function refreshPreviewRows(array $preview): array
    {
        $ranksById = Rank::query()->get()->keyBy('id');
        $satkersById = Satker::query()->get()->keyBy('id');

        return collect($preview)->map(function (array $row) use ($ranksById, $satkersById) {
            $rank = ! empty($row['rank_id']) ? $ranksById->get((int) $row['rank_id']) : null;
            $satker = ! empty($row['satker_id']) ? $satkersById->get((int) $row['satker_id']) : null;
            $rankIsPlaceholder = trim((string) ($row['rank_input'] ?? '')) === '';
            $isFatal = (bool) ($row['duplicate_nrp'] ?? false)
                || trim((string) ($row['jabatan'] ?? '')) === ''
                || ! in_array($row['gender'] ?? null, ['L', 'P'], true)
                || trim((string) ($row['religion'] ?? '')) === '';

            $status = 'ok';
            $notes = [];
            $incompleteFields = [];

            if ($rank === null && ! $rankIsPlaceholder) {
                $status = 'error';
                $notes[] = 'Pangkat tidak dikenali';
            } elseif ($rank === null) {
                $status = 'corrected';
                $incompleteFields[] = 'Pangkat';
            }

            if ($satker === null && trim((string) ($row['jabatan'] ?? '')) !== '') {
                $status = 'error';
                $notes[] = 'Satker dari jabatan tidak dikenali';
            }

            if (trim((string) ($row['nrp'] ?? '')) === '') {
                if ($status !== 'error') {
                    $status = 'corrected';
                }
                $incompleteFields[] = 'NRP/NIP';
                $notes[] = 'NRP/NIP kosong';
            }

            if ((bool) ($row['duplicate_nrp'] ?? false)) {
                $status = 'error';
                $notes[] = 'NRP/NIP duplikat dalam file';
            }

            if ($isFatal) {
                $status = 'error';
            }

            if ($status === 'corrected' && $incompleteFields !== []) {
                $notes[] = 'Belum lengkap: '.implode(', ', array_unique($incompleteFields));
            }

            $row['rank_name'] = $rank?->name;
            $row['golongan'] = $row['golongan'] ?: ($rank?->category ?? '');
            $row['satker_name'] = $satker?->name;
            $row['status'] = $status;
            $row['status_notes'] = array_values(array_unique($notes));
            $row['requires_manual_rank'] = $rank === null && ! $rankIsPlaceholder;
            $row['requires_manual_satker'] = $satker === null && trim((string) ($row['jabatan'] ?? '')) !== '';
            $row['incomplete_fields'] = array_values(array_unique($incompleteFields));
            $row['fatal_error'] = $isFatal;

            return $row;
        })->all();
    }
}
