<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = $request->query('lang', 'en');
        $user = $request->user();

        $data = [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'slug' => $this->slug,
            'title' => $lang === 'ar' ? $this->title_ar : $this->title,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title,
            'content' => $lang === 'ar' ? $this->content_ar : $this->content,
            'content_ar' => $this->content_ar,
            'content_en' => $this->content,
            'meta_title' => $lang === 'ar' ? $this->meta_title_ar : $this->meta_title,
            'meta_title_ar' => $this->meta_title_ar,
            'meta_title_en' => $this->meta_title,
            'meta_description' => $lang === 'ar' ? $this->meta_description_ar : $this->meta_description,
            'meta_description_ar' => $this->meta_description_ar,
            'meta_description_en' => $this->meta_description,
            'keywords' => $this->getKeywords($lang),
            'keywords_en' => $this->getKeywords('en'),
            'keywords_ar' => $this->getKeywords('ar'),
            'is_published' => $this->is_published,
            'is_translated' => $this->is_translated,
            'is_home' => $this->is_home,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Include site data with favicon for all users (public pages need site info for favicon, etc.)
        // Use SiteResource to ensure consistent structure and favicon_url inclusion.
        // Call toArray() directly to avoid double { data: ... } wrapping when embedded in PageResource.
        // Check if site relationship is loaded before creating resource
        if ($this->relationLoaded('site') && $this->site) {
            $data['site'] = (new SiteResource($this->site))->toArray($request);
        } else {
            $data['site'] = null;
        }

        return $data;
    }

    /**
     * Get keywords for a specific language with backward compatibility.
     */
    private function getKeywords(string $lang): array
    {
        $keywords = $this->keywords;

        if (!is_array($keywords)) {
            return [];
        }

        // New format: ['en' => [...], 'ar' => [...]]
        if (isset($keywords['en']) || isset($keywords['ar'])) {
            return $keywords[$lang] ?? [];
        }

        // Old flat array format: treat as English
        if ($lang === 'en') {
            return $keywords;
        }

        return [];
    }
}
