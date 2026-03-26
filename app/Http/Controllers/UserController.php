<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /user/profile  — return authenticated user's profile
    // ─────────────────────────────────────────────────
    public function profile(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /user/profile  — update name, phone, avatar, cv
    // ─────────────────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'   => 'sometimes|string|max:255',
            'phone'  => 'sometimes|nullable|string|max:30',
            'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'cv'     => 'sometimes|nullable|file|mimes:pdf,doc,docx|max:5120', // max 5 MB
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if it exists
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        // Handle CV upload
        if ($request->hasFile('cv')) {
            // Delete old CV if it exists
            if ($user->cv) {
                Storage::disk('public')->delete($user->cv);
            }
            $validated['cv'] = $request->file('cv')->store('cvs', 'public');
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user->fresh(),
        ]);
    }
}
