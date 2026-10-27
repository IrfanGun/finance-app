<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDepreciationController;
use App\Http\Controllers\BudgetPlanController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Cms\AuthenticatedSessionController as CmsAuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\RecurringReminderController;
use App\Http\Controllers\SavingController;
use App\Http\Controllers\TransactionController;
use App\Models\Category;
use App\Services\AiConversationService;
use App\Services\AssetService;
use App\Services\CategoryService;
use App\Services\DashboardService;
use App\Services\FinancialAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('cms')
    ->name('cms.')
    ->group(function () {
        Route::get('/', fn () => redirect()->route('cms.dashboard'))
            ->name('home');

        Route::post('logout', [CmsAuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');

        Route::middleware('guest')->group(function () {
            Route::get('login', [CmsAuthenticatedSessionController::class, 'create'])
                ->name('login');

            Route::post('login', [CmsAuthenticatedSessionController::class, 'store'])
                ->name('login.store');
        });

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::get('dashboard', function (
                Request $request,
                DashboardController $controller,
                DashboardService $dashboard,
            ) {
                return $controller($request, $dashboard);
            })->name('dashboard');

            Route::get('budget-plans/create', [BudgetPlanController::class, 'create'])
                ->name('budget-plans.create');

            Route::get('budget-plans', [BudgetPlanController::class, 'index'])
                ->name('budget-plans.index');

            Route::get('budget-plans/{plan}', [BudgetPlanController::class, 'show'])
                ->whereNumber('plan')
                ->name('budget-plans.show');

            Route::get('savings/create', [SavingController::class, 'create'])
                ->name('savings.create');

            Route::get('savings', [SavingController::class, 'index'])
                ->name('savings.index');

            Route::get('savings/{saving}/manage', [SavingController::class, 'manage'])
                ->whereNumber('saving')
                ->name('savings.manage');

            Route::get('savings/{saving}', [SavingController::class, 'show'])
                ->whereNumber('saving')
                ->name('savings.show');

            Route::get('transactions', function (Request $request, TransactionController $controller) {
                return $controller->index($request);
            })->name('transactions.index');

            Route::get('recurring', function (Request $request, RecurringReminderController $controller) {
                return $controller->index($request);
            })->name('recurring.index');

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

Route::middleware(['auth', 'verified'])
    ->get('cm/recurring', fn () => redirect()->route('cms.recurring.index'))
    ->name('cm.recurring');
