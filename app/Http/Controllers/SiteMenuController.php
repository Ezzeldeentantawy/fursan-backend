<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSiteMenuRequest;
use App\Http\Resources\SiteMenuResource;
use App\Models\Site;
use App\Models\SiteMenu;
use Illuminate\Http\JsonResponse;

class SiteMenuController extends Controller
{
    public function show(Site $site): SiteMenuResource
    {
        $site->load('menus');
        
        // Return empty structure if no menus exist yet
        $menus = $site->menus;
        if (!$menus) {
            $menus = SiteMenu::create([
                'site_id' => $site->id,
                'menus' => [],
            ]);
        }
        
        return new SiteMenuResource($menus);
    }

    public function update(UpdateSiteMenuRequest $request, Site $site): SiteMenuResource
    {
        $site->load('menus');
        
        $menus = $site->menus;
        if (!$menus) {
            $menus = new SiteMenu(['site_id' => $site->id]);
            $site->menus()->save($menus);
        }
        
        $menus->update([
            'menus' => $request->input('menus'),
        ]);
        
        return new SiteMenuResource($menus->fresh());
    }
}
