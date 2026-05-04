<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'site_id',
        'title',
        'title_ar',
        'slug',
        'content',
        'content_ar',
        'meta_title',
        'meta_title_ar',
        'meta_description',
        'meta_description_ar',
        'keywords',
        'is_published',
        'is_translated',
        'is_home',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeForSite($query, $siteId = 1)
    {
        return $query->where('site_id', $siteId);
    }

    // This converts JSON columns to PHP arrays automatically
    protected $casts = [
        'content' => 'array',
        'content_ar' => 'array',
        'keywords' => 'array',
        'is_published' => 'boolean',
        'is_translated' => 'boolean',
        'is_home' => 'boolean',
    ];

    /**
     * Sanitize HTML content recursively
     */
    protected function sanitizeHtmlContent($content)
    {
        if (!is_array($content)) {
            return $content;
        }

        $htmlFields = ['html', 'content', 'text', 'heading', 'paragraph'];

        array_walk_recursive($content, function (&$value, $key) use ($htmlFields) {
            if (is_string($value) && in_array($key, $htmlFields)) {
                $value = Purifier::clean($value);
            }
        });

        return $content;
    }

    /**
     * Auto-generate slug from title if not provided
     * Sanitize content before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (empty($page->slug)) {
                $page->slug = Str::slug($page->title);
            }

            // Sanitize content
            if (!empty($page->content)) {
                $page->content = $page->sanitizeHtmlContent($page->content);
            }
            if (!empty($page->content_ar)) {
                $page->content_ar = $page->sanitizeHtmlContent($page->content_ar);
            }
        });

        static::updating(function ($page) {
            // Sanitize content on update
            if ($page->isDirty('content') && !empty($page->content)) {
                $page->content = $page->sanitizeHtmlContent($page->content);
            }
            if ($page->isDirty('content_ar') && !empty($page->content_ar)) {
                $page->content_ar = $page->sanitizeHtmlContent($page->content_ar);
            }
        });
    }
}
