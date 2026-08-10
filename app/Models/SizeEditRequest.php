<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeEditRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_item_size_id',
        'requested_by',
        'old_stock',
        'requested_stock',
        'status',
        'reason',
    ];

    public function itemSize()
    {
        return $this->belongsTo(WarehouseItemSize::class, 'warehouse_item_size_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
