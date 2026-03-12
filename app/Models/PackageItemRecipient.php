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
     * Auto-deteksi gender dari nama item kapor.
     * Mengembalikan 'L', 'P', atau null (tidak spesifik gender).
     */
    public static function detectGenderFromItemName(string $itemName): ?string
    {
        $upper = strtoupper($itemName);

        // Kata kunci WANITA/PEREMPUAN dulu agar lebih spesifik
        if (str_contains($upper, 'WANITA') || str_contains($upper, 'PEREMPUAN')) {
            return 'P';
        }

        if (str_contains($upper, 'PRIA') || str_contains($upper, 'LAKI')) {
            return 'L';
        }

        return null; // item unisex (sepatu olahraga, kaos kaki, rompi, dll)
    }

    /**
     * Hitung jumlah personil yang cocok dengan filter di satker terkait.
     * Gender auto-detected dari nama item jika tidak diset di filter.
     */
    public function calculateMatchedCount(): int
    {
        $this->load('packageItem.kaporItem');

        $query = Personnel::where('satker_id', $this->satker_id)
            ->where('is_active', true);

        $filters = $this->recipient_filters ?? [];

        // Filter berdasarkan personnel_type (Polri/PNS)
        if (! empty($filters['personnel_type'])) {
            $mappedTypes = array_map(function ($t) {
                $lower = strtolower($t);
                if ($lower === 'polri') {
                    return 'Polri';
                }
                if ($lower === 'pns') {
                    return 'PNS';
                }
                if ($lower === 'pppk') {
                    return 'PPPK';
                }

                return $t;
            }, $filters['personnel_type']);
            $query->whereIn('personnel_type', $mappedTypes);
        }

        // Filter gender: hanya dari filter eksplisit user, tidak auto-detect dari nama item
        if (! empty($filters['gender'])) {
            $query->whereIn('gender', $filters['gender']);
        }

        // Filter berdasarkan rank categories
        if (! empty($filters['rank_categories'])) {
            $query->whereHas('rank', function ($q) use ($filters) {
                $q->whereIn('category', $filters['rank_categories']);
            });
        }

        // Filter berdasarkan keterangan
        if (! empty($filters['keterangan'])) {
            $query->whereIn('keterangan', $filters['keterangan']);
        }

        // Filter berdasarkan golongan PNS/PPPK
        if (! empty($filters['golongan'])) {
            $query->whereIn('golongan', $filters['golongan']);
        }

        $count = $query->count();
        $this->update(['matched_count' => $count]);

        return $count;
    }
}
