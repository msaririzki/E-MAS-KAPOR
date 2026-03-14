<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportAuditLogExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    public function collection()
    {
        $rows = collect();

        // Cek apakah Spatie Activity Log digunakan
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            $activities = \Spatie\Activitylog\Models\Activity::with('causer')
                ->latest()
                ->limit(1000)
                ->get();

            $no = 1;
            foreach ($activities as $a) {
                $rows->push([
                    'no' => $no++,
                    'waktu' => $a->created_at->format('d M Y H:i'),
                    'pelaku' => $a->causer->name ?? 'System',
                    'deskripsi' => $a->description,
                    'subjek' => $a->subject_type ? class_basename($a->subject_type).' #'.$a->subject_id : '-',
                    'properti' => json_encode($a->properties ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        // Fallback: Cek model AuditLog custom
        if ($rows->isEmpty() && class_exists(\App\Models\AuditLog::class)) {
            $logs = \App\Models\AuditLog::latest()->limit(1000)->get();
            $no = 1;
            foreach ($logs as $log) {
                $rows->push([
                    'no' => $no++,
                    'waktu' => $log->created_at->format('d M Y H:i'),
                    'pelaku' => $log->user_name ?? $log->user_id ?? 'System',
                    'deskripsi' => $log->action ?? $log->description ?? '-',
                    'subjek' => $log->model_type ?? '-',
                    'properti' => json_encode($log->changes ?? $log->data ?? [], JSON_UNESCAPED_UNICODE),
                ]);
            }
        }

        if ($rows->isEmpty()) {
            $rows->push([
                'no' => 1,
                'waktu' => '-',
                'pelaku' => '-',
                'deskripsi' => 'Belum ada data audit log yang tercatat.',
                'subjek' => '-',
                'properti' => '-',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Waktu', 'Pelaku', 'Deskripsi Aktivitas', 'Subjek/Model', 'Detail Perubahan'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function title(): string
    {
        return 'Riwayat Audit Log';
    }
}
