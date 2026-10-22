<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\BudgetPlan;
use App\Models\BudgetSavingsClaim;
use App\Models\FinancialAccount;
use App\Models\FundOperation;
use App\Models\RecurringReminder;
use App\Models\RecurringReminderLog;
use App\Models\Saving;
use App\Models\SavingLog;
use App\Models\User;
use App\Notifications\BudgetFundingNeeded;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FundService
{
    public function __construct(private FundBalanceService $balances) {}

    public function createSaving(User $user, array $data): Saving
    {
        return $user->savings()->create($data + ['status' => 'active']);
    }

    public function createPlan(User $user, array $data): BudgetPlan
    {
        return DB::transaction(function () use ($user, $data): BudgetPlan {
            $account = $user->financialAccounts()
                ->whereKey($data['financial_account_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->currency !== $data['currency']) {
                $this->fail('currency', 'Mata uang plan harus sama dengan akun.');
            }

            if (! empty($data['category_id'])
                && ! $user->categories()->whereKey($data['category_id'])->exists()) {
                $this->fail('category_id', 'Kategori tidak valid.');
            }

            $saving = null;

            if ($data['type'] === 'goal') {
                $saving = $user->savings()
                    ->whereKey($data['saving_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($saving->currency !== $account->currency
                    || $saving->target_amount !== null
                    || $saving->target_date !== null
                    || $saving->status !== 'active'
                    || $saving->goalPlan()->exists()) {
                    $this->fail('saving_id', 'Savings harus aktif, bermata uang sama, dan belum dipakai goal lain.');
                }

                if (bccomp($this->balances->savingBalance($saving), '0', 2) > 0
                    && empty($data['confirm_existing_funds'])) {
                    $this->fail('confirm_existing_funds', 'Konfirmasi bahwa seluruh dana savings ini untuk goal.');
                }

                if (! empty($data['items'])) {
                    $sum = '0.00';

                    foreach ($data['items'] as $item) {
                        $sum = bcadd($sum, (string) $item['amount'], 2);
                    }

                    if (bccomp($sum, (string) $data['target_amount'], 2) !== 0) {
                        $this->fail('items', 'Total rincian harus sama dengan target.');
                    }
                }

                $data['ends_on'] = $data['target_date'];
                $data['amount'] = null;
            } else {
                $data['saving_id'] = null;
                $data['target_amount'] = null;
                $data['target_date'] = null;
                $data['contribution_amount'] = null;
            }

            $data['frequency'] = ! empty($data['is_recurring']) ? 'monthly' : null;
            $data['status'] = 'active';
            $data['next_period_starts_on'] = $data['starts_on'];
            $items = $data['items'] ?? [];
            $remind = ! empty($data['remind']);
            $reminderPurpose = $data['type'] === 'goal'
                ? 'goal_contribution'
                : ($data['reminder_purpose'] ?? 'budget_savings_claim');
            unset(
                $data['items'],
                $data['confirm_existing_funds'],
                $data['remind'],
                $data['reminder_purpose'],
            );
            $plan = $user->budgetPlans()->create($data);

            foreach ($items as $index => $item) {
                $plan->items()->create($item + ['sort_order' => $index]);
            }

            $today = CarbonImmutable::today($plan->timezone)->toDateString();

            if ($plan->starts_on->toDateString() <= $today) {
                $this->openDuePeriods($plan, $account, $today, 24);
            }

            if ($remind) {
                $firstDate = in_array($reminderPurpose, ['goal_contribution', 'budget_allocation'], true)
                    ? CarbonImmutable::parse($plan->starts_on)
                    : ($plan->is_recurring
                        ? $this->monthlyAnchor(
                            CarbonImmutable::parse($plan->starts_on),
                            $plan->interval,
                        )->subDay()
                        : CarbonImmutable::parse($plan->ends_on));

                if ($plan->type === 'spending' && $plan->ends_on !== null
                    && $firstDate->greaterThan($plan->ends_on)) {
                    $firstDate = CarbonImmutable::parse($plan->ends_on);
                }

                $firstAt = $firstDate->setTime(9, 0)->shiftTimezone($plan->timezone)->setTimezone('UTC');
                $endAt = $plan->ends_on?->setTime(23, 59)->shiftTimezone($plan->timezone)->setTimezone('UTC');
                $user->recurringReminders()->create([
                    'purpose' => $reminderPurpose,
                    'budget_plan_id' => $plan->id,
                    'type' => null,
                    'priority' => 'medium',
                    'title' => match ($reminderPurpose) {
                        'goal_contribution' => 'Setoran tujuan '.$plan->name,
                        'budget_allocation' => 'Danai budget '.$plan->name,
                        default => 'Klaim sisa budget '.$plan->name,
                    },
                    'amount' => $plan->type === 'goal' ? $plan->contribution_amount : $plan->amount,
                    'financial_account_id' => $account->id,
                    'frequency' => $plan->is_recurring ? 'monthly' : 'yearly',
                    'interval' => $plan->interval,
                    'starts_at' => $firstAt,
                    'next_reminder_at' => $firstAt,
                    'ends_at' => $endAt,
                    'timezone' => $plan->timezone,
                    'status' => 'active',
                ]);
            }

            return $plan;
        }, attempts: 3);
    }

    public function updatePlan(User $user, BudgetPlan $plan, array $data): BudgetPlan
    {
        return DB::transaction(function () use ($user, $plan, $data): BudgetPlan {
            $accountIds = collect([
                $plan->financial_account_id,
                $data['financial_account_id'],
            ])->unique()->sort()->values();
            $accounts = $user->financialAccounts()
                ->whereIn('id', $accountIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedPlan = $user->budgetPlans()
                ->whereKey($plan->id)
                ->lockForUpdate()
                ->firstOrFail();
            $account = $accounts->get($data['financial_account_id']);

            if ($account === null || $account->currency !== $data['currency']) {
                $this->fail('financial_account_id', 'Akun atau mata uang tidak valid.');
            }

            if ($lockedPlan->type !== $data['type']
                || ($lockedPlan->type === 'goal'
                    && $lockedPlan->saving_id !== (int) $data['saving_id'])) {
                $this->fail('type', 'Jenis dan savings tujuan tidak dapat diubah.');
            }

            $latestPeriod = $lockedPlan->periods()->orderByDesc('ends_on')->first();
            $scheduleChanged = $lockedPlan->starts_on->toDateString() !== $data['starts_on']
                || $lockedPlan->interval !== (int) $data['interval']
                || $lockedPlan->is_recurring !== (bool) $data['is_recurring'];

            if ($scheduleChanged && $latestPeriod !== null
                && $data['starts_on'] <= $latestPeriod->ends_on->toDateString()) {
                $this->fail('starts_on', 'Jadwal baru harus dimulai setelah semua periode lama.');
            }

            if ($lockedPlan->type === 'goal') {
                $items = $data['items'] ?? [];

                if ($items !== []) {
                    $sum = '0.00';

                    foreach ($items as $item) {
                        $sum = bcadd($sum, (string) $item['amount'], 2);
                    }

                    if (bccomp($sum, (string) $data['target_amount'], 2) !== 0) {
                        $this->fail('items', 'Total rincian harus sama dengan target.');
                    }
                }

                $data['ends_on'] = $data['target_date'];
                $data['amount'] = null;
                $lockedPlan->items()->delete();

                foreach ($items as $index => $item) {
                    $lockedPlan->items()->create($item + ['sort_order' => $index]);
                }
            } else {
                $data['target_amount'] = null;
                $data['target_date'] = null;
                $data['saving_id'] = null;
                $data['contribution_amount'] = null;
            }

            $data['frequency'] = ! empty($data['is_recurring']) ? 'monthly' : null;

            if ($scheduleChanged) {
                $data['next_period_starts_on'] = $data['starts_on'];
            }

            unset($data['items'], $data['confirm_existing_funds'], $data['remind'], $data['reminder_purpose']);
            $lockedPlan->fill($data)->save();

            return $lockedPlan->refresh();
        }, attempts: 3);
    }

    public function changePlanStatus(User $user, BudgetPlan $plan, string $status): void
    {
        DB::transaction(function () use ($user, $plan, $status): void {
            $user->financialAccounts()
                ->whereKey($plan->financial_account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPlan = $user->budgetPlans()
                ->whereKey($plan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === 'active') {
                if ($lockedPlan->status !== 'paused') {
                    $this->fail('status', 'Hanya plan yang dijeda dapat dilanjutkan.');
                }

                $today = CarbonImmutable::today($lockedPlan->timezone);
                $candidate = CarbonImmutable::parse(
                    $lockedPlan->next_period_starts_on?->toDateString()
                        ?? $lockedPlan->starts_on->toDateString(),
                    $lockedPlan->timezone,
                );

                if (! $lockedPlan->is_recurring) {
                    $candidate = $candidate->greaterThan($today)
                        ? $candidate
                        : null;
                }

                for ($attempt = 0; $lockedPlan->is_recurring
                    && $candidate->lessThanOrEqualTo($today)
                    && $attempt < 240; $attempt++) {
                    $candidate = $this->nextAnchorAfter($lockedPlan, $candidate);
                }

                if ($candidate !== null
                    && $lockedPlan->ends_on !== null
                    && $candidate->greaterThan($lockedPlan->ends_on)) {
                    $candidate = null;
                }

                $lockedPlan->update([
                    'status' => $candidate === null ? 'ended' : 'active',
                    'next_period_starts_on' => $candidate?->toDateString(),
                ]);
                $reminders = $user->recurringReminders()
                    ->where('budget_plan_id', $lockedPlan->id)
                    ->where('status', 'paused')
                    ->lockForUpdate()
                    ->get();

                foreach ($reminders as $reminder) {
                    $next = $candidate === null
                        ? null
                        : ($reminder->purpose === 'budget_savings_claim'
                        && $lockedPlan->is_recurring
                        ? $this->nextAnchorAfter($lockedPlan, $candidate)
                            ->subDay()
                            ->setTime(9, 0)
                            ->shiftTimezone($lockedPlan->timezone)
                            ->setTimezone('UTC')
                        : $this->nextReminderAfter($reminder, CarbonImmutable::now('UTC')));

                    if ($lockedPlan->ends_on !== null
                        && $next !== null
                        && $next->setTimezone($lockedPlan->timezone)->toDateString()
                            > $lockedPlan->ends_on->toDateString()) {
                        $next = null;
                    }
                    $reminder->update([
                        'status' => $next === null ? 'ended' : 'active',
                        'next_reminder_at' => $next,
                    ]);
                }

                return;
            }

            if (! in_array($status, ['paused', 'ended', 'completed'], true)) {
                $this->fail('status', 'Status plan tidak valid.');
            }

            $lockedPlan->update(['status' => $status]);

            if ($status === 'paused') {
                $user->recurringReminders()
                    ->where('budget_plan_id', $lockedPlan->id)
                    ->where('status', 'active')
                    ->update(['status' => 'paused']);
            } elseif ($status === 'ended' || $status === 'completed') {
                $user->recurringReminders()
                    ->where('budget_plan_id', $lockedPlan->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'ended',
                        'next_reminder_at' => null,
                    ]);
            }
        }, attempts: 3);
    }

    public function openDuePeriods(
        BudgetPlan $plan,
        FinancialAccount $account,
        string $today,
        int $limit = 12,
    ): int {
        if ($plan->status !== 'active' || $plan->next_period_starts_on === null) {
            return 0;
        }

        $created = 0;
        $processed = 0;
        $starts = CarbonImmutable::parse($plan->next_period_starts_on);

        while ($starts->toDateString() <= $today && $processed < $limit) {
            $processed++;
            if ($plan->ends_on !== null && $starts->greaterThan($plan->ends_on)) {
                $plan->update(['next_period_starts_on' => null]);
                break;
            }

            $next = $plan->is_recurring
                ? $this->nextAnchorAfter($plan, $starts)
                : null;
            $end = $next?->subDay()
                ?? CarbonImmutable::parse($plan->ends_on);

            if ($plan->ends_on !== null && $end->greaterThan($plan->ends_on)) {
                $end = CarbonImmutable::parse($plan->ends_on);
            }

            if ($end->lessThan($starts)) {
                $plan->update(['next_period_starts_on' => null]);
                break;
            }

            if ($plan->type === 'goal') {
                $suggested = $this->suggestedContribution($plan, $starts);
                $planned = $plan->contribution_amount ?? $suggested;
            } else {
                $planned = $plan->amount;
            }

            $period = $plan->periods()->firstOrCreate(
                ['starts_on' => $starts->toDateString()],
                [
                    'ends_on' => $end->toDateString(),
                    'financial_account_id' => $account->id,
                    'currency' => $plan->currency,
                    'planned_amount' => $planned,
                    'planned_amount_origin' => $plan->type === 'goal' && $plan->contribution_amount === null
                        ? 'suggested'
                        : 'manual',
                    'status' => 'awaiting_funding',
                ],
            );

            if ($period->wasRecentlyCreated
                && $plan->type === 'spending'
                && $starts->toDateString() <= $today
                && $end->toDateString() >= $today) {
                if (bccomp($this->balances->free($account), (string) $planned, 2) >= 0) {
                    $period->update([
                        'allocated_amount' => $planned,
                        'status' => 'open',
                        'funded_at' => now('UTC'),
                    ]);
                } else {
                    $plan->user->notify(new BudgetFundingNeeded($period));
                }
            }

            $created += (int) $period->wasRecentlyCreated;
            $starts = $next;

            if ($next === null || $next->greaterThan($end->addDay()) && $plan->ends_on !== null) {
                $plan->update(['next_period_starts_on' => null]);
                break;
            }

            $plan->update(['next_period_starts_on' => $next->toDateString()]);
        }

        return $created;
    }

    public function suggestedContribution(BudgetPlan $plan, CarbonImmutable $from): string
    {
        $available = $this->balances->savingBalance($plan->pocket);
        $paid = (string) $plan->user->transactions()
            ->where('budget_plan_id', $plan->id)
            ->where('type', 'expense')
            ->sum('amount');
        $remaining = bcsub(
            (string) $plan->target_amount,
            bcadd($available, $paid, 2),
            2,
        );

        if (bccomp($remaining, '0', 2) <= 0) {
            return '0.00';
        }

        $count = 0;
        $anchor = CarbonImmutable::parse($plan->starts_on);

        for ($index = 0; $index < 240; $index++) {
            $date = $plan->is_recurring
                ? $this->monthlyAnchor($anchor, $index * $plan->interval)
                : $anchor;

            if ($date->greaterThan($plan->target_date)) {
                break;
            }

            if ($date->greaterThanOrEqualTo($from)) {
                $count++;
            }

            if (! $plan->is_recurring) {
                break;
            }
        }

        return $count > 0 ? bcdiv($remaining, (string) $count, 2) : '0.00';
    }

    public function fundPeriod(User $user, BudgetPeriod $period, string $key): void
    {
        DB::transaction(function () use ($user, $period, $key): void {
            $account = $user->financialAccounts()
                ->whereKey($period->financial_account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $plan = $user->budgetPlans()
                ->whereKey($period->budget_plan_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPeriod = $plan->periods()
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();

            $operationId = $this->operation($user, $key, ['fund', $period->id, $period->planned_amount]);

            if ($operationId === null) {
                return;
            }

            if ($plan->type !== 'spending' || $lockedPeriod->status !== 'awaiting_funding') {
                $this->fail('period', 'Periode ini tidak dapat didanai.');
            }

            if (bccomp($this->balances->free($account), (string) $lockedPeriod->planned_amount, 2) < 0) {
                $this->fail('amount', 'Uang bebas tidak mencukupi.');
            }

            $lockedPeriod->update([
                'allocated_amount' => $lockedPeriod->planned_amount,
                'status' => 'open',
                'funded_at' => now('UTC'),
            ]);
            $this->completePeriodReminders($lockedPeriod, 'budget_allocation', $operationId);
        }, attempts: 3);
    }

    public function allocate(
        User $user,
        Saving $saving,
        FinancialAccount $account,
        string $amount,
        string $key,
        ?BudgetPeriod $period = null,
        ?int $reminderLogId = null,
    ): void {
        DB::transaction(function () use ($user, $saving, $account, $amount, $key, $period, $reminderLogId): void {
            $lockedAccount = $user->financialAccounts()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();
            $plan = $period !== null
                ? $user->budgetPlans()
                    ->whereKey($period->budget_plan_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                : null;
            $lockedPeriod = $period !== null
                ? $plan->periods()->whereKey($period->id)->lockForUpdate()->firstOrFail()
                : null;
            $lockedSaving = $user->savings()
                ->whereKey($saving->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSaving->status !== 'active'
                || $lockedSaving->currency !== $lockedAccount->currency
                || bccomp($amount, '0', 2) <= 0) {
                $this->fail('amount', 'Savings, akun, atau nominal tidak valid.');
            }

            if ($plan !== null
                && ($plan->type !== 'goal'
                    || $plan->saving_id !== $lockedSaving->id
                    || $lockedPeriod->status === 'closed')) {
                $this->fail('period', 'Periode setoran goal tidak valid.');
            }

            if ($plan === null && $lockedSaving->goalPlan()->exists()) {
                $this->fail('saving', 'Gunakan setoran goal untuk savings ini.');
            }

            if ($plan !== null && $reminderLogId !== null) {
                $this->fail('reminder_log_id', 'Pengingat savings mandiri tidak berlaku untuk goal.');
            }

            $operationId = $this->operation(
                $user,
                $key,
                ['allocate', $saving->id, $account->id, $amount, $period?->id, $reminderLogId],
            );

            if ($operationId === null) {
                return;
            }

            if (bccomp($this->balances->free($lockedAccount), $amount, 2) < 0) {
                $this->fail('amount', 'Uang bebas tidak mencukupi.');
            }

            $lockedSaving->logs()->create([
                'financial_account_id' => $lockedAccount->id,
                'type' => 'allocation',
                'amount' => $amount,
                'reason' => $plan ? 'goal_contribution' : 'direct_allocation',
                'budget_period_id' => $lockedPeriod?->id,
                'operation_id' => $operationId,
                'idempotency_key' => $key,
                'occurred_at' => now('UTC'),
            ]);

            if ($lockedPeriod !== null) {
                $contributed = $this->balances->goalContribution($lockedPeriod);

                if (bccomp($contributed, (string) $lockedPeriod->planned_amount, 2) >= 0) {
                    $lockedPeriod->update([
                        'status' => 'funded',
                        'contribution_completed_at' => now('UTC'),
                    ]);
                    $this->completePeriodReminders($lockedPeriod, 'goal_contribution', $operationId);
                }
            }

            if ($reminderLogId !== null) {
                $reminderLog = RecurringReminderLog::query()
                    ->whereKey($reminderLogId)
                    ->whereHas(
                        'reminder',
                        fn ($query) => $query->where('user_id', $user->id)
                            ->where('purpose', 'saving_allocation')
                            ->where('saving_id', $lockedSaving->id),
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($reminderLog->status, ['pending', 'snoozed'], true)) {
                    $this->fail('reminder_log_id', 'Pengingat ini tidak dapat diselesaikan.');
                }

                $reminderLog->update([
                    'status' => 'completed',
                    'snoozed_until' => null,
                    'completed_at' => now('UTC'),
                    'completion_operation_id' => $operationId,
                ]);
            }
        }, attempts: 3);
    }

    public function releaseSaving(
        User $user,
        Saving $saving,
        FinancialAccount $account,
        string $amount,
        string $key,
    ): void {
        DB::transaction(function () use ($user, $saving, $account, $amount, $key): void {
            $lockedAccount = $user->financialAccounts()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSaving = $user->savings()
                ->whereKey($saving->id)
                ->lockForUpdate()
                ->firstOrFail();
            $operationId = $this->operation(
                $user,
                $key,
                ['release', $saving->id, $account->id, $amount],
            );

            if ($operationId === null) {
                return;
            }

            if ($lockedSaving->currency !== $lockedAccount->currency
                || bccomp($amount, '0', 2) <= 0
                || bccomp($this->balances->savingBalance($lockedSaving, $account->id), $amount, 2) < 0) {
                $this->fail('amount', 'Dana savings pada akun ini tidak mencukupi.');
            }

            $lockedSaving->logs()->create([
                'financial_account_id' => $account->id,
                'type' => 'release',
                'amount' => $amount,
                'reason' => 'free_release',
                'operation_id' => $operationId,
                'idempotency_key' => $key,
                'occurred_at' => now('UTC'),
            ]);
        }, attempts: 3);
    }

    public function reverseAllocation(User $user, SavingLog $log, string $key): void
    {
        DB::transaction(function () use ($user, $log, $key): void {
            $account = $user->financialAccounts()
                ->whereKey($log->financial_account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $period = $log->budget_period_id !== null
                ? BudgetPeriod::query()->whereKey($log->budget_period_id)->firstOrFail()
                : null;
            $plan = $period !== null
                ? $user->budgetPlans()
                    ->whereKey($period->budget_plan_id)
                    ->lockForUpdate()
                    ->firstOrFail()
                : null;
            $lockedPeriod = $period !== null
                ? $plan->periods()->whereKey($period->id)->lockForUpdate()->firstOrFail()
                : null;
            $saving = $user->savings()
                ->whereKey($log->saving_id)
                ->lockForUpdate()
                ->firstOrFail();
            $original = $saving->logs()
                ->whereKey($log->id)
                ->lockForUpdate()
                ->firstOrFail();
            $operationId = $this->operation($user, $key, ['reverse-allocation', $log->id]);

            if ($operationId === null) {
                return;
            }

            if (! in_array($original->reason, ['direct_allocation', 'goal_contribution'], true)
                || $original->type !== 'allocation'
                || $saving->logs()->where('reverses_log_id', $original->id)->exists()
                || bccomp(
                    $this->balances->savingBalance($saving, $account->id),
                    (string) $original->amount,
                    2,
                ) < 0) {
                $this->fail('log', 'Alokasi tidak dapat dibalik sebelum dana dipulihkan.');
            }

            $saving->logs()->create([
                'financial_account_id' => $account->id,
                'type' => 'release',
                'amount' => $original->amount,
                'reason' => 'reversal',
                'budget_period_id' => $lockedPeriod?->id,
                'operation_id' => $operationId,
                'idempotency_key' => $key.':log',
                'reverses_log_id' => $original->id,
                'occurred_at' => now('UTC'),
            ]);

            if ($lockedPeriod !== null
                && $lockedPeriod->status !== 'closed'
                && bccomp(
                    $this->balances->goalContribution($lockedPeriod),
                    (string) $lockedPeriod->planned_amount,
                    2,
                ) < 0) {
                $lockedPeriod->update([
                    'status' => 'awaiting_funding',
                    'contribution_completed_at' => null,
                ]);
            }
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{saving_id: int, amount: string}>  $parts
     */
    public function claim(User $user, BudgetPeriod $period, array $parts, string $key): void
    {
        DB::transaction(function () use ($user, $period, $parts, $key): void {
            $account = $user->financialAccounts()
                ->whereKey($period->financial_account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $plan = $user->budgetPlans()
                ->whereKey($period->budget_plan_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPeriod = $plan->periods()
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();
            $savingIds = collect($parts)->pluck('saving_id')->unique()->sort()->values();
            $savings = $user->savings()
                ->whereIn('id', $savingIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $operationId = $this->operation($user, $key, ['claim', $period->id, $parts]);

            if ($operationId === null) {
                return;
            }

            if ($plan->type !== 'spending' || $lockedPeriod->status !== 'open'
                || $savings->count() !== $savingIds->count()) {
                $this->fail('period', 'Periode atau tujuan klaim tidak valid.');
            }

            $total = '0.00';

            foreach ($parts as $part) {
                $saving = $savings->get($part['saving_id']);

                if ($saving->status !== 'active'
                    || $saving->currency !== $lockedPeriod->currency
                    || bccomp((string) $part['amount'], '0', 2) <= 0) {
                    $this->fail('parts', 'Tujuan, mata uang, atau nominal klaim tidak valid.');
                }

                $total = bcadd($total, (string) $part['amount'], 2);
            }

            if (bccomp($total, $this->balances->periodRemaining($lockedPeriod), 2) > 0) {
                $this->fail('parts', 'Jumlah klaim melebihi sisa budget.');
            }

            foreach ($parts as $index => $part) {
                $claim = $lockedPeriod->claims()->create([
                    'saving_id' => $part['saving_id'],
                    'financial_account_id' => $account->id,
                    'amount' => $part['amount'],
                    'idempotency_key' => $key.':'.$index,
                    'claimed_at' => now('UTC'),
                ]);
                $savings->get($part['saving_id'])->logs()->create([
                    'financial_account_id' => $account->id,
                    'type' => 'allocation',
                    'amount' => $part['amount'],
                    'reason' => 'budget_claim',
                    'budget_savings_claim_id' => $claim->id,
                    'operation_id' => $operationId,
                    'idempotency_key' => $key.':claim:'.$index,
                    'occurred_at' => now('UTC'),
                ]);
            }

            if (bccomp($this->balances->periodRemaining($lockedPeriod), '0', 2) === 0) {
                $this->completePeriodReminders(
                    $lockedPeriod,
                    'budget_savings_claim',
                    $operationId,
                );
            }
        }, attempts: 3);
    }

    public function releasePeriod(User $user, BudgetPeriod $period, string $amount, string $key): void
    {
        DB::transaction(function () use ($user, $period, $amount, $key): void {
            $user->financialAccounts()
                ->whereKey($period->financial_account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $plan = $user->budgetPlans()
                ->whereKey($period->budget_plan_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPeriod = $plan->periods()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if ($this->operation($user, $key, ['release-period', $period->id, $amount]) === null) {
                return;
            }

            if ($plan->type !== 'spending' || $lockedPeriod->status !== 'open'
                || bccomp($amount, '0', 2) <= 0
                || bccomp($amount, $this->balances->periodRemaining($lockedPeriod), 2) > 0) {
                $this->fail('amount', 'Sisa budget tidak mencukupi.');
            }

            $lockedPeriod->update([
                'released_amount' => bcadd((string) $lockedPeriod->released_amount, $amount, 2),
            ]);
        }, attempts: 3);
    }

    public function reverseClaim(User $user, BudgetSavingsClaim $claim, string $key): void
    {
        DB::transaction(function () use ($user, $claim, $key): void {
            $account = $user->financialAccounts()
                ->whereKey($claim->financial_account_id)
                ->lockForUpdate()
                ->firstOrFail();
            $planId = BudgetPeriod::query()
                ->whereKey($claim->budget_period_id)
                ->value('budget_plan_id');
            $plan = $user->budgetPlans()
                ->whereKey($planId)
                ->lockForUpdate()
                ->firstOrFail();
            $period = $plan->periods()
                ->whereKey($claim->budget_period_id)
                ->lockForUpdate()
                ->firstOrFail();
            $saving = $user->savings()
                ->whereKey($claim->saving_id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedClaim = $period->claims()
                ->whereKey($claim->id)
                ->lockForUpdate()
                ->firstOrFail();
            $operationId = $this->operation($user, $key, ['reverse-claim', $claim->id]);

            if ($operationId === null) {
                return;
            }

            if ($period->status !== 'open' || $lockedClaim->reversed_at !== null) {
                $this->fail('claim', 'Klaim ini tidak dapat dibalik.');
            }

            if (bccomp(
                $this->balances->savingBalance($saving, $account->id),
                (string) $lockedClaim->amount,
                2,
            ) < 0) {
                $this->fail('claim', 'Pulihkan dana savings sebelum membalik klaim.');
            }

            $original = $saving->logs()
                ->where('budget_savings_claim_id', $lockedClaim->id)
                ->lockForUpdate()
                ->firstOrFail();
            $saving->logs()->create([
                'financial_account_id' => $account->id,
                'type' => 'release',
                'amount' => $lockedClaim->amount,
                'reason' => 'reversal',
                'budget_savings_claim_id' => null,
                'operation_id' => $operationId,
                'idempotency_key' => $key.':log',
                'reverses_log_id' => $original->id,
                'occurred_at' => now('UTC'),
            ]);
            $lockedClaim->update([
                'reversed_at' => now('UTC'),
                'reversal_operation_id' => $operationId,
            ]);
        }, attempts: 3);
    }

    public function closePeriod(User $user, BudgetPeriod $period): void
    {
        DB::transaction(function () use ($user, $period): void {
            $user->financialAccounts()->whereKey($period->financial_account_id)->lockForUpdate()->firstOrFail();
            $plan = $user->budgetPlans()->whereKey($period->budget_plan_id)->lockForUpdate()->firstOrFail();
            $lockedPeriod = $plan->periods()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            if ($plan->type === 'spending'
                && bccomp($this->balances->periodRemaining($lockedPeriod), '0', 2) !== 0) {
                $this->fail('period', 'Selesaikan sisa budget sebelum menutup periode.');
            }

            $lockedPeriod->update(['status' => 'closed', 'closed_at' => now('UTC')]);
        }, attempts: 3);
    }

    /**
     * @param  array<int, mixed>  $payload
     */
    private function operation(User $user, string $key, array $payload): ?string
    {
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $operationId = (string) Str::uuid();
        $inserted = DB::table('fund_operations')->insertOrIgnore([
            'user_id' => $user->id,
            'idempotency_key' => $key,
            'payload_hash' => $hash,
            'operation_id' => $operationId,
            'created_at' => now('UTC'),
            'updated_at' => now('UTC'),
        ]);

        if ($inserted === 1) {
            return $operationId;
        }

        $existing = FundOperation::query()
            ->where('user_id', $user->id)
            ->where('idempotency_key', $key)
            ->firstOrFail();

        if (! hash_equals($existing->payload_hash, $hash)) {
            $this->fail('idempotency_key', 'Kunci aksi pernah dipakai untuk data berbeda.');
        }

        return null;
    }

    private function completePeriodReminders(
        BudgetPeriod $period,
        string $purpose,
        string $operationId,
    ): void {
        $logs = RecurringReminderLog::query()
            ->where('budget_period_id', $period->id)
            ->whereIn('status', ['pending', 'snoozed'])
            ->whereHas(
                'reminder',
                fn ($query) => $query->where('budget_plan_id', $period->budget_plan_id)
                    ->where('purpose', $purpose),
            )
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($logs as $log) {
            $log->update([
                'status' => 'completed',
                'snoozed_until' => null,
                'completed_at' => now('UTC'),
                'completion_operation_id' => $operationId,
            ]);
        }
    }

    private function monthlyAnchor(CarbonImmutable $anchor, int $months): CarbonImmutable
    {
        $month = $anchor->startOfMonth()->addMonthsNoOverflow($months);
        $day = min($anchor->day, $month->daysInMonth);

        return $month->setDay($day);
    }

    private function nextAnchorAfter(BudgetPlan $plan, CarbonImmutable $starts): CarbonImmutable
    {
        $anchor = CarbonImmutable::parse($plan->starts_on);

        for ($index = 1; $index <= 240; $index++) {
            $candidate = $this->monthlyAnchor($anchor, $index * $plan->interval);

            if ($candidate->greaterThan($starts)) {
                return $candidate;
            }
        }

        $this->fail('starts_on', 'Jadwal plan terlalu panjang.');
    }

    private function nextReminderAfter(
        RecurringReminder $reminder,
        CarbonImmutable $instant,
    ): ?CarbonImmutable {
        $anchor = $reminder->starts_at->setTimezone($reminder->timezone);

        for ($index = 0; $index <= 240; $index++) {
            $local = $reminder->frequency === 'monthly'
                ? $this->monthlyAnchor($anchor, $index * $reminder->interval)
                    ->setTime($anchor->hour, $anchor->minute, $anchor->second)
                : $anchor;
            $candidate = $local->setTimezone('UTC');

            if ($candidate->greaterThan($reminder->ends_at ?? $candidate)) {
                return null;
            }

            if ($candidate->greaterThan($instant)) {
                return $candidate;
            }

            if ($reminder->frequency !== 'monthly') {
                return null;
            }
        }

        return null;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
