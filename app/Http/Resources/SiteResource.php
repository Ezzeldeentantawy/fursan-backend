<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Http\Resources\SiteMenuResource;
use App\Http\Resources\SiteSocialMediaResource;

class SiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'domain' => $this->domain,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'favicon_url' => $this->whenLoaded('favicon', fn() => $this->favicon ? url('storage/' . $this->favicon->file_path) : null),
            'menus' => $this->whenLoaded('menus', fn() => new SiteMenuResource($this->menus)),
            'social_media' => $this->whenLoaded('socialMedia', fn() => new SiteSocialMediaResource($this->socialMedia)),
            'pages_count' => $this->whenLoaded('pages', fn() => $this->pages->count()),
            'global_css' => $this->global_css,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
