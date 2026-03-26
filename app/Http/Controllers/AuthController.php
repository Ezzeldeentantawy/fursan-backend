<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function registerCandidate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // hashed automatically if using $casts['password'] = 'hashed'
            'role' => 'candidate', // important: set role
        ]);

        Auth::login($user);

        return response()->json([
            'message' => 'Candidate registered successfully',
            'user' => $user
        ], 201);
    }
    public function createEmployer(Request $request)
    {
        // Make sure the authenticated user exists
        $admin = $request->user();
        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // hashed automatically if User model has cast 'password' => 'hashed'
            'role' => 'employer', // force role
        ]);

        return response()->json([
            'message' => 'Employer created successfully',
            'user' => $user,
        ], 201);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // User is now logged in via session
        $user = Auth::user();

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
        ]);
    }
    public function logout(Request $request)
    {
        // Revoke current user's token (if using Sanctum)
        $user = $request->user();

        if ($user) {
            // If using Sanctum token-based API
            $user->currentAccessToken()->delete();
        }

        // Log out from session (optional if using session)
        Auth::logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}

