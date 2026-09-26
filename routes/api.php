<?php

use App\Http\Controllers\AiTransactionController;
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
    });
