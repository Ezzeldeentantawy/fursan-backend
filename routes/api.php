<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobApplyController;
use App\Http\Controllers\UserController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user(); // returns the authenticated user
});
// ─────────────────────────────────────────────────
// Public Job Routes (anyone can view approved jobs)
// ─────────────────────────────────────────────────
Route::get('/jobs', [JobController::class, 'index']);
Route::get('/jobs/{job}', [JobController::class, 'show']);

// ─────────────────────────────────────────────────
// Authentication Routes
// ─────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register/candidate', [AuthController::class, 'registerCandidate']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
});

// ─────────────────────────────────────────────────
// Authenticated User Routes (any logged-in role)
// ─────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::post('/user/profile', [UserController::class, 'updateProfile']); // multipart/form-data
});

// ─────────────────────────────────────────────────
// Candidate Routes
// ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:candidate'])->group(function () {
    // Apply for a job (requires CV on profile)
    Route::post('/jobs/{job}/apply', [JobApplyController::class, 'apply']);

    // View my applications
    Route::get('/candidate/applications', [JobApplyController::class, 'myApplications']);

    // Withdraw a pending application
    Route::delete('/candidate/applications/{application}', [JobApplyController::class, 'withdraw']);
});

// ─────────────────────────────────────────────────
// Employer Routes
// ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:employer'])->group(function () {
    // Job management
    Route::get('/employer/jobs', [JobController::class, 'myJobs']);
    Route::post('/employer/jobs', [JobController::class, 'store']);
    Route::post('/employer/jobs/{job}', [JobController::class, 'update']); // POST for form-data with file
    Route::delete('/employer/jobs/{job}', [JobController::class, 'destroy']);

    // Applications received
    Route::get('/employer/jobs/{job}/applications', [JobApplyController::class, 'jobApplications']);
    Route::patch('/employer/applications/{application}/status', [JobApplyController::class, 'updateStatus']);
});

// ─────────────────────────────────────────────────
// Admin Routes
// ─────────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Employer management
    Route::post('/create/employer', [AuthController::class, 'createEmployer']);
    Route::get('/dashboard', function (Request $request) {
        return response()->json([
            'message' => 'Dashboard',
        ]);
    });

    // Job approval
    Route::get('/admin/jobs', [JobController::class, 'adminIndex']);
    Route::patch('/admin/jobs/{job}/approve', [JobController::class, 'approve']);
    Route::patch('/admin/jobs/{job}/reject', [JobController::class, 'reject']);
    Route::delete('/admin/jobs/{job}', [JobController::class, 'adminDestroy']);
});
