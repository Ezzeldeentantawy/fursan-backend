<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // ─────────────────────────────────────────────────
    // GET /user/profile  — return authenticated user's profile
    // ─────────────────────────────────────────────────
    public function profile(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'site_id' => $user->site_id,
                'phone' => $user->phone,
                'avatar' => $user->avatar,
                'cv' => $user->cv,
                'isAdmin' => $user->isAdmin(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────
    // POST /user/profile  — update name, phone, avatar, cv
    // ─────────────────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'cv' => 'sometimes|nullable|file|mimes:pdf,doc,docx|max:5120', // max 5 MB
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
            'user' => $user->fresh(),
        ]);
    }

    public function index()
    {
        $users = User::select('id', 'name', 'email', 'role', 'phone', 'avatar', 'created_at')->get();

        return response()->json(['data' => $users]);
    }

    public function destroy(User $user)
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        if ($user->cv) {
            Storage::disk('public')->delete($user->cv);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function changePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json(['message' => 'Password changed successfully']);
    }
}
