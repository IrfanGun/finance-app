<?php

namespace App\Services;

use App\Models\BudgetPeriod;
use App\Models\BudgetPlan;
use App\Models\FinancialAccount;
use App\Models\RecurringReminder;
use App\Models\RecurringReminderLog;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\RecurringReminderDue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringReminderService
{
    public function __construct(
        private FundService $funds,
        private FundBalanceService $balances,
    ) {}

    private const MAX_PERIODS_PER_RULE_PER_RUN = 20;

    private const MAX_PERIODS_PER_RUN = 200;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): RecurringReminder
    {
        $data['purpose'] = $data['purpose'] ?? 'transaction';
        $data['type'] = $data['purpose'] === 'transaction' ? $data['type'] : null;
        $data['budget_plan_id'] = $data['purpose'] === 'transaction' ? null : ($data['budget_plan_id'] ?? null);
        $data['saving_id'] = $data['purpose'] === 'saving_allocation' ? $data['saving_id'] : null;
        $data = $this->normalizeSchedule($data);
        $data['next_reminder_at'] = $data['starts_at'];
        $data['status'] = 'active';

        return $user->recurringReminders()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RecurringReminder $reminder, array $data): RecurringReminder
    {
        $data['purpose'] = $data['purpose'] ?? $reminder->purpose;
        $data['type'] = $data['purpose'] === 'transaction' ? $data['type'] : null;
        $data['budget_plan_id'] = $data['purpose'] === 'transaction' ? null : ($data['budget_plan_id'] ?? null);
        $data['saving_id'] = $data['purpose'] === 'saving_allocation' ? $data['saving_id'] : null;
        $data = $this->normalizeSchedule($data);

        return DB::transaction(function () use ($reminder, $data): RecurringReminder {
            $lockedReminder = RecurringReminder::query()
                ->whereKey($reminder->id)
                ->lockForUpdate()
                ->firstOrFail();
            $scheduleChanged = $lockedReminder->frequency !== $data['frequency']
                || $lockedReminder->interval !== (int) $data['interval']
                || $lockedReminder->timezone !== $data['timezone']
                || ! $lockedReminder->starts_at->equalTo($data['starts_at']);

            if ($scheduleChanged) {
                if ($data['starts_at']->lessThan(CarbonImmutable::now('UTC'))) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'Jadwal baru harus sekarang atau di masa mendatang.',
                    ]);
                }

                $data['next_reminder_at'] = $data['starts_at'];
            } else {
                unset($data['starts_at'], $data['next_reminder_at']);
            }

            if ($data['ends_at'] !== null
                && $data['ends_at']->lessThan($scheduleChanged ? $data['starts_at'] : $lockedReminder->starts_at)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Batas akhir harus sama dengan atau setelah jadwal pertama.',
                ]);
            }

            if ($data['ends_at'] !== null
                && $lockedReminder->next_reminder_at !== null
                && $lockedReminder->next_reminder_at->greaterThan($data['ends_at'])) {
                $data['status'] = 'ended';
                $data['next_reminder_at'] = null;
            }

            $lockedReminder->fill($data)->save();

            return $lockedReminder->refresh();
        }, attempts: 3);
    }

    public function pause(RecurringReminder $reminder): void
    {
        DB::transaction(function () use ($reminder): void {
            $lockedReminder = RecurringReminder::query()
                ->whereKey($reminder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReminder->status === 'active') {
                $lockedReminder->update(['status' => 'paused']);
            }
        }, attempts: 3);
    }

    public function resume(RecurringReminder $reminder): void
    {
        DB::transaction(function () use ($reminder): void {
            $lockedReminder = RecurringReminder::query()
                ->whereKey($reminder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReminder->status !== 'paused') {
                return;
            }

            $next = $this->nextOccurrenceAtOrAfter(
                $lockedReminder,
                CarbonImmutable::now('UTC'),
            );

            if ($next === null) {
                $lockedReminder->update([
                    'status' => 'ended',
                    'next_reminder_at' => null,
                ]);

                return;
            }

            $lockedReminder->update([
                'status' => 'active',
                'next_reminder_at' => $next,
            ]);
        }, attempts: 3);
    }

    public function end(RecurringReminder $reminder): void
    {
        DB::transaction(function () use ($reminder): void {
            $lockedReminder = RecurringReminder::query()
                ->whereKey($reminder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReminder->status !== 'ended') {
                $lockedReminder->update([
                    'status' => 'ended',
                    'next_reminder_at' => null,
                ]);
            }
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function complete(
        User $user,
        int $reminderId,
        int $logId,
        array $data,
        TransactionService $transactions,
    ): ?Transaction {
        return DB::transaction(function () use ($user, $reminderId, $logId, $data, $transactions): ?Transaction {
            $reminder = $user->recurringReminders()
                ->whereKey($reminderId)
                ->firstOrFail();
            $existingLog = $reminder->logs()->whereKey($logId)->firstOrFail();

            if ($existingLog->status === 'completed') {
                return $existingLog->transaction;
            }

            $account = $user->financialAccounts()
                ->whereKey($data['account_id'])
                ->lockForUpdate()
                ->firstOrFail();
            $periodId = $data['budget_period_id'] ?? null;
            $savingId = $data['saving_id'] ?? null;
            $planId = $periodId !== null
                ? BudgetPeriod::query()->whereKey($periodId)->value('budget_plan_id')
                : ($savingId !== null
                    ? BudgetPlan::query()->where('saving_id', $savingId)->value('id')
                    : null);

            if ($planId !== null) {
                $user->budgetPlans()
                    ->whereKey($planId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            if ($periodId !== null) {
                BudgetPeriod::query()->whereKey($periodId)->lockForUpdate()->firstOrFail();
            }

            if ($savingId !== null) {
                $user->savings()->whereKey($savingId)->lockForUpdate()->firstOrFail();
            }

            $log = $reminder->logs()
                ->whereKey($logId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reminder->purpose !== 'transaction') {
                throw ValidationException::withMessages([
                    'reminder' => 'Gunakan tindakan budget atau savings untuk pengingat ini.',
                ]);
            }

            if ($log->status === 'completed') {
                return $log->transaction;
            }

            if (! in_array($log->status, ['pending', 'snoozed'], true)) {
                throw ValidationException::withMessages([
                    'reminder' => 'Pengingat ini sudah dilewati dan tidak dapat dicatat.',
                ]);
            }

            $category = isset($data['category_id'])
                ? $user->categories()->findOrFail($data['category_id'])
                : null;

            if ($category !== null
                && ($category->type !== $reminder->type || ! $category->is_active)) {
                throw ValidationException::withMessages([
                    'category_id' => 'Pilih kategori aktif yang sesuai dengan jenis transaksi.',
                ]);
            }

            $transaction = $transactions->create($user, [
                'account_id' => $account->id,
                'category_id' => $category?->id,
                'type' => $reminder->type,
                'amount' => $data['amount'],
                'date' => $data['date'],
                'title' => $data['title'],
                'note' => $data['note'] ?? null,
                'budget_period_id' => $data['budget_period_id'] ?? null,
                'saving_id' => $data['saving_id'] ?? null,
            ]);

            $log->update([
                'transaction_id' => $transaction->id,
                'status' => 'completed',
                'completed_at' => now('UTC'),
                'snoozed_until' => null,
            ]);

            return $transaction;
        }, attempts: 3);
    }

    /**
     * @return array{logs_created: int, notifications_sent: int}
     */
    public function processDue(): array
    {
        $now = CarbonImmutable::now('UTC');
        $summary = [
            'logs_created' => 0,
            'notifications_sent' => 0,
        ];

        $remaining = self::MAX_PERIODS_PER_RUN;

        RecurringReminder::query()
            ->where('status', 'active')
            ->whereNotNull('next_reminder_at')
            ->where('next_reminder_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(100, function ($reminders) use ($now, &$summary, &$remaining): bool {
                foreach ($reminders as $reminder) {
                    if ($remaining <= 0) {
                        return false;
                    }

                    $created = $this->processReminderPeriods(
                        $reminder->id,
                        $now,
                        min(self::MAX_PERIODS_PER_RULE_PER_RUN, $remaining),
                    );
                    $summary['logs_created'] += $created['logs_created'];
                    $summary['notifications_sent'] += $created['notifications_sent'];
                    $remaining -= $created['periods_processed'];

                    if ($remaining <= 0) {
                        return false;
                    }
                }

                return true;
            });

        $summary['notifications_sent'] += $this->processDueSnoozes($now, self::MAX_PERIODS_PER_RUN);

        return $summary;
    }

    public function nextOccurrenceAtOrAfter(
        RecurringReminder $reminder,
        CarbonImmutable $instant,
    ): ?CarbonImmutable {
        $anchor = $reminder->starts_at->setTimezone('UTC');
        $localInstant = $instant->setTimezone($reminder->timezone);
        $localAnchor = $anchor->setTimezone($reminder->timezone);
        $interval = max(1, $reminder->interval);

        $index = match ($reminder->frequency) {
            'daily' => max(0, (int) floor($localAnchor->diffInDays($localInstant, false) / $interval)),
            'weekly' => max(0, (int) floor($localAnchor->diffInDays($localInstant, false) / (7 * $interval))),
            'monthly' => max(0, (int) floor($this->monthDifference($localAnchor, $localInstant) / $interval)),
            'yearly' => max(0, (int) floor(($localInstant->year - $localAnchor->year) / $interval)),
            default => 0,
        };

        $candidate = $this->occurrenceAtIndex($reminder, $index);

        while ($candidate->lessThan($instant)) {
            $index++;
            $candidate = $this->occurrenceAtIndex($reminder, $index);
        }

        if ($reminder->ends_at !== null && $candidate->greaterThan($reminder->ends_at)) {
            return null;
        }

        return $candidate;
    }

    private function processReminderPeriods(int $reminderId, CarbonImmutable $now, int $limit): array
    {
        $summary = [
            'logs_created' => 0,
            'notifications_sent' => 0,
            'periods_processed' => 0,
        ];

        for ($processed = 0; $processed < $limit; $processed++) {
            $result = DB::transaction(function () use ($reminderId, $now): array {
                $snapshot = RecurringReminder::query()->find($reminderId);
                $lockedPlan = null;

                if ($snapshot?->budget_plan_id !== null) {
                    $planAccountId = BudgetPlan::query()
                        ->whereKey($snapshot->budget_plan_id)
                        ->value('financial_account_id');
                    $account = FinancialAccount::query()
                        ->whereKey($planAccountId)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $lockedPlan = BudgetPlan::query()
                        ->whereKey($snapshot->budget_plan_id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $reminder = RecurringReminder::query()
                    ->whereKey($reminderId)
                    ->lockForUpdate()
                    ->first();

                if ($reminder === null
                    || $reminder->status !== 'active'
                    || $reminder->next_reminder_at === null
                    || $reminder->next_reminder_at->greaterThan($now)) {
                    return ['created' => false, 'notified' => false, 'continue' => false, 'processed' => false];
                }

                if ($lockedPlan !== null && $lockedPlan->status !== 'active') {
                    if (in_array($lockedPlan->status, ['ended', 'completed'], true)) {
                        $reminder->update([
                            'status' => 'ended',
                            'next_reminder_at' => null,
                        ]);
                    }

                    return ['created' => false, 'notified' => false, 'continue' => false, 'processed' => false];
                }

                if ($reminder->purpose === 'saving_allocation'
                    && ($reminder->pocket?->status !== 'active'
                        || $reminder->pocket?->goalPlan !== null)) {
                    $reminder->update([
                        'status' => 'ended',
                        'next_reminder_at' => null,
                    ]);

                    return ['created' => false, 'notified' => false, 'continue' => false, 'processed' => false];
                }

                $scheduledAt = $reminder->next_reminder_at;

                if ($reminder->ends_at !== null && $scheduledAt->greaterThan($reminder->ends_at)) {
                    $reminder->update([
                        'status' => 'ended',
                        'next_reminder_at' => null,
                    ]);

                    return ['created' => false, 'notified' => false, 'continue' => false, 'processed' => false];
                }

                $periodId = null;

                if ($lockedPlan !== null) {
                    $localDate = $scheduledAt->setTimezone($lockedPlan->timezone)->toDateString();
                    $this->funds->openDuePeriods($lockedPlan, $account, $localDate);
                    $periodId = $lockedPlan->periods()
                        ->whereDate('starts_on', '<=', $localDate)
                        ->whereDate('ends_on', '>=', $localDate)
                        ->value('id');

                    if ($periodId === null) {
                        throw ValidationException::withMessages([
                            'reminder' => 'Periode budget untuk jadwal pengingat belum dapat ditemukan.',
                        ]);
                    }
                }

                $alreadySatisfied = false;

                if ($periodId !== null) {
                    $period = BudgetPeriod::query()->findOrFail($periodId);
                    $alreadySatisfied = match ($reminder->purpose) {
                        'budget_allocation' => $period->status === 'open',
                        'budget_savings_claim' => bccomp(
                            $this->balances->periodRemaining($period),
                            '0',
                            2,
                        ) === 0,
                        'goal_contribution' => $period->status === 'funded'
                            || bccomp(
                                bcadd(
                                    $this->balances->savingBalance($lockedPlan->pocket),
                                    (string) $lockedPlan->user->transactions()
                                        ->where('budget_plan_id', $lockedPlan->id)
                                        ->where('type', 'expense')
                                        ->sum('amount'),
                                    2,
                                ),
                                (string) $lockedPlan->target_amount,
                                2,
                            ) >= 0,
                        default => false,
                    };

                    if ($reminder->purpose === 'goal_contribution'
                        && $period->ends_on->toDateString()
                            < $now->setTimezone($lockedPlan->timezone)->toDateString()) {
                        $alreadySatisfied = true;
                    }
                }

                $log = $alreadySatisfied
                    ? null
                    : $reminder->logs()->firstOrCreate(
                        ['scheduled_at' => $scheduledAt],
                        [
                            'status' => 'pending',
                            'budget_period_id' => $periodId,
                        ],
                    );
                $created = $log?->wasRecentlyCreated ?? false;
                $notified = false;

                if ($log !== null && $log->notified_at === null) {
                    $this->sendNotification($reminder, $log, 'initial');
                    $log->update(['notified_at' => $now]);
                    $notified = true;
                }

                $index = $this->indexAtOrAfter($reminder, $scheduledAt);
                $next = $reminder->purpose === 'budget_savings_claim' && $lockedPlan !== null
                    ? $this->nextBudgetClaimAt($lockedPlan, $period, $reminder)
                    : $this->occurrenceAtIndex($reminder, $index + 1);
                $status = $next === null
                    || ($reminder->ends_at !== null && $next->greaterThan($reminder->ends_at))
                    ? 'ended'
                    : 'active';

                $reminder->update([
                    'status' => $status,
                    'next_reminder_at' => $status === 'active' ? $next : null,
                ]);

                return [
                    'created' => $created,
                    'notified' => $notified,
                    'continue' => $status === 'active',
                    'processed' => true,
                ];
            }, attempts: 3);

            $summary['logs_created'] += (int) $result['created'];
            $summary['notifications_sent'] += (int) $result['notified'];
            $summary['periods_processed'] += (int) $result['processed'];

            if (! $result['continue']) {
                break;
            }
        }

        return $summary;
    }

    private function processDueSnoozes(CarbonImmutable $now, int $limit): int
    {
        $sent = 0;
        $processed = 0;

        RecurringReminderLog::query()
            ->where('status', 'snoozed')
            ->where('snoozed_until', '<=', $now)
            ->whereHas('reminder', fn (Builder $query) => $query->where('status', 'active'))
            ->with('reminder')
            ->orderBy('id')
            ->chunkById(100, function ($logs) use ($now, $limit, &$sent, &$processed): bool {
                foreach ($logs as $log) {
                    if ($processed >= $limit) {
                        return false;
                    }

                    $processed++;

                    $wasSent = DB::transaction(function () use ($log, $now): bool {
                        $reminder = RecurringReminder::query()
                            ->whereKey($log->recurring_reminder_id)
                            ->lockForUpdate()
                            ->first();
                        $lockedLog = RecurringReminderLog::query()
                            ->whereKey($log->id)
                            ->lockForUpdate()
                            ->first();

                        if ($reminder === null
                            || $reminder->status !== 'active'
                            || $lockedLog === null
                            || $lockedLog->status !== 'snoozed'
                            || $lockedLog->snoozed_until === null
                            || $lockedLog->snoozed_until->greaterThan($now)) {
                            return false;
                        }

                        if (($reminder->budget_plan_id !== null
                                && $reminder->plan?->status !== 'active')
                            || ($reminder->saving_id !== null
                                && $reminder->pocket?->status !== 'active')) {
                            return false;
                        }

                        $this->sendNotification($reminder, $lockedLog, 'snooze');
                        $lockedLog->update([
                            'status' => 'pending',
                            'notified_at' => $now,
                            'snoozed_until' => null,
                        ]);

                        return true;
                    }, attempts: 3);

                    $sent += (int) $wasSent;
                }

                return $processed < $limit;
            });

        return $sent;
    }

    private function sendNotification(
        RecurringReminder $reminder,
        RecurringReminderLog $log,
        string $kind,
    ): void {
        $reminder->loadMissing('account', 'user');
        $reminder->user->notify(new RecurringReminderDue($reminder, $log, $kind));
    }

    private function occurrenceAtIndex(RecurringReminder $reminder, int $index): CarbonImmutable
    {
        $anchor = $reminder->starts_at->setTimezone($reminder->timezone);
        $interval = max(1, $reminder->interval);
        $occurrence = match ($reminder->frequency) {
            'daily' => $anchor->addDays($interval * $index),
            'weekly' => $anchor->addWeeks($interval * $index),
            'monthly' => $this->monthlyOccurrence($anchor, $interval * $index),
            'yearly' => $this->yearlyOccurrence($anchor, $interval * $index),
            default => $anchor,
        };

        return $occurrence->setTimezone('UTC');
    }

    private function monthlyOccurrence(CarbonImmutable $anchor, int $months): CarbonImmutable
    {
        $month = $anchor->startOfMonth()->addMonthsNoOverflow($months);
        $day = min($anchor->day, $month->daysInMonth);

        return $month->setDate($month->year, $month->month, $day)
            ->setTime($anchor->hour, $anchor->minute, $anchor->second);
    }

    private function nextBudgetClaimAt(
        BudgetPlan $plan,
        BudgetPeriod $period,
        RecurringReminder $reminder,
    ): ?CarbonImmutable {
        if (! $plan->is_recurring) {
            return null;
        }

        $anchor = CarbonImmutable::parse(
            $plan->starts_on->toDateString().' 09:00:00',
            $plan->timezone,
        );

        for ($index = 0; $index < 240; $index++) {
            $start = $this->monthlyOccurrence($anchor, $index * $plan->interval);

            if ($start->toDateString() !== $period->starts_on->toDateString()) {
                continue;
            }

            $nextStart = $this->monthlyOccurrence($anchor, ($index + 1) * $plan->interval);

            if ($plan->ends_on !== null
                && $nextStart->toDateString() > $plan->ends_on->toDateString()) {
                return null;
            }

            $followingStart = $this->monthlyOccurrence(
                $anchor,
                ($index + 2) * $plan->interval,
            );
            $nextEnd = $followingStart->subDay();

            if ($plan->ends_on !== null
                && $nextEnd->toDateString() > $plan->ends_on->toDateString()) {
                $nextEnd = CarbonImmutable::parse(
                    $plan->ends_on->toDateString().' 09:00:00',
                    $plan->timezone,
                );
            }

            return $nextEnd->setTimezone('UTC');
        }

        return null;
    }

    private function yearlyOccurrence(CarbonImmutable $anchor, int $years): CarbonImmutable
    {
        $year = $anchor->year + $years;
        $daysInMonth = CarbonImmutable::create($year, $anchor->month, 1)->daysInMonth;
        $day = min($anchor->day, $daysInMonth);

        return $anchor->setDate($year, $anchor->month, $day);
    }

    private function monthDifference(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (($end->year - $start->year) * 12) + $end->month - $start->month;
    }

    private function indexAtOrAfter(RecurringReminder $reminder, CarbonImmutable $instant): int
    {
        $anchor = $reminder->starts_at->setTimezone($reminder->timezone);
        $localInstant = $instant->setTimezone($reminder->timezone);
        $interval = max(1, $reminder->interval);

        $index = match ($reminder->frequency) {
            'daily' => max(0, (int) floor($anchor->diffInDays($localInstant, false) / $interval)),
            'weekly' => max(0, (int) floor($anchor->diffInDays($localInstant, false) / (7 * $interval))),
            'monthly' => max(0, (int) floor($this->monthDifference($anchor, $localInstant) / $interval)),
            'yearly' => max(0, (int) floor(($localInstant->year - $anchor->year) / $interval)),
            default => 0,
        };

        while ($this->occurrenceAtIndex($reminder, $index)->lessThan($instant)) {
            $index++;
        }

        return $index;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeSchedule(array $data): array
    {
        $timezone = $data['timezone'];
        $data['starts_at'] = CarbonImmutable::createFromFormat(
            'Y-m-d\\TH:i',
            $data['starts_at'],
            $timezone,
        )->setTimezone('UTC');

        if (! empty($data['ends_at'])) {
            $data['ends_at'] = CarbonImmutable::createFromFormat(
                'Y-m-d\\TH:i',
                $data['ends_at'],
                $timezone,
            )->setTimezone('UTC');
        } else {
            $data['ends_at'] = null;
        }

        return $data;
    }
}
