<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\FinancialAccount;
use App\Models\SavingLog;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialAccountService
{
    public function __construct(private FundBalanceService $balances) {}

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
        return DB::transaction(function () use ($account, $data): bool {
            $lockedAccount = FinancialAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (($data['currency'] ?? $lockedAccount->currency) !== $lockedAccount->currency
                && (BudgetPeriod::query()
                    ->where('financial_account_id', $lockedAccount->id)
                    ->exists()
                    || SavingLog::query()
                        ->where('financial_account_id', $lockedAccount->id)
                        ->exists())) {
                throw ValidationException::withMessages([
                    'currency' => 'Mata uang akun tidak dapat diubah setelah ada riwayat cadangan.',
                ]);
            }

            if (isset($data['opening_balance'])) {
                $decrease = BigDecimal::of((string) $lockedAccount->opening_balance)
                    ->minus((string) $data['opening_balance'])
                    ->toScale(2);

                if ($decrease->compareTo('0') > 0
                    && BigDecimal::of($this->balances->free($lockedAccount))
                        ->compareTo($decrease) < 0) {
                    throw ValidationException::withMessages([
                        'opening_balance' => 'Saldo awal tidak dapat diturunkan melebihi uang bebas.',
                    ]);
                }
            }

            return $lockedAccount->update($data);
        }, attempts: 3);
    }

    public function delete(FinancialAccount $account, bool $deleteTransactions): void
    {
        DB::transaction(function () use ($account, $deleteTransactions): void {
            $lockedAccount = FinancialAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (BigDecimal::of($this->balances->reserved($lockedAccount))
                ->compareTo('0') > 0) {
                throw ValidationException::withMessages([
                    'account' => 'Selesaikan seluruh cadangan sebelum menghapus akun.',
                ]);
            }

            if (BudgetPeriod::query()
                ->where('financial_account_id', $lockedAccount->id)
                ->exists()
                || SavingLog::query()
                    ->where('financial_account_id', $lockedAccount->id)
                    ->exists()
                || $lockedAccount->transactions()
                    ->withoutGlobalScope('posted')
                    ->where('status', 'void')
                    ->exists()) {
                throw ValidationException::withMessages([
                    'account' => 'Akun memiliki riwayat budget atau savings dan tidak dapat dihapus.',
                ]);
            }

            if ($deleteTransactions) {
                $account->transactions()->delete();
            }
            $account->delete();
        });
    }
}
