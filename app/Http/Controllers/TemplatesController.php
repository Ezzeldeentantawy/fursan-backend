<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\TemplateResource;
use Illuminate\Http\Request;
use App\Models\Template;

class TemplatesController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => TemplateResource::collection(Template::latest()->get())
        ]);
    }

    // Create a new empty page
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|array',
            'type' => 'required|in:header,footer,block' // Strict validation
        ]);

        // If saving a header or footer, demote the current one to 'block'
        if (in_array($validated['type'], ['header', 'footer'])) {
            Template::where('type', $validated['type'])
                ->update(['type' => 'block']);
        }

        $template = Template::create($validated);

        return response()->json([
            'message' => 'Template created successfully',
            'data' => new TemplateResource($template),
        ], 201);
    }

    public function show(Template $template)
    {
        return response()->json([
            'data' => new TemplateResource($template),
        ]);
    }

    public function update(Request $request, Template $template)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'nullable|array',
            'type' => 'sometimes|in:header,footer,block',
            'is_published' => 'boolean'
        ]);

        // If updating a template to be a header/footer, demote the OLD one
        if (isset($validated['type']) && in_array($validated['type'], ['header', 'footer'])) {
            // Ensure we don't demote the one we are currently updating
            Template::where('type', $validated['type'])
                ->where('id', '!=', $template->id)
                ->update(['type' => 'block']);
        }

        $template->update($validated);

        return response()->json([
            'message' => 'Template updated successfully',
            'data' => new TemplateResource($template),
        ]);
    }

    // Remove a page
    public function destroy(Template $template)
    {
        $template->delete();
        return response()->json(['message' => 'Template moved to trash']);
    }
    public function getGlobals()
    {
        $header = Template::where('type', 'header')->first();
        $footer = Template::where('type', 'footer')->first();

        return response()->json([
            'header' => $header ? new TemplateResource($header) : null,
            'footer' => $footer ? new TemplateResource($footer) : null,
        ]);
    }

    /**
     * Get only the active header.
     */
    public function getHeader()
    {
        $header = Template::where('type', 'header')->first();
        
        if (!$header) {
            return response()->json(['message' => 'No active header found'], 404);
        }

        return response()->json([
            'data' => new TemplateResource($header),
        ]);
    }

    /**
     * Get only the active footer.
     */
    public function getFooter()
    {
        $footer = Template::where('type', 'footer')->first();

        if (!$footer) {
            return response()->json(['message' => 'No active footer found'], 404);
        }

        return response()->json([
            'data' => new TemplateResource($footer),
        ]);
    }
}
