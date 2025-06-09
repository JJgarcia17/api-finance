<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ApiStatusController;


Route::get('/', function () {
    return redirect()->route('api.status.dashboard');
});

Route::get('/api-status', [ApiStatusController::class, 'dashboard'])->name('api.status.dashboard');
Route::get('/api-status/json', [ApiStatusController::class, 'apiStatus'])->name('api.status.json');

// Agregar ruta de login para evitar errores
Route::get('/login', function () {
    return response()->json([
        'message' => 'Esta es una API. Use /api/v1/auth/login para autenticación.'
    ], 401);
})->name('login');
