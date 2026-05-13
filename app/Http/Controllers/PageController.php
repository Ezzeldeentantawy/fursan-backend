<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Models\Setting;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Get the site ID from request or default to user's site or site_id=1
     * SECURITY: Validates that user can access the requested site
     */
    protected function getSiteId(Request $request): int
    {
        $user = auth()->user();
        
        // First check if site_id is provided in request
        $siteId = $request->input('site_id');
        
        // If site_id provided in request, validate user can access it
        if ($siteId) {
            $siteId = (int) $siteId;
            
            // Validate user has access to this site
            if (!$user->canAccessSite($siteId)) {
                abort(403, 'You are not authorized to access this site.');
            }
            
            return $siteId;
        }

        // Use user's assigned site if available
        if ($user->site_id) {
            return $user->site_id;
        }

        // Default to the configured default site for backward compatibility (admin only)
        if ($user->isAdmin()) {
            return Site::getDefaultSiteId();
        }

        abort(403, 'No site assigned to your account. Please contact administrator.');
    }

    /**
     * Get site ID from request for PUBLIC render endpoints (no auth required)
     * Only resolves site by domain, does NOT check user authorization
     * Used for path-based multi-tenancy in render endpoints
     */
    protected function getSiteIdFromRequestPublic(Request $request): int
    {
        // Check for ?site=domain parameter first (path-based multi-tenancy)
        $siteIdentifier = $request->query('site'); // e.g., "webcom", "hello"
        
        if ($siteIdentifier) {
            // Validate site identifier to prevent injection
            if (!is_string($siteIdentifier) || strlen($siteIdentifier) > 255) {
                abort(400, 'Invalid site parameter.');
            }
            
            // Look up site by domain column (Eloquent uses parameter binding - safe from SQL injection)
            $site = Site::where('domain', $siteIdentifier)
                ->where('is_active', true) // Only allow active sites
                ->first();
            
            if ($site) {
                return $site->id;
            }
            
            // Site not found - don't echo back the user input to prevent XSS
            abort(404, 'Site not found.');
        }
        
        // For public endpoints without site param, use the configured default site
        $defaultSiteId = Site::getDefaultSiteId();
        $defaultSite = Site::where('id', $defaultSiteId)->where('is_active', true)->first();
        if ($defaultSite) {
            return $defaultSite->id;
        }

        // Fallback to any active site
        $fallbackSite = Site::where('is_active', true)->first();
        if ($fallbackSite) {
            return $fallbackSite->id;
        }

        abort(404, 'No active site found.');
    }

    /**
     * Get site ID from request, supporting both ?site=domain and ?site_id=id parameters
     * Priority: 1. ?site=domain (look up by domain column), 2. ?site_id=id, 3. default logic
     * Used for authenticated endpoints - includes authorization check
     */
    protected function getSiteIdFromRequest(Request $request): int
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401, 'Authentication required.');
        }
        
        // Check for ?site=domain parameter first (path-based multi-tenancy)
        $siteIdentifier = $request->query('site'); // e.g., "webcom", "hello"
        
        if ($siteIdentifier) {
            // Validate site identifier to prevent injection
            if (!is_string($siteIdentifier) || strlen($siteIdentifier) > 255) {
                abort(400, 'Invalid site parameter.');
            }
            
            // Look up site by domain column
            $site = Site::where('domain', $siteIdentifier)->first();
            
            if ($site) {
                // Validate user has access to this site
                if (!$user->canAccessSite($site->id)) {
                    abort(403, 'You are not authorized to access this site.');
                }
                return $site->id;
            }
            
            // Site not found - don't echo back user input
            abort(404, 'Site not found.');
        }
        
        // Fallback to existing getSiteId logic (?site_id=id or default)
        return $this->getSiteId($request);
    }

    /**
     * Validate that the site exists
     */
    protected function validateSite(int $siteId): void
    {
        if (!Site::where('id', $siteId)->exists()) {
            abort(422, 'The selected site does not exist.');
        }
    }

    /**
     * Check if user is authorized to access/manage pages for the given site
     */
    protected function authorizeSiteAccess(int $siteId): void
    {
        $user = auth()->user();

        // Admin can access any site
        if ($user->isAdmin()) {
            return;
        }

        // Check if user has access to this site
        if (!$user->canAccessSite($siteId)) {
            abort(403, 'You are not authorized to access this site.');
        }
    }

    /**
     * Check if user is authorized to modify the page (update/delete)
     */
    protected function authorizePageModification(Page $page): void
    {
        $user = auth()->user();

        // Admin can modify any page
        if ($user->isAdmin()) {
            return;
        }

        // Check if user can access the page's site
        if (!$user->canAccessSite($page->site_id)) {
            abort(403, 'You are not authorized to modify this page.');
        }
    }

    // 1. List all pages: Keep this light! Don't fetch the massive content columns here.
    public function index(Request $request)
    {
        $user = auth()->user();
        $lang = $request->query('lang', 'en');

        // Check if "all sites" is requested (admin only)
        $requestedSiteId = $request->input('site_id');
        $showAllSites = (!$requestedSiteId || $requestedSiteId === 'all') && $user->isAdmin();

        if ($showAllSites) {
            // Log admin access to all sites pages (security audit)
            \Log::info('Admin accessed all sites pages', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Admin viewing all sites - no site filter applied
            $query = Page::with('site')
                ->select('id', 'title', 'title_ar', 'slug', 'is_published', 'is_translated', 'created_at', 'updated_at', 'is_home', 'site_id')
                ->latest();
        } else {
            // Normal flow: get specific site ID and authorize
            $siteId = $this->getSiteId($request);
            $this->validateSite($siteId);
            $this->authorizeSiteAccess($siteId);

            $query = Page::with('site')
                ->forSite($siteId)
                ->select('id', 'title', 'title_ar', 'slug', 'is_published', 'is_translated', 'created_at', 'updated_at', 'is_home', 'site_id')
                ->latest();
        }

        if ($lang === 'ar') {
            $query->where('is_translated', true);
        }

        // Optional search filter — search by title or meta data
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('title_ar', 'like', "%{$search}%")
                  ->orWhere('meta_title', 'like', "%{$search}%")
                  ->orWhere('meta_title_ar', 'like', "%{$search}%")
                  ->orWhere('meta_description', 'like', "%{$search}%")
                  ->orWhere('meta_description_ar', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'data' => PageResource::collection($query->get())->toArray($request),
            'lang' => $lang,
            'site_id' => $showAllSites ? 'all' : ($siteId ?? null),
        ]);
    }

    public function getBySlug($slug, Request $request)
    {
        // Use path-based multi-tenancy: supports ?site=domain parameter
        // Public endpoint - uses method that doesn't require auth
        $siteId = $this->getSiteIdFromRequestPublic($request);
        $this->validateSite($siteId);

        $lang = $request->query('lang', 'en');

        // Handle home slug variations: 'home', '/', or empty
        if ($slug === '/' || $slug === 'home' || empty($slug)) {
            return $this->getHomePage($request);
        }

        $page = Page::forSite($siteId)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with('site.favicon')
            ->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $siteUrl = Setting::where('key', 'site_url')->value('value')
            ?? env('APP_URL', 'http://localhost');

        $pageUrl = rtrim($siteUrl, '/') . '/' . $page->slug;

        $webPageSchema = [
            "@context" => "https://schema.org",
            "@type" => "WebPage",
            "@id" => $pageUrl . "/#webpage",
            "url" => $pageUrl,
            "name" => $lang === 'ar' ? $page->meta_title_ar : $page->meta_title,
            "description" => $lang === 'ar' ? $page->meta_description_ar : $page->meta_description,
            "isPartOf" => [
                "@id" => $siteUrl . "/#website"
            ],
            "about" => [
                "@id" => $siteUrl . "/#organization"
            ]
        ];

        return response()->json([
            'data' => (new PageResource($page))->toArray($request),
            // Don't expose internal domain identifiers to prevent site enumeration
            'schema' => $webPageSchema,
        ]);
    }

    public function getHomePage(Request $request)
    {
        // Use path-based multi-tenancy: supports ?site=domain parameter
        // Public endpoint - uses method that doesn't require auth
        $siteId = $this->getSiteIdFromRequestPublic($request);
        $this->validateSite($siteId);

        $lang = $request->query('lang', 'en');

        $page = Page::forSite($siteId)
            ->where('is_home', true)
            ->where('is_published', true)
            ->with('site.favicon')
            ->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $siteUrl = Setting::where('key', 'site_url')->value('value')
            ?? env('APP_URL', 'http://localhost');

        $pageUrl = rtrim($siteUrl, '/') . '/' . $page->slug;

        $webPageSchema = [
            "@context" => "https://schema.org",
            "@type" => "WebPage",
            "@id" => $pageUrl . "/#webpage",
            "url" => $pageUrl,
            "name" => $lang === 'ar' ? $page->meta_title_ar : $page->meta_title,
            "description" => $lang === 'ar' ? $page->meta_description_ar : $page->meta_description,
            "isPartOf" => [
                "@id" => $siteUrl . "/#website"
            ],
            "about" => [
                "@id" => $siteUrl . "/#organization"
            ]
        ];

        return response()->json([
            'data' => (new PageResource($page))->toArray($request),
            // Don't expose internal domain identifiers to prevent site enumeration
            'schema' => $webPageSchema,
        ]);
    }

    // 2. Create: Initialize with English title and generate a slug
    public function store(StorePageRequest $request)
    {
        $validated = $request->validated();

        // Ensure site_id is set and authorized
        if (!isset($validated['site_id'])) {
            $validated['site_id'] = $this->getSiteId($request);
        }

        $this->validateSite($validated['site_id']);
        $this->authorizeSiteAccess($validated['site_id']);

        // Auto-generate slug if not provided
        if (empty($validated['slug']) && !empty($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Create the page
        $page = Page::create($validated);

        return response()->json([
            'message' => 'Page created successfully',
            'data' => (new PageResource($page))->toArray($request),
        ], 201);
    }

    // 3. Show: Fetch based on a language query parameter (?lang=ar)
    public function show(Page $page, Request $request)
    {
        $siteId = $this->getSiteId($request);
        $this->validateSite($siteId);

        // Allow access if: page belongs to requested site OR user is admin
        if ($page->site_id !== $siteId && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $this->authorizePageModification($page);

        // Eager load the site relationship so PageResource includes site data (site.id is needed by the builder to fetch menus, templates, etc.)
        $page->load('site');

        return response()->json([
            'data' => (new PageResource($page))->toArray($request),
        ]);
    }

    // 4. Update: Save only the data for the language being edited
    public function update(UpdatePageRequest $request, Page $page)
    {
        $lang = $request->query('lang', 'en');
        $validated = $request->validated();

        // Get the target site_id (either from request or existing page)
        $targetSiteId = $validated['site_id'] ?? $page->site_id;

        // Validate the target site exists
        $this->validateSite($targetSiteId);

        // Check user authorization for the target site
        $this->authorizeSiteAccess($targetSiteId);

        // If site_id is being changed, also verify access to the OLD site
        if (isset($validated['site_id']) && $validated['site_id'] !== $page->site_id) {
            // User must have access to BOTH sites to move a page
            $this->authorizeSiteAccess($page->site_id);
            
            // Log this security-sensitive action
            \Log::info('Page site_id changed', [
                'page_id' => $page->id,
                'old_site_id' => $page->site_id,
                'new_site_id' => $validated['site_id'],
                'user_id' => auth()->id(),
            ]);
        }

        // If we saved Arabic content, automatically mark as translated
        if ($lang === 'ar' && isset($validated['content_ar'])) {
            $validated['is_translated'] = true;
        }

        $page->update($validated);

        return response()->json([
            'message' => 'Page updated successfully',
            'data' => (new PageResource($page))->toArray($request),
        ]);
    }

    public function destroy(Page $page)
    {
        $siteId = $this->getSiteId(request());
        $this->validateSite($siteId);

        // Allow deletion if: page belongs to requested site OR user is admin
        if ($page->site_id !== $siteId && !auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $this->authorizePageModification($page);

        $page->delete();

        return response()->json(['message' => 'Page moved to trash'], 200);
    }

    public function translateArabic($id, Request $request)
    {
        $siteId = $this->getSiteId($request);
        $this->validateSite($siteId);

        $page = Page::forSite($siteId)->find($id);

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $this->authorizePageModification($page);

        $page->update([
            'title_ar' => $page->title,
            'content_ar' => $page->content,
            'meta_title_ar' => $page->meta_title,
            'meta_description_ar' => $page->meta_description,
            'is_translated' => true,
        ]);

        return response()->json([
            'message' => 'Arabic translation initialized from English',
            'data' => (new PageResource($page))->toArray($request),
        ]);
    }

    public function setHomePage($id, Request $request)
    {
        $siteId = $this->getSiteId($request);
        $this->validateSite($siteId);
        $this->authorizeSiteAccess($siteId);

        Page::forSite($siteId)->update(['is_home' => false]);
        $page = Page::forSite($siteId)->find($id);

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        $this->authorizePageModification($page);

        $page->update(['is_home' => true]);

        return response()->json([
            'message' => 'Home page set successfully',
            'data' => (new PageResource($page))->toArray($request),
        ]);
    }
}
