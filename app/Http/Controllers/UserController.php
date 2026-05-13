<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * List all users (super_admin only)
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorizeSuperAdmin();
        
        $users = User::with('site')->get();
        return UserResource::collection($users);
    }

    /**
     * Store a new user (super_admin only)
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorizeSuperAdmin();
        
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        
        $user = User::create($data);
        $user->load('site');
        
        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a specific user
     */
    public function show($id): UserResource
    {
        $user = User::with('site')->findOrFail($id);
        
        // Site admins can only view themselves
        if (!auth()->user()->isSuperAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized');
        }
        
        return new UserResource($user);
    }

    /**
     * Update a user
     */
    public function update(UpdateUserRequest $request, $id): UserResource
    {
        $user = User::findOrFail($id);
        
        // Only super_admin can update other users
        // Site admins can only update themselves
        if (!auth()->user()->isSuperAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized');
        }
        
        $data = $request->validated();
        
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user->update($data);
        $user->load('site');
        
        return new UserResource($user);
    }

    /**
     * Change user password (super_admin only, or user changing their own)
     */
    public function changePassword(ChangePasswordRequest $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Only super_admin can change other users' passwords
        if (!auth()->user()->isSuperAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized');
        }
        
        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);
        
        return response()->json(['message' => 'Password updated successfully']);
    }

    /**
     * Delete a user (super_admin only)
     */
    public function destroy($id): JsonResponse
    {
        $this->authorizeSuperAdmin();
        
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }
        
        $user->delete();
        
        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Check if current user is super admin
     */
    protected function authorizeSuperAdmin(): void
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can perform this action');
        }
    }
}
