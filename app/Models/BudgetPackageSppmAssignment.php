<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPackageSppmAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_package_id',
        'personnel_id',
        'original_satker_id',
        'sppm_satker_id',
        'assigned_by',
        'notes',
    ];

    public function budgetPackage(): BelongsTo
    {
        return $this->belongsTo(BudgetPackage::class);
    }

    public function personnel(): BelongsTo
    {
        return $this->belongsTo(Personnel::class);
    }

    public function originalSatker(): BelongsTo
    {
        return $this->belongsTo(Satker::class, 'original_satker_id');
    }

    public function sppmSatker(): BelongsTo
    {
        return $this->belongsTo(Satker::class, 'sppm_satker_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
