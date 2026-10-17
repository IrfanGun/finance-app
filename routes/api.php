<?php

use App\Http\Controllers\AiFinanceController;
use App\Http\Controllers\AiMemoryController;
use App\Http\Controllers\AiTransactionController;
use App\Http\Controllers\AiWorkflowController;
use App\Http\Middleware\AuthenticateAiService;
use Illuminate\Support\Facades\Route;

Route::prefix('ai')
    ->middleware(AuthenticateAiService::class)
    ->group(function (): void {
        Route::get('transactions', [AiTransactionController::class, 'index']);
        Route::post('transactions', [AiTransactionController::class, 'store']);
        Route::post('transactions/batch', [AiTransactionController::class, 'storeBatch']);
        Route::patch('transactions/{transaction}', [AiTransactionController::class, 'update']);
        Route::delete('transactions/{transaction}', [AiTransactionController::class, 'destroy']);
        Route::get('finance/summary', [AiFinanceController::class, 'summary']);
        Route::get('meal-records', [AiFinanceController::class, 'mealRecords']);
    });

Route::prefix('ai/memories')
    ->middleware(['web', 'auth'])
    ->group(function (): void {
        Route::get('/', [AiMemoryController::class, 'index']);
        Route::delete('/{memory}', [AiMemoryController::class, 'destroy']);
    });

Route::prefix('ai/workflows')
    ->middleware(['web', 'auth'])
    ->group(function (): void {
        Route::post('/{workflow}/cancel', [AiWorkflowController::class, 'cancel']);
    });
