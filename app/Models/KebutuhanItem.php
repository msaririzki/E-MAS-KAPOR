<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KebutuhanItem extends Model
{
    use HasFactory;

    protected $table = 'kebutuhan_items';

    protected $fillable = [
        'kebutuhan_id',
        'kapor_item_id',
        'quantity',
        'notes',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function kebutuhan(): BelongsTo
    {
        return $this->belongsTo(Kebutuhan::class);
    }

    public function kaporItem(): BelongsTo
    {
        return $this->belongsTo(KaporItem::class);
    }
}
