<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavingRequest;
use App\Models\Saving;
use App\Models\SavingLog;
use App\Services\FundBalanceService;
use App\Services\FundService;
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
                    ->with('reversal')
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

        return Inertia::render('Savings/Index', [
            'createMode' => $createMode,
            'manageMode' => $manageMode,
            'managedSavingId' => $managedSavingId,
            'savings' => $savings,
            'accounts' => $user->financialAccounts()->orderBy('name')->get(['id', 'name', 'currency']),
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

        return redirect()->route('savings.index');
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
