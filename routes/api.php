<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BalanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| All routes use the "api" middleware group and v1 prefix.
|
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'api'])->group(function () {
    // Transaction routes
    Route::prefix('transactions')->group(function () {
        Route::post('/', [TransactionController::class, 'store'])
            ->name('transactions.store');

        Route::get('/', [TransactionController::class, 'history'])
            ->name('transactions.history');
    });

    // Balance routes
    Route::prefix('balance')->group(function () {
        Route::get('/', [BalanceController::class, 'show'])
            ->name('balance.show');

        Route::get('/details', [BalanceController::class, 'details'])
            ->name('balance.details');
    });
});

// Health check route (public)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()
    ]);
});
