<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseItem extends Model
{
    protected $fillable = [
        'name',
        'unit',
        'price',
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
