<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\JobApplyController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TemplatesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\MediaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('render/home', [PageController::class, 'getHomePage']);
    Route::get('render/{slug}', [PageController::class, 'getBySlug']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // ─────────────────────────────────────────────────
    // Public Job Routes (anyone can view approved jobs)
    // ─────────────────────────────────────────────────
    Route::get('/jobs', [JobController::class, 'index']);
    Route::get('/jobs/{job}', [JobController::class, 'show']);
    Route::get('/seo/schema', [SettingsController::class, 'getSchema']);
    Route::get('/settings/menus', [SettingsController::class, 'getMenuLinks']);
    Route::get('/settings/favicon', [SettingsController::class, 'getFavicon']);
    Route::get('/global-elements', [TemplatesController::class, 'getGlobals']);
    Route::get('/active-header', [TemplatesController::class, 'getHeader']);
    Route::get('/active-footer', [TemplatesController::class, 'getFooter']);

    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/newsletter/subscribe', [ContactController::class, 'newsLetterSubscribe']);
        Route::post('/contact', [ContactController::class, 'store']);
    });

    // ─────────────────────────────────────────────────
    // Authenticated User Routes (any logged-in role)
    // ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/profile', [UserController::class, 'profile']);
        Route::post('/user/profile', [UserController::class, 'updateProfile']);
    });

    // ─────────────────────────────────────────────────
    // Candidate Routes
    // ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:candidate'])->group(function () {
        Route::post('/jobs/{job}/apply', [JobApplyController::class, 'apply']);
        Route::get('/candidate/applications', [JobApplyController::class, 'myApplications']);
        Route::delete('/candidate/applications/{application}', [JobApplyController::class, 'withdraw']);
    });

    // ─────────────────────────────────────────────────
    // Employer Routes
    // ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:employer'])->group(function () {
        Route::get('/employer/jobs', [JobController::class, 'myJobs']);
        Route::post('/employer/jobs', [JobController::class, 'store']);
        Route::put('/employer/jobs/{job}', [JobController::class, 'update']);
        Route::delete('/employer/jobs/{job}', [JobController::class, 'destroy']);
        Route::get('/employer/jobs/{job}/applications', [JobApplyController::class, 'jobApplications']);
        Route::patch('/employer/applications/{application}/status', [JobApplyController::class, 'updateStatus']);
    });

    // ─────────────────────────────────────────────────
    // Page Management Routes (authenticated users, authorization in controller)
    // ─────────────────────────────────────────────────
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('pages', PageController::class);
        Route::post('pages/{id}/translate-ar', [PageController::class, 'translateArabic']);
        Route::patch('pages/{id}/set-home', [PageController::class, 'setHomePage']);
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

        // Contact messages
        Route::get('/contact', [ContactController::class, 'index']);
        Route::get('/contact/unread', [ContactController::class, 'getUnread']);
        Route::get('/contact/read', [ContactController::class, 'getRead']);
        Route::patch('/contact/{id}/read', [ContactController::class, 'markAsRead']);
        Route::delete('/contact/{id}', [ContactController::class, 'destroy']);

        // Job approval
        Route::get('/admin/jobs', [JobController::class, 'adminIndex']);
        Route::patch('/admin/jobs/{job}/approve', [JobController::class, 'approve']);
        Route::patch('/admin/jobs/{job}/reject', [JobController::class, 'reject']);
        Route::patch('/admin/jobs/{job}/pending', [JobController::class, 'pending']);
        Route::delete('/admin/jobs/{job}', [JobController::class, 'adminDestroy']);
        Route::patch('/admin/jobs/{job}/restore', [JobController::class, 'restore']);
        Route::get('/admin/trashed', [JobController::class, 'trashed']);

        // Media management
        Route::get('/media', [MediaController::class, 'index']);
        Route::post('/media', [MediaController::class, 'store']);
        Route::delete('/media/{id}', [MediaController::class, 'destroy']);

        // Templates management
        Route::get('/templates', [TemplatesController::class, 'index']);
        Route::post('/templates', [TemplatesController::class, 'store']);
        Route::get('/templates/{template}', [TemplatesController::class, 'show']);
        Route::put('/templates/{template}', [TemplatesController::class, 'update']);
        Route::delete('/templates/{template}', [TemplatesController::class, 'destroy']);

        // Settings
        Route::get('/settings/site', [SettingsController::class, 'getSiteSettings']);
        Route::put('/settings/site', [SettingsController::class, 'updateSiteSettings']);
        Route::delete('/settings/site', [SettingsController::class, 'deleteSiteSetting']);
        Route::put('/settings/menus', [SettingsController::class, 'updateMenuLinks']);
        Route::post('/settings/social-links', [SettingsController::class, 'updateSocialLinks']);
        Route::get('/settings/social-links', [SettingsController::class, 'getSocialLinks']);
        Route::delete('/settings/social-links', [SettingsController::class, 'deleteSocialLinks']);

        // User management
        Route::get('/users', [UserController::class, 'index']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{id}/password', [UserController::class, 'changePassword']);

        // Site management (rate limited: 60 requests per minute for admin usage)
        // Note: throttle middleware applies to all routes in this group
        Route::middleware('throttle:60,1')->group(function () {
            Route::apiResource('sites', SiteController::class)->except(['create', 'show']);
            // Custom route for edit (GET /sites/{id}) since we excluded 'show'
            Route::get('/sites/{id}', [SiteController::class, 'edit']);
        });
    });
});
