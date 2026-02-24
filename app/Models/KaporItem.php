<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KaporItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'item_name',
        'description',
        'gender_specific',
        'rank_categories',
        'unit_keywords',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rank_categories' => 'array',
            'unit_keywords' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function sizes(): HasMany
    {
        return $this->hasMany(KaporSize::class)->orderBy('sort_order');
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
     * Filter items applicable for a specific personnel based on
     * gender, rank category, and unit (satker) keywords.
     */
    public function scopeForPersonnel($query, Personnel $personnel)
    {
        return $query
            ->where(function ($q) use ($personnel) {
            // Gender filter: null means all genders
            $q->whereNull('gender_specific')
                ->orWhere('gender_specific', $personnel->gender);
        })
            ->where(function ($q) use ($personnel) {
            // Rank category filter: null means all categories
            $q->whereNull('rank_categories')
                ->orWhereJsonContains('rank_categories', $personnel->rank->category);
        })
            ->where(function ($q) use ($personnel) {
            // Unit keyword filter: null means all units
            $q->whereNull('unit_keywords')
                ->orWhere(function ($sq) use ($personnel) {
                $satkerName = $personnel->satker->name;
                // Check if any keyword matches the satker name
                $sq->whereRaw('JSON_SEARCH(unit_keywords, "one", ?) IS NOT NULL', [
                    '%' . $satkerName . '%',
                ]);
            }
            );
        });
    }
}
