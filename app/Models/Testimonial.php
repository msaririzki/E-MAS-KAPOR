<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'tutup_kepala' => 'Tutup Kepala',
        'tutup_badan' => 'Tutup Badan',
        'tutup_kaki' => 'Tutup Kaki',
    ];

    /** Cooldown period in days between testimonial submissions. */
    public const COOLDOWN_DAYS = 5;

    protected $fillable = ['user_id', 'category', 'message', 'rating'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the human-readable label for this testimonial's category.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
