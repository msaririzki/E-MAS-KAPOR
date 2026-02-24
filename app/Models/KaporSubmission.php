<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaporSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_id',
        'kapor_item_id',
        'kapor_size_id',
        'fiscal_year',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function kaporItem(): BelongsTo
    {
        return $this->belongsTo(KaporItem::class);
    }

    public function kaporSize(): BelongsTo
    {
        return $this->belongsTo(KaporSize::class);
    }
}
