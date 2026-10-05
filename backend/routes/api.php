<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, '__invoke']);

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    });
});

Route::prefix('v1')->group(function () {
    Route::get('/dashboard', function () {
        return \App\Support\ApiResponse::success(['message' => 'Welcome']);
    })->middleware(['auth:sanctum', 'permission:dashboard,view']);
});