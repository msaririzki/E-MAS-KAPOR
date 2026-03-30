<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseSignatory extends Model
{
    protected $fillable = [
        'satuan_kerja',
        'name',
        'jabatan',
        'atribut',
        'wakil',
        'pangkat',
        'nrp',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
