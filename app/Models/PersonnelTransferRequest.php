<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelTransferRequest extends Model
{
    protected $fillable = [
        'personnel_id',
        'from_satker_id',
        'to_satker_id',
        'requested_by',
        'reviewed_by',
        'source_file',
        'source_sheet',
        'source_row',
        'payload',
        'status',
        'review_note',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function fromSatker(): BelongsTo
    {
        return $this->belongsTo(Satker::class, 'from_satker_id');
    }

    public function toSatker(): BelongsTo
    {
        return $this->belongsTo(Satker::class, 'to_satker_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
