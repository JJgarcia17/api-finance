<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas públicas (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    // Categories - Rutas CRUD
    Route::apiResource('categories', CategoryController::class);
    
    // Categories - Rutas adicionales
    Route::prefix('categories')->group(function () {
        Route::post('{category}/restore', [CategoryController::class, 'restore']);
        Route::post('{category}/toggle-status', [CategoryController::class, 'toggleStatus']);
    });

    Route::apiResource('accounts', AccountController::class);
    
    // Additional account routes
    Route::post('accounts/{account}/restore', [AccountController::class, 'restore'])
        ->name('accounts.restore')
        ->withTrashed();
    
    Route::patch('accounts/{account}/toggle-status', [AccountController::class, 'toggleStatus'])
        ->name('accounts.toggle-status');
    
    Route::get('accounts-stats', [AccountController::class, 'stats'])
        ->name('accounts.stats');

    // Ruta de ejemplo (mantener por ahora)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
