<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ApiStatusController;

Route::get('/', function () {
    return redirect()->route('api.status.dashboard');
});

Route::get('/api-status', [ApiStatusController::class, 'dashboard'])->name('api.status.dashboard');
Route::get('/api-status/json', [ApiStatusController::class, 'apiStatus'])->name('api.status.json');
Route::get('/api-status/diagnostics', [ApiStatusController::class, 'diagnostics'])->name('api.status.diagnostics');

// Ruta de verificación de salud para debugging
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app_url' => config('app.url'),
        'environment' => config('app.env'),
        'timestamp' => now()->toISOString(),
        'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
        'http_host' => $_SERVER['HTTP_HOST'] ?? 'unknown'
    ]);
});

// Agregar ruta de login para evitar errores
Route::get('/login', function () {
    return response()->json([
        'message' => 'Esta es una API. Use /api/v1/auth/login para autenticación.'
    ], 401);
})->name('login');
