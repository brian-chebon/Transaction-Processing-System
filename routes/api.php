<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BalanceController;

Route::middleware('auth:sanctum')->group(function () {
    // Balance endpoints
    Route::prefix('v1')->group(function () {
        // Balance routes
        Route::get('/balance', [BalanceController::class, 'show']);
        Route::get('/balance/details', [BalanceController::class, 'details']);
        Route::get('/balance/history', [BalanceController::class, 'history']);

        // Transaction routes
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::get('/transactions', [TransactionController::class, 'history']);
    });
});

// Health check route (public)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()
    ]);
});
