<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteGlobalCssController extends Controller
{
    /**
     * Get global CSS for a site (public endpoint).
     */
    public function show(Site $site): JsonResponse
    {
        return response()->json([
            'global_css' => $site->global_css,
        ]);
    }

    /**
     * Update global CSS for a site (admin only).
     */
    public function update(Request $request, Site $site): JsonResponse
    {
        $validated = $request->validate([
            'global_css' => 'nullable|string',
        ]);

        $site->update([
            'global_css' => $validated['global_css'] ?? null,
        ]);

        return response()->json([
            'message' => 'Global CSS updated successfully',
            'global_css' => $site->fresh()->global_css,
        ]);
    }
}
