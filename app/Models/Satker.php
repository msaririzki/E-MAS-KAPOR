<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Satker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'parent_id',
        'sort_order',
        'polri_count',
        'pns_count',
    ];

    // ── Relationships ─────────────────────────────────────────

    /**
     * Parent satker (e.g. Polres → Polda)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Satker::class , 'parent_id');
    }

    /**
     * Child satkers (e.g. Polda → [Polres1, Polres2, ...])
     */
    public function children(): HasMany
    {
        return $this->hasMany(Satker::class , 'parent_id');
    }

    /**
     * Recursive children (nested tree)
     */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }
}
