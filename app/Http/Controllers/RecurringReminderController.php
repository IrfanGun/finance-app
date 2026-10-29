<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteRecurringReminderRequest;
use App\Http\Requests\RecurringReminderRequest;
use App\Models\RecurringReminder;
use App\Models\RecurringReminderLog;
use App\Services\RecurringReminderService;
use App\Services\TransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RecurringReminderController extends Controller
{
    public function index(Request $request): Response
    {
        $reminders = $request->user()->recurringReminders()
            ->with(['account', 'category'])
            ->latest('updated_at')
            ->get()
            ->map(fn (RecurringReminder $reminder): array => $this->reminderPayload($reminder, includeLogs: false));

        $view = $request->is('cms/recurring')
            ? 'Cms/RecurringCMS'
            : 'RecurringReminders/Index';

        return Inertia::render($view, [
            'reminders' => $reminders,
            'editReminderId' => $request->integer('edit') ?: null,
            'accounts' => $request->user()->financialAccounts()->orderBy('name')->get(['id', 'name', 'currency']),
            'categories' => $request->user()->categories()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'notifications' => $request->user()->notifications()
                ->where('type', 'App\\Notifications\\RecurringReminderDue')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'read_at' => $notification->read_at,
                    'data' => $notification->data,
                ]),
        ]);
    }

    public function show(Request $request, int $reminder): Response
    {
        $record = $request->user()->recurringReminders()
            ->with(['account', 'category'])
            ->with(['logs' => fn ($query) => $query->latest('scheduled_at')->limit(30)])
            ->findOrFail($reminder);

        return Inertia::render('RecurringReminders/Show', [
            'reminder' => $this->reminderPayload($record),
        ]);
    }

    public function store(
        RecurringReminderRequest $request,
        RecurringReminderService $reminders,
    ): RedirectResponse {
        $reminders->create($request->user(), $request->validated());

        if ($this->cameFromCms($request)) {
            return back()->with('success', 'Pengingat tabungan berhasil dibuat.');
        }

        return redirect()->route('recurring-reminders.index')
            ->with('success', 'Pengingat berulang berhasil dibuat.');
    }

    public function update(
        RecurringReminderRequest $request,
        int $reminder,
        RecurringReminderService $reminders,
    ): RedirectResponse {
        $record = $request->user()->recurringReminders()->findOrFail($reminder);
        $reminders->update($record, $request->validated());

        if ($this->cameFromCms($request)) {
            return back()->with('success', 'Pengingat berhasil diperbarui.');
        }

        return redirect()->route('recurring-reminders.index')
            ->with('success', 'Pengingat berhasil diperbarui.');
    }

    public function pause(Request $request, int $reminder, RecurringReminderService $reminders): RedirectResponse
    {
        $record = $request->user()->recurringReminders()->findOrFail($reminder);
        $reminders->pause($record);

        return back();
    }

    public function resume(Request $request, int $reminder, RecurringReminderService $reminders): RedirectResponse
    {
        $record = $request->user()->recurringReminders()->findOrFail($reminder);
        $reminders->resume($record);

        return back();
    }

    public function end(Request $request, int $reminder, RecurringReminderService $reminders): RedirectResponse
    {
        $record = $request->user()->recurringReminders()->findOrFail($reminder);
        $reminders->end($record);

        return back();
    }

    public function transactionForm(Request $request, int $reminder, int $log): Response
    {
        $record = $request->user()->recurringReminders()
            ->with(['account', 'category'])
            ->findOrFail($reminder);
        $reminderLog = $record->logs()->with('transaction')->findOrFail($log);

        if ($record->purpose !== 'transaction') {
            abort(404);
        }

        return Inertia::render('RecurringReminders/Transaction', [
            'reminder' => $this->reminderPayload($record, includeLogs: false),
            'log' => [
                'id' => $reminderLog->id,
                'status' => $reminderLog->status,
                'scheduled_at' => $reminderLog->scheduled_at
                    ->setTimezone($record->timezone)
                    ->format('Y-m-d H:i'),
                'transaction_id' => $reminderLog->transaction_id,
            ],
            'accounts' => $request->user()->financialAccounts()->orderBy('name')->get(['id', 'name', 'currency']),
            'categories' => $request->user()->categories()
                ->where('is_active', true)
                ->where('type', $record->type)
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'budgetPeriods' => $request->user()->budgetPlans()
                ->where('type', 'spending')
                ->with(['periods' => fn ($query) => $query->where('status', 'open')])
                ->get()
                ->flatMap(fn ($plan) => $plan->periods->map(fn ($period) => [
                    'id' => $period->id,
                    'name' => $plan->name,
                    'account_id' => $period->financial_account_id,
                    'starts_on' => $period->starts_on->toDateString(),
                    'ends_on' => $period->ends_on->toDateString(),
                ]))
                ->values(),
            'savings' => $request->user()->savings()
                ->where('status', 'active')
                ->get(['id', 'name', 'currency']),
        ]);
    }

    public function complete(
        CompleteRecurringReminderRequest $request,
        int $reminder,
        int $log,
        RecurringReminderService $reminders,
        TransactionService $transactions,
    ): RedirectResponse {
        $reminders->complete(
            $request->user(),
            $reminder,
            $log,
            $request->validated(),
            $transactions,
        );

        return redirect()->route('recurring-reminders.index')
            ->with('success', 'Transaksi berhasil dicatat dari pengingat.');
    }

    public function snooze(Request $request, int $reminder, int $log): RedirectResponse
    {
        $validated = $request->validate([
            'snoozed_until' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);
        $record = $request->user()->recurringReminders()->findOrFail($reminder);
        $snoozedUntil = CarbonImmutable::createFromFormat(
            'Y-m-d\\TH:i',
            $validated['snoozed_until'],
            $record->timezone,
        )->setTimezone('UTC');

        if ($snoozedUntil->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
            throw ValidationException::withMessages([
                'snoozed_until' => 'Waktu pengingat ulang harus berada di masa mendatang.',
            ]);
        }

        DB::transaction(function () use ($record, $log, $snoozedUntil): void {
            $lockedReminder = RecurringReminder::query()
                ->whereKey($record->id)
                ->lockForUpdate()
                ->firstOrFail();
            $reminderLog = $record->logs()->whereKey($log)->lockForUpdate()->firstOrFail();

            if ($lockedReminder->status === 'ended') {
                throw ValidationException::withMessages([
                    'reminder' => 'Pengingat yang dihentikan tidak dapat dijadwalkan ulang.',
                ]);
            }

            if ($snoozedUntil->lessThanOrEqualTo(CarbonImmutable::now('UTC'))) {
                throw ValidationException::withMessages([
                    'snoozed_until' => 'Waktu pengingat ulang harus berada di masa mendatang.',
                ]);
            }

            if (! in_array($reminderLog->status, ['pending', 'snoozed'], true)) {
                throw ValidationException::withMessages([
                    'reminder' => 'Pengingat yang sudah selesai atau dilewati tidak dapat ditunda.',
                ]);
            }

            $reminderLog->update([
                'status' => 'snoozed',
                'snoozed_until' => $snoozedUntil,
            ]);
        }, attempts: 3);

        return back();
    }

    public function skip(Request $request, int $reminder, int $log): RedirectResponse
    {
        DB::transaction(function () use ($request, $reminder, $log): void {
            $record = $request->user()->recurringReminders()
                ->whereKey($reminder)
                ->firstOrFail();
            $reminderLog = $record->logs()
                ->whereKey($log)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($reminderLog->status, ['pending', 'snoozed'], true)) {
                throw ValidationException::withMessages([
                    'reminder' => 'Pengingat ini sudah selesai atau dilewati.',
                ]);
            }

            $reminderLog->update([
                'status' => 'skipped',
                'snoozed_until' => null,
            ]);
        }, attempts: 3);

        return back();
    }

    private function cameFromCms(Request $request): bool
    {
        $refererPath = parse_url($request->header('referer', ''), PHP_URL_PATH);

        return is_string($refererPath) && str_starts_with($refererPath, '/cms/');
    }

    /**
     * @return array<string, mixed>
     */
    private function reminderPayload(RecurringReminder $reminder, bool $includeLogs = true): array
    {
        return [
            'id' => $reminder->id,
            'type' => $reminder->type,
            'purpose' => $reminder->purpose,
            'budget_plan_id' => $reminder->budget_plan_id,
            'saving_id' => $reminder->saving_id,
            'priority' => $reminder->priority,
            'title' => $reminder->title,
            'amount' => $reminder->amount,
            'financial_account_id' => $reminder->financial_account_id,
            'category_id' => $reminder->category?->is_active ? $reminder->category_id : null,
            'frequency' => $reminder->frequency,
            'interval' => $reminder->interval,
            'starts_at' => $reminder->starts_at->setTimezone($reminder->timezone)->format('Y-m-d\\TH:i'),
            'next_reminder_at' => $reminder->next_reminder_at?->setTimezone($reminder->timezone)->format('Y-m-d H:i'),
            'ends_at' => $reminder->ends_at?->setTimezone($reminder->timezone)->format('Y-m-d\\TH:i'),
            'timezone' => $reminder->timezone,
            'status' => $reminder->status,
            'notes' => $reminder->notes,
            'account' => $reminder->account,
            'category' => $reminder->category,
            'logs' => $includeLogs ? $reminder->logs->sortByDesc('scheduled_at')->values()->map(fn (RecurringReminderLog $log): array => [
                'id' => $log->id,
                'status' => $log->status,
                'scheduled_at' => $log->scheduled_at->setTimezone($reminder->timezone)->format('Y-m-d H:i'),
                'snoozed_until' => $log->snoozed_until?->setTimezone($reminder->timezone)->format('Y-m-d H:i'),
                'transaction_id' => $log->transaction_id,
                'budget_period_id' => $log->budget_period_id,
                'completed_at' => $log->completed_at,
            ]) : [],
        ];
    }
}
