<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseItemSize extends Model
{
    protected $fillable = [
        'warehouse_item_id',
        'size_label',
        'stock',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(WarehouseItem::class, 'warehouse_item_id');
    }
}
