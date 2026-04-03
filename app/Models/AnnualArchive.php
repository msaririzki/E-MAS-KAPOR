<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'fiscal_year',
        'format',
        'title',
        'file_name',
        'file_path',
        'disk',
        'generated_by',
        'generated_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
