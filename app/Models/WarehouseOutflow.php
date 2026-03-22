<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseOutflow extends Model
{
    protected $fillable = [
        'warehouse_item_size_id',
        'satker_id',
        'quantity',
        'outflow_date',
        'recipient_name',
        'reference_note',
    ];

    protected function casts(): array
    {
        return [
            'outflow_date' => 'date',
        ];
    }

    public function itemSize(): BelongsTo
    {
        return $this->belongsTo(WarehouseItemSize::class, 'warehouse_item_size_id');
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class, 'satker_id');
    }
}
