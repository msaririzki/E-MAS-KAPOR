<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSetting extends Model
{
    protected $fillable = [
        'signatory_name',
        'signatory_rank',
        'signatory_nrp',
        'signatory_title',
        'location',
        'organization_name',
        'header_title',
        'work_type',
    ];

    /**
     * Get the singleton settings instance
     */
    public static function getSettings(): self
    {
        return static::first() ?? static::create([]);
    }
}
