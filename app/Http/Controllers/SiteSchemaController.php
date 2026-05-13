<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteSchema;
use Illuminate\Http\Request;
use App\Http\Resources\SiteSchemaResource;

class SiteSchemaController extends Controller
{
    /**
     * Get schema for a specific site
     */
    public function index(Site $site)
    {
        $schemas = $site->schemas()->get();
        return SiteSchemaResource::collection($schemas);
    }
    
    /**
     * Get a specific schema type for a site
     */
    public function show(Site $site, $type)
    {
        $schema = $site->schemas()->where('type', $type)->firstOrFail();
        return new SiteSchemaResource($schema);
    }
    
    /**
     * Update or create schema for a site
     */
    public function update(Request $request, Site $site, $type)
    {
        $data = $request->validate([
            'data' => 'required|array',
        ]);
        
        $schema = $site->schemas()->updateOrCreate(
            ['type' => $type],
            ['data' => $data['data']]
        );
        
        return new SiteSchemaResource($schema);
    }
    
    /**
     * Delete a schema for a site
     */
    public function destroy(Site $site, $type)
    {
        $schema = $site->schemas()->where('type', $type)->firstOrFail();
        $schema->delete();
        
        return response()->noContent();
    }
}
