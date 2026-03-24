<?php

use App\Http\Controllers\AuthController;
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
});
