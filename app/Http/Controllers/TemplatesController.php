<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Site;

class TemplatesController extends Controller
{
    /**
     * Resolve the site ID from request + authenticated user.
     */
    protected function getSiteId(Request $request): int
    {
        $user = auth()->user();
        $siteId = $request->input('site_id');

        if ($siteId) {
            $siteId = (int) $siteId;
            if (!$user->canAccessSite($siteId)) {
                abort(403, 'You are not authorized to access this site.');
            }
            return $siteId;
        }

        if ($user->site_id) {
            return $user->site_id;
        }

        if ($user->isAdmin()) {
            return Site::getDefaultSiteId();
        }

        abort(403, 'No site assigned to your account.');
    }

    /**
     * List templates, filtered by site_id.
     * Includes legacy templates with site_id = null.
     * Supports "All Sites" mode for super_admin when no site_id is provided.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Check if "all sites" is requested (admin only)
        $requestedSiteId = $request->input('site_id');
        $showAllSites = (!$requestedSiteId || $requestedSiteId === 'all') && $user->isAdmin();

        if ($showAllSites) {
            // Admin viewing all templates — no site filter
            $query = Template::with('site')->latest();
        } else {
            // Normal flow: get specific site ID and authorize
            $siteId = $this->getSiteId($request);

            $query = Template::with('site')
                ->where(function ($q) use ($siteId) {
                    $q->where('site_id', $siteId)
                      ->orWhereNull('site_id'); // Include legacy templates with no site
                });
        }

        // Optional type filter
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Optional search filter — search by title
        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $templates = $query->latest()->get();

        return response()->json([
            'data' => TemplateResource::collection($templates),
        ]);
    }

    /**
     * Get all templates for a specific site (used by frontend TemplatePicker).
     * Includes legacy templates with site_id = null.
     */
    public function bySite($siteId, Request $request)
    {
        $user = auth()->user();
        $siteId = (int) $siteId;

        if (!Site::where('id', $siteId)->exists()) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        if (!$user->canAccessSite($siteId)) {
            abort(403, 'Unauthorized.');
        }

        $templates = Template::where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)
              ->orWhereNull('site_id'); // Include legacy templates
        })->latest()->get();

        return response()->json([
            'data' => TemplateResource::collection($templates),
        ]);
    }

    /**
     * Create a new template.
     * site_id is optional — falls back to authenticated user's site.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id'  => 'sometimes|integer|exists:sites,id',
            'title'    => 'required|string|max:255',
            'content'  => 'nullable|array',
            'type'     => 'required|in:header,footer,block',
        ]);

        $user = auth()->user();

        // Infer site_id from user if not provided
        if (!isset($validated['site_id'])) {
            $validated['site_id'] = $user->site_id ?? Site::getDefaultSiteId();
        }

        if (!$user->canAccessSite((int) $validated['site_id'])) {
            abort(403, 'Unauthorized.');
        }

        // Enforce one header / one footer per site
        if (in_array($validated['type'], ['header', 'footer'])) {
            Template::where('site_id', $validated['site_id'])
                ->where('type', $validated['type'])
                ->update(['type' => 'block']);
        }

        $template = Template::create($validated);

        return response()->json([
            'message' => 'Template created successfully',
            'data'    => new TemplateResource($template->load('site')),
        ], 201);
    }

    /**
     * Show a single template.
     */
    public function show(Template $template)
    {
        $this->authorizeTemplateAccess($template);

        return response()->json([
            'data' => new TemplateResource($template->load('site')),
        ]);
    }

    /**
     * Update template metadata (title, type, site_id, is_published).
     */
    public function update(Request $request, Template $template)
    {
        $this->authorizeTemplateAccess($template);

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'content'      => 'nullable|array',
            'type'         => 'sometimes|in:header,footer,block',
            'site_id'      => 'sometimes|integer|exists:sites,id',
            'is_published' => 'boolean',
        ]);

        // Use the effective site_id for header/footer enforcement
        $effectiveSiteId = $validated['site_id'] ?? $template->site_id;

        // If changing type to header/footer, enforce one per site
        if (isset($validated['type']) && in_array($validated['type'], ['header', 'footer'])) {
            Template::where('site_id', $effectiveSiteId)
                ->where('type', $validated['type'])
                ->where('id', '!=', $template->id)
                ->update(['type' => 'block']);
        }

        $template->update($validated);

        return response()->json([
            'message' => 'Template updated successfully',
            'data'    => new TemplateResource($template->load('site')),
        ]);
    }

    /**
     * Update only the content (tree) of a template — used when saving from builder.
     */
    public function updateContent(Request $request, Template $template)
    {
        $this->authorizeTemplateAccess($template);

        $validated = $request->validate([
            'content' => 'required|array',
        ]);

        $template->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Template content updated successfully',
            'data'    => new TemplateResource($template),
        ]);
    }

    /**
     * Delete a template.
     */
    public function destroy(Template $template)
    {
        $this->authorizeTemplateAccess($template);

        $template->delete();

        return response()->json(['message' => 'Template moved to trash']);
    }

    /**
     * Get global elements (header + footer) for a site.
     * Includes legacy templates with site_id = null.
     */
    public function getGlobals(Request $request)
    {
        $siteId = $request->input('site_id', Site::getDefaultSiteId());

        if (!Site::where('id', $siteId)->exists()) {
            return response()->json(['message' => 'Site not found'], 404);
        }

        $header = Template::where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)->orWhereNull('site_id');
        })->where('type', 'header')->first();

        $footer = Template::where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)->orWhereNull('site_id');
        })->where('type', 'footer')->first();

        return response()->json([
            'header' => $header ? new TemplateResource($header) : null,
            'footer' => $footer ? new TemplateResource($footer) : null,
        ]);
    }

    /**
     * Get the active header for a site.
     */
    public function getHeader(Request $request)
    {
        $siteId = $request->input('site_id', Site::getDefaultSiteId());

        $header = Template::where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)->orWhereNull('site_id');
        })->where('type', 'header')->first();

        if (!$header) {
            return response()->json(['message' => 'No active header found'], 404);
        }

        return response()->json([
            'data' => new TemplateResource($header),
        ]);
    }

    /**
     * Get the active footer for a site.
     */
    public function getFooter(Request $request)
    {
        $siteId = $request->input('site_id', Site::getDefaultSiteId());

        $footer = Template::where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)->orWhereNull('site_id');
        })->where('type', 'footer')->first();

        if (!$footer) {
            return response()->json(['message' => 'No active footer found'], 404);
        }

        return response()->json([
            'data' => new TemplateResource($footer),
        ]);
    }

    /**
     * Authorize that the authenticated user can access this template's site.
     */
    protected function authorizeTemplateAccess(Template $template): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        if (!$user->canAccessSite($template->site_id)) {
            abort(403, 'You are not authorized to access this template.');
        }
    }
}
