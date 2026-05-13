<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
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
            'role' => 'site_admin', // Updated: use new role value
        ]);

        Auth::login($user);

        return response()->json([
            'message' => 'Candidate registered successfully',
            'user' => new UserResource($user),
        ], 201);
    }
    public function createEmployer(Request $request)
    {
        // Make sure the authenticated user is super_admin
        $admin = $request->user();
        if (!$admin || !$admin->isSuperAdmin()) {
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
            'role' => 'site_admin', // Updated: use new role value
        ]);

        return response()->json([
            'message' => 'Site Admin created successfully',
            'user' => new UserResource($user),
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
            'user' => new UserResource($user),
            'redirect' => $user->isSuperAdmin() ? '/admin' : '/admin/site',
        ]);
    }
    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        // This manually tells the browser to clear the session cookie
        return response()->json(['message' => 'Logged out'])
            ->withoutCookie(config('session.cookie'));
    }

}

