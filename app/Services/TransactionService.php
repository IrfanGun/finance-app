<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\BudgetPlan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(private FundBalanceService $balances) {}

    public function create(User $user, array $data): Transaction
    {
        return DB::transaction(function () use ($user, $data): Transaction {
            $mealRecordId = $data['meal_record_id'] ?? null;
            unset($data['meal_record_id']);
            $account = $user->financialAccounts()
                ->whereKey($data['account_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! empty($data['category_id'])) {
                $category = $user->categories()->findOrFail($data['category_id']);

                if (! $category->is_active || $category->type !== $data['type']) {
                    throw ValidationException::withMessages([
                        'category_id' => 'Pilih kategori aktif yang sesuai dengan transaksi.',
                    ]);
                }
            }

            $periodId = $data['budget_period_id'] ?? null;
            $savingId = $data['saving_id'] ?? null;
            $period = null;
            $saving = null;

            if ($periodId !== null && $savingId !== null) {
                throw ValidationException::withMessages([
                    'source' => 'Pilih budget atau savings, bukan keduanya.',
                ]);
            }

            if (($periodId !== null || $savingId !== null) && $data['type'] !== 'expense') {
                throw ValidationException::withMessages([
                    'type' => 'Hanya pengeluaran yang dapat memakai cadangan.',
                ]);
            }

            if ($data['type'] === 'expense'
                && bccomp($this->balances->actual($account), (string) $data['amount'], 2) < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Saldo aktual akun tidak mencukupi.',
                ]);
            }

            if ($periodId !== null) {
                $planId = BudgetPeriod::query()->whereKey($periodId)->value('budget_plan_id');
                $plan = $user->budgetPlans()
                    ->whereKey($planId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $period = $plan->periods()
                    ->whereKey($periodId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($plan->type !== 'spending'
                    || $period->status !== 'open'
                    || $period->financial_account_id !== $account->id
                    || $period->currency !== $account->currency
                    || $data['date'] < $period->starts_on->toDateString()
                    || $data['date'] > $period->ends_on->toDateString()
                    || bccomp($this->balances->periodRemaining($period), (string) $data['amount'], 2) < 0) {
                    throw ValidationException::withMessages([
                        'budget_period_id' => 'Periode tidak valid atau sisanya tidak mencukupi.',
                    ]);
                }

                $data['budget_plan_id'] = null;
                $data['saving_id'] = null;
            } elseif ($savingId !== null) {
                $goalPlanId = BudgetPlan::query()
                    ->where('saving_id', $savingId)
                    ->value('id');
                $plan = $goalPlanId !== null
                    ? $user->budgetPlans()
                        ->whereKey($goalPlanId)
                        ->lockForUpdate()
                        ->firstOrFail()
                    : null;
                $saving = $user->savings()
                    ->whereKey($savingId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($saving->currency !== $account->currency
                    || bccomp($this->balances->savingBalance($saving, $account->id), (string) $data['amount'], 2) < 0) {
                    throw ValidationException::withMessages([
                        'saving_id' => 'Dana savings pada akun ini tidak mencukupi.',
                    ]);
                }

                $data['budget_plan_id'] = $plan?->id;
                $data['budget_period_id'] = null;
            } elseif ($data['type'] === 'expense'
                && bccomp($this->balances->free($account), (string) $data['amount'], 2) < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'Uang bebas tidak mencukupi.',
                ]);
            }

            $transaction = $user->transactions()->make($data);

            if ($mealRecordId !== null) {
                $mealRecord = $user->mealRecords()->findOrFail($mealRecordId);
                $transaction->mealRecord()->associate($mealRecord);
            }

            $transaction->save();

            if ($saving !== null) {
                $saving->logs()->create([
                    'financial_account_id' => $account->id,
                    'type' => 'release',
                    'amount' => $transaction->amount,
                    'reason' => 'expense',
                    'transaction_id' => $transaction->id,
                    'operation_id' => (string) Str::uuid(),
                    'idempotency_key' => 'expense:'.$transaction->id,
                    'occurred_at' => now('UTC'),
                ]);
            }

            return $transaction;
        }, attempts: 3);
    }

    public function void(User $user, Transaction $transaction): void
    {
        DB::transaction(function () use ($user, $transaction): void {
            $account = $user->financialAccounts()
                ->whereKey($transaction->account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $planId = $transaction->budget_plan_id
                ?? ($transaction->budget_period_id !== null
                    ? BudgetPeriod::query()->whereKey($transaction->budget_period_id)->value('budget_plan_id')
                    : null);
            $plan = $planId !== null
                ? $user->budgetPlans()->whereKey($planId)->lockForUpdate()->firstOrFail()
                : null;
            $period = $transaction->budget_period_id !== null
                ? $plan->periods()->whereKey($transaction->budget_period_id)->lockForUpdate()->firstOrFail()
                : null;
            $saving = $transaction->saving_id !== null
                ? $user->savings()->whereKey($transaction->saving_id)->lockForUpdate()->firstOrFail()
                : null;
            $lockedTransaction = $user->transactions()
                ->withoutGlobalScope('posted')
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->status === 'void') {
                return;
            }

            if ($period !== null
                && ($period->status === 'closed'
                    || $period->claims()->whereNull('reversed_at')->exists())) {
                throw ValidationException::withMessages([
                    'transaction' => 'Periode sudah ditutup atau diklaim. Koreksi klaim terlebih dahulu.',
                ]);
            }

            if ($lockedTransaction->type === 'income'
                && bccomp($this->balances->free($account), (string) $lockedTransaction->amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'transaction' => 'Pemasukan tidak dapat dibatalkan karena dana sudah dicadangkan.',
                ]);
            }

            if ($saving !== null) {
                $originalLog = $saving->logs()
                    ->where('transaction_id', $lockedTransaction->id)
                    ->where('reason', 'expense')
                    ->lockForUpdate()
                    ->firstOrFail();
                $saving->logs()->create([
                    'financial_account_id' => $account->id,
                    'type' => 'allocation',
                    'amount' => $lockedTransaction->amount,
                    'reason' => 'reversal',
                    'transaction_id' => $lockedTransaction->id,
                    'operation_id' => (string) Str::uuid(),
                    'idempotency_key' => 'void:'.$lockedTransaction->id,
                    'reverses_log_id' => $originalLog->id,
                    'occurred_at' => now('UTC'),
                ]);
            }

            $lockedTransaction->update(['status' => 'void']);
        }, attempts: 3);
    }
}
