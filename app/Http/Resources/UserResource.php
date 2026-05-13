<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'site_id' => $this->site_id,
            'site' => new SiteResource($this->whenLoaded('site')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
