<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\FinancialAccount;
use App\Models\Saving;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class FundBalanceService
{
    public function actual(FinancialAccount $account): string
    {
        $income = $account->transactions()->where('type', 'income')->sum('amount');
        $expense = $account->transactions()->where('type', 'expense')->sum('amount');

        return $this->subtract(
            $this->add((string) $account->opening_balance, (string) $income),
            (string) $expense,
        );
    }

    public function periodRemaining(BudgetPeriod $period): string
    {
        $spent = $period->transactions()->where('type', 'expense')->sum('amount');
        $claimed = $period->claims()->whereNull('reversed_at')->sum('amount');

        return $this->subtract(
            $this->subtract(
                $this->subtract((string) $period->allocated_amount, (string) $spent),
                (string) $claimed,
            ),
            (string) $period->released_amount,
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

        return $this->subtract((string) $allocated, (string) $released);
    }

    public function goalContribution(BudgetPeriod $period): string
    {
        $logs = DB::table('saving_logs')
            ->where('budget_period_id', $period->id);
        $allocated = (clone $logs)->where('type', 'allocation')->sum('amount');
        $released = (clone $logs)->where('type', 'release')->sum('amount');

        return $this->subtract((string) $allocated, (string) $released);
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
            $budget = $this->add($budget, $this->periodRemaining($period));
        }

        $savingLogs = DB::table('saving_logs')
            ->join('savings', 'savings.id', '=', 'saving_logs.saving_id')
            ->where('savings.user_id', $account->user_id)
            ->where('saving_logs.financial_account_id', $account->id);
        $allocated = (clone $savingLogs)->where('saving_logs.type', 'allocation')->sum('saving_logs.amount');
        $released = (clone $savingLogs)->where('saving_logs.type', 'release')->sum('saving_logs.amount');

        return $this->add(
            $budget,
            $this->subtract((string) $allocated, (string) $released),
        );
    }

    public function free(FinancialAccount $account): string
    {
        return $this->subtract($this->actual($account), $this->reserved($account));
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
            $savings = $this->subtract((string) $allocated, (string) $released);
            $budget = $this->subtract($reserved, $savings);

            foreach ([
                'actual' => $actual,
                'budget' => $budget,
                'savings' => $savings,
                'free' => $this->subtract($actual, $reserved),
            ] as $key => $value) {
                $totals[$currency][$key] = $this->add($totals[$currency][$key], $value);
            }
        }

        return $totals;
    }

    private function add(string $left, string $right): string
    {
        return (string) BigDecimal::of($left)
            ->plus($right)
            ->toScale(2);
    }

    private function subtract(string $left, string $right): string
    {
        return (string) BigDecimal::of($left)
            ->minus($right)
            ->toScale(2);
    }
}
