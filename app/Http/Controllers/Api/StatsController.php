<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\Page;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    /**
     * Get dashboard stats for super_admin
     */
    public function index(): JsonResponse
    {
        // Debug: Log the authenticated user
        \Log::info('StatsController@index called', [
            'user' => auth()->user()?->email,
            'role' => auth()->user()?->role,
            'is_authenticated' => auth()->check(),
        ]);
        
        $this->authorizeSuperAdmin();
        
        // Get detailed page stats
        $publishedPagesCount = Page::where('is_published', true)->count();
        $draftPagesCount = Page::where('is_published', false)->count();
        $homePageCount = Page::where('is_home', true)->count();
        
        $stats = [
            'sites_count' => Site::count(),
            'pages_count' => Page::count(),
            'published_pages_count' => $publishedPagesCount,
            'draft_pages_count' => $draftPagesCount,
            'home_pages_count' => $homePageCount,
            'media_count' => Media::count(),
            'users_count' => User::count(),
            'super_admins_count' => User::where('role', 'super_admin')->count(),
            'site_admins_count' => User::where('role', 'site_admin')->count(),
        ];
        
        \Log::info('Stats returned:', $stats);
        
        return response()->json([
            'data' => $stats
        ]);
    }
    
    /**
     * Get stats for site_admin (only their site's data)
     * Also accessible by super_admin (returns their site's stats if site_id is set)
     */
    public function siteStats(): JsonResponse
    {
        $user = auth()->user();
        
        // Allow both site_admin and super_admin
        if (!$user->isSiteAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
        
        // Get site-specific page stats
        $siteId = $user->site_id;
        
        // If no site_id assigned
        if (!$siteId) {
            \Log::warning('User has no site_id', ['user' => $user->email, 'role' => $user->role]);
            
            // For super_admin without site_id, they should use /api/v1/stats instead
            // But if they call this endpoint, return 0 counts with appropriate message
            return response()->json([
                'data' => [
                    'pages_count' => 0,
                    'published_pages_count' => 0,
                    'draft_pages_count' => 0,
                    'media_count' => Media::count(),
                    'site_name' => $user->isSuperAdmin() ? 'Super Admin (Use /stats endpoint)' : 'No Site Assigned',
                ]
            ]);
        }
        
        $publishedPagesCount = Page::where('site_id', $siteId)->where('is_published', true)->count();
        $draftPagesCount = Page::where('site_id', $siteId)->where('is_published', false)->count();
        
        return response()->json([
            'data' => [
                'pages_count' => Page::where('site_id', $siteId)->count(),
                'published_pages_count' => $publishedPagesCount,
                'draft_pages_count' => $draftPagesCount,
                'media_count' => Media::count(), // Media is global, not site-specific
                'site_name' => $user->site?->name ?? 'Unknown Site',
            ]
        ]);
    }
    
    /**
     * Check if current user is super admin
     */
    protected function authorizeSuperAdmin(): void
    {
        if (!auth()->user() || !auth()->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can view these stats');
        }
    }
}
