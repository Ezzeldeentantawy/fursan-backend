<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function store(Request $request)
    {
        // Check if the file actually exists in the request first
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded'], 422);
        }

        $request->validate([
            // added mimes to ensure they are actually images/docs
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf,zip|max:20480'
        ]);

        $file = $request->file('file');

        // Use 'public' disk. It stores in storage/app/public/uploads
        $path = $file->store('uploads', 'public');

        $media = Media::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => asset('storage/' . $path),
            'file_name' => $media->file_name // Useful for the UI label
        ]);
    }
    public function index()
    {
        return Media::latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'url' => asset('storage/' . $item->file_path),
                'type' => $item->file_type
            ];
        });
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
