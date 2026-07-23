<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentBatch extends Model
{
    use HasFactory;

    public const GROUPS = [
        'TAMTAMA' => 'Tamtama',
        'BINTARA' => 'Bintara',
        'PAMA' => 'Perwira Pertama (Pama)',
        'PAMEN' => 'Perwira Menengah (Pamen)',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'code',
        'name',
        'fiscal_year',
        'satker_id',
        'procurement_group',
        'default_rank_id',
        'default_jabatan',
        'default_bagian',
        'requested_male_count',
        'requested_female_count',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'requested_male_count' => 'integer',
            'requested_female_count' => 'integer',
        ];
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function defaultRank(): BelongsTo
    {
        return $this->belongsTo(Rank::class, 'default_rank_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Personnel::class, 'student_batch_id');
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
}
