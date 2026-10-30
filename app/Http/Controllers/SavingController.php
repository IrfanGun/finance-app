<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingRequest;
use App\Models\Saving;
use App\Models\SavingLog;
use App\Services\DeleteSavingService;
use App\Services\FundBalanceService;
use App\Services\FundService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SavingController extends Controller
{
    public function index(Request $request, FundBalanceService $balances): Response
    {
        $filters = $request->validate([
            'saving' => ['nullable', 'integer'],
            'reminder_log' => ['nullable', 'integer'],
            'source' => [
                'nullable',
                Rule::in([
                    'direct_allocation',
                    'goal_contribution',
                    'budget_claim',
                    'free_release',
                    'expense',
                    'reversal',
                ]),
            ],
            'period_id' => ['nullable', 'integer'],
            'account_id' => ['nullable', 'integer'],
        ]);

        return $this->renderPage($request, $balances, $filters, false);
    }

    public function create(Request $request, FundBalanceService $balances): Response
    {
        return $this->renderPage($request, $balances, [], true);
    }

    public function show(Request $request, int $saving, FundBalanceService $balances): Response
    {
        $record = $request->user()->savings()
            ->with([
                'goalPlan.periods' => fn ($query) => $query->orderByDesc('starts_on'),
                'logs' => fn ($query) => $query
                    ->with(['reversal', 'claim.period'])
                    ->latest('occurred_at')
                    ->limit(20),
            ])
            ->findOrFail($saving);

        return Inertia::render('Savings/Show', [
            'cmsMode' => $request->is('cms/*'),
            'saving' => [
                ...$record->toArray(),
                'balance' => $balances->savingBalance($record),
            ],
        ]);
    }

    public function manage(
        Request $request,
        int $saving,
        FundBalanceService $balances,
    ): Response {
        $request->user()->savings()->findOrFail($saving);

        return $this->renderPage($request, $balances, ['saving' => $saving], false, true, $saving);
    }

    private function renderPage(
        Request $request,
        FundBalanceService $balances,
        array $filters,
        bool $createMode,
        bool $manageMode = false,
        ?int $managedSavingId = null,
    ): Response {
        $user = $request->user();
        $savings = $user->savings()
            ->with([
                'goalPlan.periods',
                'logs' => fn ($query) => $query
                    ->with(['reversal', 'account'])
                    ->when(
                        isset($filters['source']),
                        fn ($query) => $query->where('reason', $filters['source']),
                    )
                    ->when(
                        isset($filters['account_id']),
                        fn ($query) => $query->where('financial_account_id', $filters['account_id']),
                    )
                    ->when(
                        isset($filters['period_id']),
                        fn ($query) => $query->where(function ($query) use ($filters): void {
                            $query->where('budget_period_id', $filters['period_id'])
                                ->orWhereHas(
                                    'claim',
                                    fn ($claim) => $claim->where('budget_period_id', $filters['period_id']),
                                )
                                ->orWhereHas(
                                    'reversedOriginal.claim',
                                    fn ($claim) => $claim->where('budget_period_id', $filters['period_id']),
                                );
                        }),
                    )
                    ->latest('occurred_at')
                    ->limit(30),
            ])
            ->when($managedSavingId !== null, fn ($query) => $query->whereKey($managedSavingId))
            ->orderBy('name')
            ->get()
            ->map(function (Saving $saving) use ($balances): array {
                return [
                    ...$saving->toArray(),
                    'balance' => $balances->savingBalance($saving),
                ];
            });

        $reminders = $managedSavingId === null
            ? collect()
            : $user->recurringReminders()
                ->where('purpose', 'saving_allocation')
                ->where('saving_id', $managedSavingId)
                ->whereIn('status', ['active', 'paused'])
                ->latest('updated_at')
                ->get()
                ->map(fn ($reminder): array => [
                    'id' => $reminder->id,
                    'saving_id' => $reminder->saving_id,
                    'title' => $reminder->title,
                    'amount' => $reminder->amount,
                    'frequency' => $reminder->frequency,
                    'interval' => $reminder->interval,
                    'starts_at' => $reminder->starts_at
                        ->setTimezone($reminder->timezone)
                        ->format('Y-m-d\\TH:i'),
                    'next_reminder_at' => $reminder->next_reminder_at?->toIso8601String(),
                    'timezone' => $reminder->timezone,
                    'status' => $reminder->status,
                ])
                ->values();
        $managedSaving = $managedSavingId === null
            ? null
            : $user->savings()->find($managedSavingId);
        $accounts = $user->financialAccounts()
            ->orderBy('name')
            ->get($managedSaving === null
                ? ['id', 'name', 'currency']
                : ['id', 'name', 'currency', 'type', 'opening_balance']);

        if ($managedSaving !== null) {
            $accounts = $accounts
                ->map(fn ($account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency' => $account->currencyCode(),
                    'available_balance' => $balances->free($account),
                    'saving_balance' => $balances->savingBalance($managedSaving, $account->id),
                ])
                ->values();
        }

        return Inertia::render('Savings/Index', [
            'cmsMode' => $request->is('cms/*'),
            'createMode' => $createMode,
            'manageMode' => $manageMode,
            'managedSavingId' => $managedSavingId,
            'savings' => $savings,
            'reminders' => $reminders,
            'accounts' => $accounts,
            'categories' => $managedSavingId === null
                ? collect()
                : $user->categories()
                    ->where('is_active', true)
                    ->where('type', 'expense')
                    ->orderBy('name')
                    ->get(['id', 'name', 'type']),
            'totals' => $balances->totalsFor($user),
            'historyFilters' => $filters,
            'periodOptions' => $user->budgetPlans()
                ->with('periods')
                ->get()
                ->flatMap(fn ($plan) => $plan->periods->map(fn ($period) => [
                    'id' => $period->id,
                    'label' => $plan->name.' · '.$period->starts_on->toDateString(),
                ]))
                ->values(),
        ]);
    }

    public function store(SavingRequest $request, FundService $funds): RedirectResponse
    {
        $funds->createSaving($request->user(), $request->validated());

        $refererPath = parse_url($request->header('referer', ''), PHP_URL_PATH);
        $routeName = is_string($refererPath) && str_starts_with($refererPath, '/cms/')
            ? 'cms.savings.index'
            : 'savings.index';

        return redirect()->route($routeName);
    }

    public function update(
        SavingRequest $request,
        int $saving,
        FundBalanceService $balances,
    ): RedirectResponse {
        $record = $request->user()->savings()->findOrFail($saving);
        $data = $request->validated();

        if ($record->goalPlan !== null
            && (($data['target_amount'] ?? null) !== null
                || ($data['target_date'] ?? null) !== null)) {
            throw ValidationException::withMessages([
                'target_amount' => 'Target savings goal dikelola di budget plan.',
            ]);
        }

        if ($record->currency !== $data['currency']
            && ($record->goalPlan !== null
                || $record->logs()->exists()
                || bccomp($balances->savingBalance($record), '0', 2) !== 0)) {
            throw ValidationException::withMessages([
                'currency' => 'Mata uang tidak dapat diubah setelah ada dana.',
            ]);
        }

        $record->update($data);

        return back();
    }

    public function allocate(Request $request, int $saving, FundService $funds): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'budget_period_id' => ['nullable', 'integer'],
            'reminder_log_id' => ['nullable', 'integer'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $record = $request->user()->savings()->findOrFail($saving);
        $account = $request->user()->financialAccounts()->findOrFail($data['account_id']);
        $period = isset($data['budget_period_id'])
            ? $record->goalPlan?->periods()->findOrFail($data['budget_period_id'])
            : null;
        $funds->allocate(
            $request->user(),
            $record,
            $account,
            (string) $data['amount'],
            $data['idempotency_key'],
            $period,
            $data['reminder_log_id'] ?? null,
            isset($data['date'])
                ? CarbonImmutable::parse($data['date'], 'Asia/Jakarta')->startOfDay()->utc()
                : null,
            $data['notes'] ?? null,
        );

        return back();
    }

    public function release(Request $request, int $saving, FundService $funds): RedirectResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'idempotency_key' => ['required', 'uuid'],
        ]);
        $record = $request->user()->savings()->findOrFail($saving);
        $account = $request->user()->financialAccounts()->findOrFail($data['account_id']);
        $funds->releaseSaving(
            $request->user(),
            $record,
            $account,
            (string) $data['amount'],
            $data['idempotency_key'],
        );

        return back();
    }

    public function archive(Request $request, int $saving, FundBalanceService $balances): RedirectResponse
    {
        $record = $request->user()->savings()->findOrFail($saving);

        if (bccomp($balances->savingBalance($record), '0', 2) !== 0
            || ($record->goalPlan !== null
                && ! in_array($record->goalPlan->status, ['ended', 'completed'], true))) {
            throw ValidationException::withMessages([
                'saving' => 'Kosongkan dana dan akhiri goal sebelum mengarsipkan savings.',
            ]);
        }

        $record->update(['status' => 'archived']);

        return back();
    }

    public function unarchive(Request $request, int $saving): RedirectResponse
    {
        $record = $request->user()->savings()->findOrFail($saving);

        if ($record->status !== 'archived') {
            throw ValidationException::withMessages([
                'saving' => 'Hanya tabungan yang diarsipkan yang dapat dipulihkan.',
            ]);
        }

        $record->update(['status' => 'active']);

        return back();
    }

    public function destroy(
        Request $request,
        int $saving,
        DeleteSavingService $deleteSaving,
    ): RedirectResponse {
        $deleteSaving->handle($request->user(), $saving);

        return back();
    }

    public function reverseAllocation(
        Request $request,
        int $log,
        FundService $funds,
    ): RedirectResponse {
        $data = $request->validate(['idempotency_key' => ['required', 'uuid']]);
        $record = SavingLog::query()
            ->whereKey($log)
            ->whereHas(
                'pocket',
                fn ($query) => $query->where('user_id', $request->user()->id),
            )
            ->firstOrFail();
        $funds->reverseAllocation($request->user(), $record, $data['idempotency_key']);

        return back();
    }
}
