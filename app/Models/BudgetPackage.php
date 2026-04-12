<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_year_id',
        'name',
        'description',
        'status',
        'total_budget',
    ];

    protected function casts(): array
    {
        return [
            'total_budget' => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function budgetYear(): BelongsTo
    {
        return $this->belongsTo(BudgetYear::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class)
            ->orderByRaw('CASE WHEN sort_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getFormattedBudgetAttribute(): string
    {
        return 'Rp '.number_format((float) $this->total_budget, 0, ',', '.');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'finalized' => 'Final',
            'archived' => 'Arsip',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['bg' => '#FEF3C7', 'text' => '#92400E'],
            'finalized' => ['bg' => '#DCFCE7', 'text' => '#166534'],
            'archived' => ['bg' => '#F3F4F6', 'text' => '#6B7280'],
            default => ['bg' => '#F3F4F6', 'text' => '#6B7280'],
        };
    }
}
