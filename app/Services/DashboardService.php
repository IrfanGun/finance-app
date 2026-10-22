<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function __construct(private FundBalanceService $funds) {}

    public function dataFor(User $user): array
    {
        $income = $user->transactions()
            ->where('type', 'income')
            ->whereHas('account', fn (Builder $query) => $query->where('currency', 'IDR'))
            ->sum('amount');
        $expense = $user->transactions()
            ->where('type', 'expense')
            ->whereHas('account', fn (Builder $query) => $query->where('currency', 'IDR'))
            ->sum('amount');
        $assetValue = $user->assets()
            ->where('status', 'active')
            ->where('currency', 'IDR')
            ->sum('current_value');
        $accountBalance = $user->financialAccounts()
            ->where('currency', 'IDR')
            ->sum('opening_balance') + $income - $expense;
        $categories = $user->categories()
            ->orderBy('type')
            ->orderBy('name')
            ->get();
        $categoryExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereHas('account', fn (Builder $query) => $query->where('currency', 'IDR'))
            ->whereNotNull('category_id')
            ->select('category_id')
            ->selectRaw('SUM(amount) as amount')
            ->groupBy('category_id')
            ->pluck('amount', 'category_id');
        $spendingCategories = $categories
            ->where('type', 'expense')
            ->map(function (Category $category) use ($categoryExpenses): array {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'amount' => (float) $categoryExpenses->get($category->id, 0),
                ];
            })
            ->filter(fn (array $category): bool => $category['amount'] > 0)
            ->sortByDesc('amount')
            ->values();

        return [
            'funds' => $this->funds->totalsFor($user),
            'budgetPeriods' => $user->budgetPlans()
                ->where('type', 'spending')
                ->with(['periods' => fn ($query) => $query->where('status', 'open')])
                ->get()
                ->flatMap(fn ($plan) => $plan->periods->map(fn ($period) => [
                    'id' => $period->id,
                    'name' => $plan->name,
                    'starts_on' => $period->starts_on->toDateString(),
                    'ends_on' => $period->ends_on->toDateString(),
                    'account_id' => $period->financial_account_id,
                ]))
                ->values(),
            'savingOptions' => $user->savings()
                ->where('status', 'active')
                ->with('goalPlan')
                ->get(['id', 'name', 'currency'])
                ->map(fn ($saving) => [
                    'id' => $saving->id,
                    'name' => $saving->name,
                    'currency' => $saving->currency,
                    'goal_plan_id' => $saving->goalPlan?->id,
                ]),
            'categories' => $categories->where('is_active', true)->values(),
            'spendingCategories' => $spendingCategories,
            'accounts' => $user->financialAccounts()
                ->withSum([
                    'transactions as income_total' => fn (Builder $query) => $query->where('type', 'income'),
                    'transactions as expense_total' => fn (Builder $query) => $query->where('type', 'expense'),
                ], 'amount')
                ->get()
                ->each(function ($account) {
                    $account->current_balance = $account->opening_balance
                        + ($account->income_total ?? 0)
                        - ($account->expense_total ?? 0);
                }),
            'assets' => $user->assets()
                ->where('status', 'active')
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->take(3)
                ->get(['id', 'name', 'currency', 'current_value']),
            'transactions' => $user->transactions()->with(['category', 'account'])->latest('date')->take(5)->get(),
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'account_balance' => $accountBalance,
                'asset_value' => $assetValue,
                'balance' => $accountBalance + $assetValue,
            ],
        ];
    }
}
