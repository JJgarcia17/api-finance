<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\FinancialChatController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\TransferController;
use App\Http\Controllers\Api\LlmController;
use App\Http\Controllers\Api\LlmMetricsController;
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

    // Categories 
    Route::prefix('categories')->group(function () {
        Route::post('{category}/restore', [CategoryController::class, 'restore']);
        Route::post('{category}/toggle-status', [CategoryController::class, 'toggleStatus']);
    });

    Route::apiResource('categories', CategoryController::class);
    
    // Additional account routes
    Route::group(['prefix' => 'accounts'], function () {
        Route::post('{account}/restore', [AccountController::class, 'restore'])
            ->name('accounts.restore')
            ->withTrashed();
        Route::patch('{account}/toggle-status', [AccountController::class, 'toggleStatus'])
            ->name('accounts.toggle-status');
        
        Route::get('stats', [AccountController::class, 'stats'])
            ->name('accounts.stats');
    });

    Route::apiResource('accounts', AccountController::class);

    // Transactions
    Route::group(['prefix' => 'transactions'], function () {
        Route::post('{transaction}/restore', [TransactionController::class, 'restore'])
        ->name('transactions.restore')
        ->withTrashed();
        Route::get('stats', [TransactionController::class, 'stats'])
        ->name('transactions.stats');
        Route::get('stats-by-currency', [TransactionController::class, 'statsByCurrency'])
        ->name('transactions.stats-by-currency');
    });

    Route::apiResource('transactions', TransactionController::class);

    // Transfers - specific routes first to avoid conflicts
    Route::get('transfers/stats', [TransferController::class, 'stats'])
        ->name('transfers.stats');
    Route::get('transfers/accounts', [TransferController::class, 'accounts'])
        ->name('transfers.accounts');
    
    Route::apiResource('transfers', TransferController::class)->except(['update']);

    // Budgets
    Route::group(['prefix' => 'budgets'], function () {
        Route::post('{budget}/restore', [BudgetController::class,'restore'])
        ->name('budgets.restore')
        ->withTrashed();
        Route::patch('{budget}/toggle-status', [BudgetController::class, 'toggleStatus'])
        ->name('budgets.toggle-status');

        Route::get('budgets-active', [BudgetController::class, 'active'])
            ->name('budgets.active');

        Route::get('current', [BudgetController::class, 'current'])
            ->name('budgets.current');
    });
    Route::apiResource('budgets', BudgetController::class);

    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Financial Chat API
    Route::prefix('chat')->group(function () {
        Route::post('message', [FinancialChatController::class, 'sendMessage']);
        Route::get('stats', [FinancialChatController::class, 'getConversationStats']);
        Route::delete('clear', [FinancialChatController::class, 'clearConversation']);
        Route::get('suggestions', [FinancialChatController::class, 'getSuggestions']);
        Route::get('summary', [FinancialChatController::class, 'getFinancialSummary']);
    });
    
    // LLM API
    Route::prefix('llm')->group(function () {
        Route::get('status', [LlmController::class, 'getStatus']);
        Route::post('generate-text', [LlmController::class, 'generateText']);
        Route::post('generate-structured', [LlmController::class, 'generateStructuredOutput']);
        
        // Métricas y monitoreo
        Route::get('metrics', [LlmMetricsController::class, 'getMetrics']);
        Route::get('system-status', [LlmMetricsController::class, 'getSystemStatus']);
        Route::post('reset-circuit-breaker', [LlmMetricsController::class, 'resetCircuitBreaker']);
    });
});
