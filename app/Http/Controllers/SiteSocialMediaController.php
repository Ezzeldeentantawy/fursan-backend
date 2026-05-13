<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSiteSocialMediaRequest;
use App\Http\Resources\SiteSocialMediaResource;
use App\Models\Site;
use App\Models\SiteSocialMedia;
use Illuminate\Http\JsonResponse;

class SiteSocialMediaController extends Controller
{
    public function show(Site $site): SiteSocialMediaResource
    {
        $site->load('socialMedia');
        
        // Return empty structure if no social media exists yet
        $socialMedia = $site->socialMedia;
        if (!$socialMedia) {
            $socialMedia = SiteSocialMedia::create([
                'site_id' => $site->id,
                'social_links' => [],
            ]);
        }
        
        return new SiteSocialMediaResource($socialMedia);
    }

    public function update(UpdateSiteSocialMediaRequest $request, Site $site): SiteSocialMediaResource
    {
        $site->load('socialMedia');
        
        $socialMedia = $site->socialMedia;
        if (!$socialMedia) {
            $socialMedia = new SiteSocialMedia(['site_id' => $site->id]);
            $site->socialMedia()->save($socialMedia);
        }
        
        $socialMedia->update([
            'social_links' => $request->input('social_links'),
        ]);
        
        return new SiteSocialMediaResource($socialMedia->fresh());
    }
}
