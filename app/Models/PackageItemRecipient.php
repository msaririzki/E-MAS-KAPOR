<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItemRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_item_id',
        'satker_id',
        'recipient_filters',
        'matched_count',
    ];

    protected function casts(): array
    {
        return [
            'recipient_filters' => 'array',
        ];
    }

    // ── Relationships ─────────────────────────────────────────

    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(PackageItem::class);
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    // ── Methods ───────────────────────────────────────────────

    /**
     * Hitung jumlah personil yang cocok dengan filter di satker terkait
     */
    public function calculateMatchedCount(): int
    {
        $query = Personnel::where('satker_id', $this->satker_id)
            ->where('is_active', true);

        $filters = $this->recipient_filters ?? [];

        // Filter berdasarkan personnel_type (Polri/PNS)
        // DB stores 'Polri' / 'PNS', filter may come as lowercase
        if (!empty($filters['personnel_type'])) {
            $mappedTypes = array_map(function ($t) {
                $lower = strtolower($t);
                if ($lower === 'polri') return 'Polri';
                if ($lower === 'pns') return 'PNS';
                if ($lower === 'pppk') return 'PPPK';
                return $t;
            }, $filters['personnel_type']);
            $query->whereIn('personnel_type', $mappedTypes);
        }

        // Filter berdasarkan gender (L/P)
        if (!empty($filters['gender'])) {
            $query->whereIn('gender', $filters['gender']);
        }

        // Filter berdasarkan rank categories
        if (!empty($filters['rank_categories'])) {
            $query->whereHas('rank', function ($q) use ($filters) {
                $q->whereIn('category', $filters['rank_categories']);
            });
        }

        // Filter berdasarkan keterangan
        if (!empty($filters['keterangan'])) {
            $query->whereIn('keterangan', $filters['keterangan']);
        }

        $count = $query->count();
        $this->update(['matched_count' => $count]);

        return $count;
    }
}
