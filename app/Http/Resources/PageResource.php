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
            'keywords' => $this->keywords,
            'is_published' => $this->is_published,
            'is_translated' => $this->is_translated,
            'is_home' => $this->is_home,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        // Only include site_id and site_name for admin users or when specifically needed
        // This prevents leaking site information to unauthorized users
        if ($user && ($user->isAdmin() || $user->canAccessSite($this->site_id))) {
            $data['site_id'] = $this->site_id;
            $data['site_name'] = $this->whenLoaded('site', fn() => $this->site->name);
        }

        return $data;
    }
}
