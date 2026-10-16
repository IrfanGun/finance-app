<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AiChatTransactionController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDepreciationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Cms\AuthenticatedSessionController as CmsAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\TransactionController;
use App\Models\Category;
use App\Services\AiConversationService;
use App\Services\AssetService;
use App\Services\CategoryService;
use App\Services\DashboardService;
use App\Services\FinancialAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');

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
    Route::resource('categories', CategoryController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
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

Route::prefix('cms')->name('cms.')->group(function () {
    Route::get('/', fn () => redirect()->route('cms.dashboard'))->name('home');
    Route::post('logout', [CmsAuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware('guest')->group(function () {
        Route::get('login', [CmsAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('login', [CmsAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', function (
            Request $request,
            DashboardController $controller,
            DashboardService $dashboard,
        ) {
            return $controller($request, $dashboard);
        })->name('dashboard');
        Route::get('transactions', function (Request $request, TransactionController $controller) {
            return $controller->index($request);
        })->name('transactions.index');
        Route::get('categories', function (
            Request $request,
            CategoryController $controller,
            CategoryService $categories,
        ) {
            return $controller->index($request, $categories);
        })->name('categories.index');
        Route::get('categories/{category}', function (
            Request $request,
            Category $category,
            CategoryController $controller,
        ) {
            return $controller->show($request, $category);
        })->name('categories.show');
        Route::get('accounts', function (
            Request $request,
            FinancialAccountController $controller,
            FinancialAccountService $accounts,
        ) {
            return $controller->index($request, $accounts);
        })->name('accounts.index');
        Route::get('assets', function (
            Request $request,
            AssetController $controller,
            AssetService $assets,
        ) {
            return $controller->index($request, $assets);
        })->name('assets.index');
        Route::get('assets/{asset}', function (
            Request $request,
            int $asset,
            AssetController $controller,
            AssetService $assets,
        ) {
            return $controller->show($request, $asset, $assets);
        })->whereNumber('asset')->name('assets.show');
        Route::get('assets/{asset}/edit', function (
            Request $request,
            int $asset,
            AssetController $controller,
            AssetService $assets,
        ) {
            return $controller->edit($request, $asset, $assets);
        })->whereNumber('asset')->name('assets.edit');
        Route::get('assets/{asset}/depreciation-logs', function (
            Request $request,
            int $asset,
            AssetDepreciationController $controller,
            AssetService $assets,
        ) {
            return $controller->logs($request, $asset, $assets);
        })->whereNumber('asset')->name('assets.depreciation.logs');
        Route::get('chat', function (
            Request $request,
            ChatController $controller,
            AiConversationService $conversations,
        ) {
            return $controller($request, $conversations);
        })->middleware('verified')->name('chat');
        Route::get('receipt/scan', function (Request $request, ReceiptController $controller) {
            return $controller->scan($request);
        })->name('receipt.scan');
        Route::get('profile', function (Request $request, ProfileController $controller) {
            return $controller->edit($request);
        })->name('profile.edit');
    });

});

require __DIR__.'/auth.php';
