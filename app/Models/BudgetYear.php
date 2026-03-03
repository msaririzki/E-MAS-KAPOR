<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function packages(): HasMany
    {
        return $this->hasMany(BudgetPackage::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Accessors ─────────────────────────────────────────────

    public function getTotalPackagesAttribute(): int
    {
        return $this->packages()->count();
    }

    public function getTotalBudgetAttribute(): string
    {
        $total = $this->packages()->sum('total_budget');

        return 'Rp ' . number_format((float) $total, 0, ',', '.');
    }
}
