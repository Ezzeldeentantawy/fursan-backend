<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// ─────────────────────────────────────────────────
    // Authentication Routes
    // ─────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register/candidate', [AuthController::class, 'registerCandidate']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });