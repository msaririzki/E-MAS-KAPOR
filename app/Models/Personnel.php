<?php

namespace App\Models;

use App\Support\GolonganNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Personnel extends Model
{
    use HasFactory;

    protected $table = 'personnels';

    protected $appends = [
        'whatsapp_link',
    ];

    protected $fillable = [
        'user_id',
        'student_batch_id',
        'student_code',
        'nrp',
        'full_name',
        'gender',
        'personnel_type',
        'procurement_group',
        'rank_id',
        'golongan',
        'jabatan',
        'bagian',
        'keterangan',
        'keterangan_2',
        'keterangan_3',
        'keterangan_4',
        'satker_id',
        'phone',
        'avatar',
        'address',
        'religion',
        'is_active',
        'verification_status',

        'nrp_issue_note',
        'nrp_issue_resolved_at',
        'kapor_sizes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'verification_status' => 'string',
            'nrp_issue_resolved_at' => 'datetime',
            'kapor_sizes' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentBatch(): BelongsTo
    {
        return $this->belongsTo(StudentBatch::class);
    }

    public function isStudentRecord(): bool
    {
        return $this->student_batch_id !== null;
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

    public function itemAllocations(): HasMany
    {
        return $this->hasMany(PersonnelItemAllocation::class);
    }

    public function itemReviews(): HasMany
    {
        return $this->hasMany(ItemReview::class);
    }

    public function sppmAssignments(): HasMany
    {
        return $this->hasMany(BudgetPackageSppmAssignment::class);
    }

    public function getWhatsappLinkAttribute(): ?string
    {
        return User::buildWhatsappLink($this->phone ?: $this->user?->phone);
    }

    public function setGolonganAttribute(mixed $value): void
    {
        $rawValue = trim((string) $value);

        $this->attributes['golongan'] = GolonganNormalizer::major($rawValue) ?? ($rawValue !== '' ? $rawValue : null);
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
