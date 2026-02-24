<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KaporSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'kapor_item_id',
        'size_label',
        'sort_order',
        'gender', // L, P, null
    ];

    // ── Relationships ─────────────────────────────────────────

    public function kaporItem(): BelongsTo
    {
        return $this->belongsTo(KaporItem::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(KaporSubmission::class);
    }
}
