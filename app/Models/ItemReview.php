<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemReview extends Model
{
    use HasFactory;

    public const STATUS_REVIEWED = "reviewed";

    public const STATUS_NOT_RECEIVED = "belum_menerima";

    public const RESPONSE_STATUSES = [
        self::STATUS_REVIEWED => "Diterima",
        self::STATUS_NOT_RECEIVED => "Belum menerima item",
    ];

    protected $appends = [
        "category_label",
        "display_message",
        "item_name_snapshot",
        "package_name_snapshot",
    ];

    protected $fillable = [
        "personnel_item_allocation_id",
        "user_id",
        "personnel_id",
        "kapor_item_id",
        "fiscal_year",
        "response_status",
        "rating",
        "comment",
        "submitted_at",
    ];

    protected function casts(): array
    {
        return [
            "submitted_at" => "datetime",
        ];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(
            PersonnelItemAllocation::class,
            "personnel_item_allocation_id",
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function kaporItem(): BelongsTo
    {
        return $this->belongsTo(KaporItem::class);
    }

    public function getResponseLabelAttribute(): string
    {
        return self::RESPONSE_STATUSES[$this->response_status] ??
            ucfirst(str_replace("_", " ", $this->response_status));
    }

    public function getCategoryLabelAttribute(): string
    {
        $category =
            $this->allocation?->item_category_snapshot ??
            ($this->kaporItem?->category ?? "Tanpa kategori");

        return str_replace("_", " ", $category);
    }

    public function getItemNameSnapshotAttribute(): string
    {
        return $this->allocation?->kapor_item_name_snapshot ??
            ($this->kaporItem?->item_name ?? "Item Kapor");
    }

    public function getPackageNameSnapshotAttribute(): ?string
    {
        return $this->allocation?->budget_package_name_snapshot;
    }

    public function getDisplayMessageAttribute(): string
    {
        if (filled($this->comment)) {
            return $this->comment;
        }

        return $this->response_status === self::STATUS_NOT_RECEIVED
            ? "Personil melaporkan item ini belum diterima."
            : "Tidak ada catatan tambahan.";
    }
}
