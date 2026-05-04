<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'domain',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Scope a query to only include active sites.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
