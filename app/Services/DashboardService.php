<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function dataFor(User $user): array
    {
        $income = $user->transactions()->where('type', 'income')->sum('amount');
        $expense = $user->transactions()->where('type', 'expense')->sum('amount');

        return [
            'categories' => $user->categories()->where('is_active', true)->orderBy('type')->orderBy('name')->get(),
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
            'transactions' => $user->transactions()->with(['category', 'account'])->latest('date')->take(5)->get(),
            'summary' => [
                'income' => $income,
                'expense' => $expense,
                'balance' => $user->financialAccounts()->sum('opening_balance') + $income - $expense,
            ],
        ];
    }
}
