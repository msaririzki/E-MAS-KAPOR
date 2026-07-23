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
        'sumber_pengadaan',
        'kategori_stok',
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
        $price = (float) $this->price;
        $decimals = floor($price) == $price ? 0 : 2;

        return 'Rp '.number_format($price, $decimals, ',', '.');
    }

    public function sizes(): HasMany
    {
        return $this->hasMany(WarehouseItemSize::class);
    }
}
