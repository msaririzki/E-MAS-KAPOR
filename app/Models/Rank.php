<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'sort_order',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}
