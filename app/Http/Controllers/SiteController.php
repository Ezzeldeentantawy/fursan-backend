<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SiteController extends Controller
{
    /**
     * List all sites (admin only).
     * Note: Route middleware 'role:admin' already protects this endpoint,
     * but we add defensive check here as well.
     */
    public function index(): AnonymousResourceCollection
    {
        // Defensive check - should already be handled by middleware
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
        
        // Log access to sites list for audit trail
        \Log::info('Sites list accessed', [
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
        ]);
        
        // Only show non-deleted sites by default (withTrashed() for admin to see deleted)
        return SiteResource::collection(
            Site::orderBy('created_at', 'desc')->paginate(20)
        );
    }

    /**
     * Create a new site (admin only).
     */
    public function store(StoreSiteRequest $request): JsonResponse
    {
        // Defensive check - should already be handled by middleware
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validated();

        $site = Site::create($validated);

        // Log site creation for audit trail
        \Log::info('Site created', [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'created_by' => auth()->id(),
        ]);

        return (new SiteResource($site))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Return a single site for editing.
     */
    public function edit($id): JsonResponse
    {
        // Authorization check - only admin can edit sites
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
        
        $site = Site::findOrFail($id);
        return response()->json(['data' => new SiteResource($site)]);
    }

    /**
     * Update an existing site.
     */
    public function update(UpdateSiteRequest $request, $id): JsonResponse
    {
        // Authorization check - only admin can update sites
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
        
        $site = Site::findOrFail($id);
        $validated = $request->validated();
        $site->update($validated);

        // Log the site update for audit trail
        \Log::info('Site updated', [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'updated_by' => auth()->id(),
            'changes' => $validated,
        ]);

        return response()->json([
            'message' => 'Site updated successfully',
            'data' => new SiteResource($site)
        ]);
    }

    /**
     * Delete a site (soft delete - can be restored).
     */
    public function destroy($id): JsonResponse
    {
        // Authorization check - only admin can delete sites
        if (!auth()->user() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
        
        $site = Site::findOrFail($id);
        
        // Check if site has pages and log warning
        $pagesCount = $site->pages()->count();
        
        // Log the site deletion for audit trail (BEFORE deletion for data integrity)
        \Log::warning('Site soft deleted', [
            'site_id' => $site->id,
            'site_name' => $site->name,
            'site_domain' => $site->domain,
            'deleted_by' => auth()->id(),
            'pages_affected_count' => $pagesCount,
        ]);
        
        // Soft delete - site can be restored if needed
        $site->delete();

        return response()->json([
            'message' => 'Site moved to trash successfully. It can be restored by an administrator if needed.',
            'pages_affected' => $pagesCount,
        ]);
    }
}
