<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackageItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_package_id',
        'kapor_item_id',
        'sort_order',
        'custom_price',
        'calculated_qty',
        'calculated_total',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'custom_price' => 'decimal:2',
            'calculated_total' => 'decimal:2',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function budgetPackage(): BelongsTo
    {
        return $this->belongsTo(BudgetPackage::class);
    }

    public function kaporItem(): BelongsTo
    {
        return $this->belongsTo(KaporItem::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PackageItemRecipient::class);
    }

    // ── Accessors ─────────────────────────────────────────────

    /**
     * Harga efektif: custom_price jika ada, kalau tidak pakai harga dari kaporItem
     */
    public function getEffectivePriceAttribute(): float
    {
        return (float) ($this->custom_price ?? $this->kaporItem->price ?? 0);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format($this->effective_price, 0, ',', '.');
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp '.number_format((float) $this->calculated_total, 0, ',', '.');
    }
}
