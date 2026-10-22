<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\FinancialAccount;
use App\Models\Saving;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FundBalanceService
{
    public function actual(FinancialAccount $account): string
    {
        $income = $account->transactions()->where('type', 'income')->sum('amount');
        $expense = $account->transactions()->where('type', 'expense')->sum('amount');

        return bcsub(
            bcadd((string) $account->opening_balance, (string) $income, 2),
            (string) $expense,
            2,
        );
    }

    public function periodRemaining(BudgetPeriod $period): string
    {
        $spent = $period->transactions()->where('type', 'expense')->sum('amount');
        $claimed = $period->claims()->whereNull('reversed_at')->sum('amount');

        return bcsub(
            bcsub(
                bcsub((string) $period->allocated_amount, (string) $spent, 2),
                (string) $claimed,
                2,
            ),
            (string) $period->released_amount,
            2,
        );
    }

    public function savingBalance(Saving $saving, ?int $accountId = null): string
    {
        $logs = $saving->logs();

        if ($accountId !== null) {
            $logs->where('financial_account_id', $accountId);
        }

        $allocated = (clone $logs)->where('type', 'allocation')->sum('amount');
        $released = (clone $logs)->where('type', 'release')->sum('amount');

        return bcsub((string) $allocated, (string) $released, 2);
    }

    public function goalContribution(BudgetPeriod $period): string
    {
        $logs = DB::table('saving_logs')
            ->where('budget_period_id', $period->id);
        $allocated = (clone $logs)->where('type', 'allocation')->sum('amount');
        $released = (clone $logs)->where('type', 'release')->sum('amount');

        return bcsub((string) $allocated, (string) $released, 2);
    }

    public function reserved(FinancialAccount $account): string
    {
        $periods = BudgetPeriod::query()
            ->where('financial_account_id', $account->id)
            ->whereHas(
                'plan',
                fn ($query) => $query->where('user_id', $account->user_id)->where('type', 'spending'),
            )
            ->get();
        $budget = '0.00';

        foreach ($periods as $period) {
            $budget = bcadd($budget, $this->periodRemaining($period), 2);
        }

        $savingLogs = DB::table('saving_logs')
            ->join('savings', 'savings.id', '=', 'saving_logs.saving_id')
            ->where('savings.user_id', $account->user_id)
            ->where('saving_logs.financial_account_id', $account->id);
        $allocated = (clone $savingLogs)->where('saving_logs.type', 'allocation')->sum('saving_logs.amount');
        $released = (clone $savingLogs)->where('saving_logs.type', 'release')->sum('saving_logs.amount');

        return bcadd($budget, bcsub((string) $allocated, (string) $released, 2), 2);
    }

    public function free(FinancialAccount $account): string
    {
        return bcsub($this->actual($account), $this->reserved($account), 2);
    }

    /**
     * @return array<string, array{actual: string, budget: string, savings: string, free: string}>
     */
    public function totalsFor(User $user): array
    {
        $totals = [];

        foreach ($user->financialAccounts()->get() as $account) {
            $currency = $account->currency;
            $totals[$currency] ??= [
                'actual' => '0.00',
                'budget' => '0.00',
                'savings' => '0.00',
                'free' => '0.00',
            ];
            $actual = $this->actual($account);
            $reserved = $this->reserved($account);
            $savingLogs = DB::table('saving_logs')
                ->join('savings', 'savings.id', '=', 'saving_logs.saving_id')
                ->where('savings.user_id', $user->id)
                ->where('saving_logs.financial_account_id', $account->id);
            $allocated = (clone $savingLogs)->where('saving_logs.type', 'allocation')->sum('saving_logs.amount');
            $released = (clone $savingLogs)->where('saving_logs.type', 'release')->sum('saving_logs.amount');
            $savings = bcsub((string) $allocated, (string) $released, 2);
            $budget = bcsub($reserved, $savings, 2);

            foreach ([
                'actual' => $actual,
                'budget' => $budget,
                'savings' => $savings,
                'free' => bcsub($actual, $reserved, 2),
            ] as $key => $value) {
                $totals[$currency][$key] = bcadd($totals[$currency][$key], $value, 2);
            }
        }

        return $totals;
    }
}
