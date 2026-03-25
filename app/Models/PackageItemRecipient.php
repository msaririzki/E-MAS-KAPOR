<?php

namespace App\Models;

use App\Services\KaporRequirementService;
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

        app(KaporRequirementService::class)->applyRecipientFilters(
            $query,
            $this->recipient_filters ?? [],
            $this->satker
        );

        $count = $query->count();
        $this->update(['matched_count' => $count]);

        return $count;
    }
}
