<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FinancialAccountService
{
    public function listFor(User $user): Collection
    {
        return $user->financialAccounts()
            ->withCount('transactions')
            ->withSum([
                'transactions as income_total' => fn (Builder $query) => $query->where('type', 'income'),
                'transactions as expense_total' => fn (Builder $query) => $query->where('type', 'expense'),
            ], 'amount')
            ->latest()
            ->get()
            ->each(function (FinancialAccount $account): void {
                $account->current_balance = $account->opening_balance
                    + ($account->income_total ?? 0)
                    - ($account->expense_total ?? 0);
            });
    }

    public function create(User $user, array $data): FinancialAccount
    {
        return $user->financialAccounts()->create($data);
    }

    public function update(FinancialAccount $account, array $data): bool
    {
        return $account->update($data);
    }

    public function delete(FinancialAccount $account, bool $deleteTransactions): void
    {
        DB::transaction(function () use ($account, $deleteTransactions): void {
            if ($deleteTransactions) {
                $account->transactions()->delete();
            }
            $account->delete();
        });
    }
}
