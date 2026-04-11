<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonnelItemAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_package_id',
        'package_item_id',
        'kapor_item_id',
        'user_id',
        'personnel_id',
        'satker_id',
        'fiscal_year',
        'allocation_status',
        'allocated_at',
        'nrp_snapshot',
        'full_name_snapshot',
        'satker_name_snapshot',
        'kapor_item_name_snapshot',
        'item_category_snapshot',
        'budget_package_name_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'allocated_at' => 'datetime',
        ];
    }

    public function budgetPackage(): BelongsTo
    {
        return $this->belongsTo(BudgetPackage::class);
    }

    public function packageItem(): BelongsTo
    {
        return $this->belongsTo(PackageItem::class);
    }

    public function kaporItem(): BelongsTo
    {
        return $this->belongsTo(KaporItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ItemReview::class, 'personnel_item_allocation_id');
    }
}
