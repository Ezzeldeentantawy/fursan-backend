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
        'is_default',
        'is_active',
        'global_css',
        'favicon_media_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $dates = [
        'deleted_at',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($site) {
            if ($site->is_default) {
                static::where('is_default', true)
                    ->where('id', '!=', $site->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function schemas(): HasMany
    {
        return $this->hasMany(SiteSchema::class);
    }

    public function favicon()
    {
        return $this->belongsTo(Media::class, 'favicon_media_id');
    }

    public function menus()
    {
        return $this->hasOne(SiteMenu::class);
    }

    public function socialMedia()
    {
        return $this->hasOne(SiteSocialMedia::class);
    }

    /**
     * Scope a query to only include the default site.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include active sites.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default site ID.
     * Falls back to ID 1 if no default is set.
     */
    public static function getDefaultSiteId(): int
    {
        $defaultSite = static::where('is_default', true)->first();
        return $defaultSite ? $defaultSite->id : 1;
    }
}
