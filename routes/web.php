<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
    Route::resource('assets', FinancialAccountController::class)->parameters(['assets' => 'account'])->only(['index', 'store', 'update', 'destroy']);
    Route::resource('transactions', TransactionController::class)
        ->only(['index', 'store']);
    Route::get('receipt/scan', [ReceiptController::class, 'scan'])->name('receipt.scan');
    Route::post('receipt/ocr', [ReceiptController::class, 'ocr'])
        ->middleware('throttle:10,1')
        ->name('receipt.ocr');
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});

require __DIR__.'/auth.php';
