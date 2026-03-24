<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// --- Guest ---
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// --- Authenticated ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('sites', SiteController::class)->only(['index', 'store', 'show', 'update', 'destroy']);

    Route::post('/sites/{site}/scan', [ScanController::class, 'store']);
    Route::post('/sites/{site}/findings/{finding}/complete', [ScanController::class, 'completeFinding']);

    // Missions
    Route::get('/sites/{site}/missions', [MissionController::class, 'index']);
    Route::post('/sites/{site}/missions/generate', [MissionController::class, 'generate']);
    Route::post('/sites/{site}/missions/{mission}/steps/{step}/toggle', [MissionController::class, 'completeStep']);

    // Dashboard
    Route::get('/sites/{site}/dashboard', [DashboardController::class, 'show']);
});
