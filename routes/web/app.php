<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiChatTransactionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDepreciationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\MealRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RecurringReminderController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('ai/chat', AiChatController::class)
        ->middleware('verified')
        ->middleware('throttle:20,1')
        ->name('ai.chat');

    Route::post('ai/chat/transactions/confirm', [AiChatTransactionController::class, 'confirm'])
        ->middleware('verified')
        ->middleware('throttle:20,1')
        ->name('ai.chat.transactions.confirm');

    Route::get('chat', ChatController::class)
        ->middleware('verified')
        ->name('chat');

    Route::resource('categories', CategoryController::class)
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::resource('accounts', FinancialAccountController::class)
        ->parameters(['accounts' => 'account'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('assets', AssetController::class)
        ->only(['index', 'store', 'show', 'edit', 'update', 'destroy']);

    Route::patch('assets/{asset}/status', [AssetController::class, 'dispose'])
        ->name('assets.status');

    Route::put('assets/{asset}/depreciation', [AssetDepreciationController::class, 'update'])
        ->name('assets.depreciation.update');

    Route::patch('assets/{asset}/depreciation/status', [AssetDepreciationController::class, 'updateStatus'])
        ->name('assets.depreciation.status');

    Route::get('assets/{asset}/depreciation-logs', [AssetDepreciationController::class, 'logs'])
        ->name('assets.depreciation.logs');

    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'store']);

    Route::get('recurring-reminders', [RecurringReminderController::class, 'index'])
        ->name('recurring-reminders.index');

    Route::get('recurring-reminders/{reminder}', [RecurringReminderController::class, 'show'])
        ->whereNumber('reminder')
        ->name('recurring-reminders.show');

    Route::post('recurring-reminders', [RecurringReminderController::class, 'store'])
        ->name('recurring-reminders.store');

    Route::patch('recurring-reminders/{reminder}', [RecurringReminderController::class, 'update'])
        ->whereNumber('reminder')
        ->name('recurring-reminders.update');

    Route::post('recurring-reminders/{reminder}/pause', [RecurringReminderController::class, 'pause'])
        ->whereNumber('reminder')
        ->name('recurring-reminders.pause');

    Route::post('recurring-reminders/{reminder}/resume', [RecurringReminderController::class, 'resume'])
        ->whereNumber('reminder')
        ->name('recurring-reminders.resume');

    Route::post('recurring-reminders/{reminder}/end', [RecurringReminderController::class, 'end'])
        ->whereNumber('reminder')
        ->name('recurring-reminders.end');

    Route::get('recurring-reminders/{reminder}/logs/{log}/transaction', [RecurringReminderController::class, 'transactionForm'])
        ->whereNumber(['reminder', 'log'])
        ->name('recurring-reminders.transaction');

    Route::post('recurring-reminders/{reminder}/logs/{log}/complete', [RecurringReminderController::class, 'complete'])
        ->whereNumber(['reminder', 'log'])
        ->name('recurring-reminders.complete');

    Route::post('recurring-reminders/{reminder}/logs/{log}/snooze', [RecurringReminderController::class, 'snooze'])
        ->whereNumber(['reminder', 'log'])
        ->name('recurring-reminders.snooze');

    Route::post('recurring-reminders/{reminder}/logs/{log}/skip', [RecurringReminderController::class, 'skip'])
        ->whereNumber(['reminder', 'log'])
        ->name('recurring-reminders.skip');

    Route::resource('meals', MealRecordController::class)
        ->parameters(['meals' => 'meal'])
        ->only(['index', 'show', 'store', 'update', 'destroy']);

    Route::get('receipt/scan', [ReceiptController::class, 'scan'])
        ->name('receipt.scan');

    Route::post('receipt/ocr', [ReceiptController::class, 'ocr'])
        ->middleware('throttle:10,1')
        ->name('receipt.ocr');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
