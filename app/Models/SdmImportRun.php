<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SdmImportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'initiated_by',
        'status',
        'processing_mode',
        'source_files',
        'summary',
        'preview_payload_path',
        'error_report_path',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'source_files' => 'array',
            'summary' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
