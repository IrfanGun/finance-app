<?php

namespace App\Services;

use App\Models\AssetDepreciationLog;
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
        $netWorth = $accountBalance + $assetValue;
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
                'balance' => $netWorth,
            ],
            'wealthTrend' => $this->wealthTrendFor($user, (float) $netWorth),
        ];
    }

    /**
     * @return array{series: array<int, array{date: string, value: float}>, change: float, change_percentage: float}
     */
    private function wealthTrendFor(User $user, float $currentWealth): array
    {
        $periodStart = now()->subMonthNoOverflow()->startOfDay();
        $today = now()->startOfDay();
        $startDate = $periodStart->toDateString();
        $endDate = $today->toDateString();

        $dailyChanges = [];

        $transactionChanges = $user->transactions()
            ->join('financial_accounts', 'transactions.account_id', '=', 'financial_accounts.id')
            ->where('financial_accounts.currency', 'IDR')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->whereIn('transactions.type', ['income', 'expense'])
            ->selectRaw("transactions.date as event_date, SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE -transactions.amount END) as amount")
            ->groupBy('transactions.date')
            ->pluck('amount', 'event_date');

        foreach ($transactionChanges as $date => $amount) {
            $dailyChanges[$date] = ($dailyChanges[$date] ?? 0) + (float) $amount;
        }

        $assetValueChanges = AssetDepreciationLog::query()
            ->join('asset_depreciations', 'asset_depreciation_logs.asset_depreciation_id', '=', 'asset_depreciations.id')
            ->join('assets', 'asset_depreciations.asset_id', '=', 'assets.id')
            ->where('assets.user_id', $user->id)
            ->where('assets.currency', 'IDR')
            ->where('assets.status', 'active')
            ->whereNull('assets.deleted_at')
            ->whereBetween('asset_depreciation_logs.depreciation_date', [$startDate, $endDate])
            ->selectRaw('asset_depreciation_logs.depreciation_date as event_date, SUM(asset_depreciation_logs.value_change) as amount')
            ->groupBy('asset_depreciation_logs.depreciation_date')
            ->pluck('amount', 'event_date');

        foreach ($assetValueChanges as $date => $amount) {
            $dailyChanges[$date] = ($dailyChanges[$date] ?? 0) + (float) $amount;
        }

        $assetPurchases = $user->assets()
            ->where('status', 'active')
            ->where('currency', 'IDR')
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->selectRaw('purchase_date as event_date, SUM(purchase_price) as amount')
            ->groupBy('purchase_date')
            ->pluck('amount', 'event_date');

        foreach ($assetPurchases as $date => $amount) {
            $dailyChanges[$date] = ($dailyChanges[$date] ?? 0) + (float) $amount;
        }

        $startingWealth = $currentWealth;

        foreach ($dailyChanges as $date => $amount) {
            if ($date > $startDate) {
                $startingWealth -= $amount;
            }
        }

        $series = [[
            'date' => $startDate,
            'value' => round($startingWealth, 2),
        ]];
        $wealth = $startingWealth;
        $date = $periodStart->copy()->addDay();

        while ($date->lte($today)) {
            $dateKey = $date->toDateString();
            $wealth += $dailyChanges[$dateKey] ?? 0;
            $series[] = [
                'date' => $dateKey,
                'value' => round($wealth, 2),
            ];
            $date->addDay();
        }

        $change = round($currentWealth - $startingWealth, 2);

        return [
            'series' => $series,
            'change' => $change,
            'change_percentage' => $startingWealth != 0.0
                ? round(($change / abs($startingWealth)) * 100, 2)
                : ($currentWealth == 0.0 ? 0.0 : ($currentWealth > 0 ? 100.0 : -100.0)),
        ];
    }
}
