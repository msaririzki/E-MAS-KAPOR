<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IdentifikasiItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_name',
        'category',
        'description',
        'eligible_satker_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'eligible_satker_count' => 'integer',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function kebutuhanItems(): HasMany
    {
        return $this->hasMany(KebutuhanItem::class, 'identifikasi_item_id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
