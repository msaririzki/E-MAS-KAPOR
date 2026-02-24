<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends Model
{
    use HasFactory;

    protected $table = 'personnels';

    protected $fillable = [
        'user_id',
        'nrp',
        'full_name',
        'gender',
        'personnel_type',
        'rank_id',
        'golongan',
        'jabatan',
        'bagian',
        'keterangan',
        'satker_id',
        'phone',
        'avatar',
        'address',
        'religion',
        'is_active',
        'kapor_sizes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'kapor_sizes' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(Rank::class);
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(KaporSubmission::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Auto-scope to current user's satker if they are admin_satker.
     */
    public function scopeForCurrentSatker($query)
    {
        if (auth()->check() && auth()->user()->hasRole('admin_satker')) {
            return $query->where('satker_id', auth()->user()->satker_id);
        }

        return $query;
    }
}
