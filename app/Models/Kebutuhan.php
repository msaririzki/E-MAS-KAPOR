<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kebutuhan extends Model
{
    use HasFactory;

    protected $table = 'kebutuhans';

    protected $fillable = [
        'satker_id',
        'user_id',
        'title',
        'fiscal_year',
        'status',
        'notes',
        'admin_notes',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KebutuhanItem::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForCurrentSatker($query)
    {
        if (auth()->check() && auth()->user()->hasRole('admin_satker')) {
            return $query->where('satker_id', auth()->user()->satker_id);
        }

        return $query;
    }

    // ── Helpers ───────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isDiajukan(): bool
    {
        return $this->status === 'diajukan';
    }

    public function isDisetujui(): bool
    {
        return $this->status === 'disetujui';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'badge-neutral',
            'diajukan' => 'badge-info',
            'disetujui' => 'badge-success',
            'ditolak' => 'badge-danger',
            default => 'badge-neutral',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'diajukan' => 'Diajukan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            default => ucfirst($this->status),
        };
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items()->count();
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items()->sum('quantity');
    }
}
