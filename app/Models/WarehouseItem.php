<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarehouseItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'unit',
        'price',
        'deletion_reason',
        'deleted_at_stock',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    /**
     * Format harga ke Rupiah
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(WarehouseItemSize::class);
    }
}
