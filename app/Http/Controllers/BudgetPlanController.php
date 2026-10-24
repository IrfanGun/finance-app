<?php

namespace App\Http\Controllers;

use App\Http\Requests\BudgetPlanRequest;
use App\Models\BudgetPeriod;
use App\Models\BudgetPlan;
use App\Models\BudgetSavingsClaim;
use App\Models\Saving;
use App\Notifications\BudgetFundingNeeded;
use App\Services\FundBalanceService;
use App\Services\FundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetPlanController extends Controller
{
    public function index(Request $request, FundBalanceService $balances): Response
    {
        return $this->renderPage($request, $balances, false);
    }

    public function create(Request $request, FundBalanceService $balances): Response
    {
        return $this->renderPage($request, $balances, true);
    }

    private function renderPage(
        Request $request,
        FundBalanceService $balances,
        bool $createMode,
        ?int $selectedPlanId = null,
    ): Response {
        $user = $request->user();
        $plansQuery = $user->budgetPlans()
            ->with([
                'pocket',
                'items',
                'periods' => fn ($query) => $query->with('claims.pocket')->orderByDesc('starts_on'),
            ])
            ->latest();

        if ($selectedPlanId !== null) {
            $plansQuery->whereKey($selectedPlanId);
        }

        $plans = $plansQuery
            ->get()
            ->map(function (BudgetPlan $plan) use ($balances): array {
                $periods = $plan->periods->map(function (BudgetPeriod $period) use ($balances, $plan): array {
                    $contributed = $plan->type === 'goal'
                        ? $balances->goalContribution($period)
                        : null;

                    return [
                        ...$period->toArray(),
                        'remaining' => $plan->type === 'spending'
                            ? $balances->periodRemaining($period)
                            : null,
                        'spent' => $plan->type === 'spending'
                            ? $period->transactions()
                                ->where('type', 'expense')
                                ->sum('amount')
                            : null,
                        'contributed' => $contributed,
                    ];
                });
                $available = $plan->type === 'goal'
                    ? $balances->savingBalance($plan->pocket)
                    : null;
                $paid = $plan->type === 'goal'
                    ? $plan->user->transactions()
                        ->where('budget_plan_id', $plan->id)
                        ->where('type', 'expense')
                        ->sum('amount')
                    : null;
                $funded = $plan->type === 'goal'
                    ? bcadd((string) $available, (string) $paid, 2)
                    : null;
                $shortfall = $funded !== null
                    ? bcsub((string) $plan->target_amount, $funded, 2)
                    : null;

                return [
                    ...$plan->toArray(),
                    'periods' => $periods,
                    'goal_available' => $available,
                    'goal_paid' => $paid,
                    'goal_funded' => $funded,
                    'goal_shortfall' => $shortfall === null
                        ? null
                        : (bccomp($shortfall, '0', 2) > 0 ? $shortfall : '0.00'),
                ];
            });

        abort_if($selectedPlanId !== null && $plans->isEmpty(), 404);

        return Inertia::render('BudgetPlans/Index', [
            'createMode' => $createMode,
            'selectedPlanId' => $selectedPlanId,
            'plans' => $plans,
            'legacyBudgets' => $user->budgets()
                ->with('category')
                ->orderByDesc('period_start')
                ->get(),
            'accounts' => $user->financialAccounts()
                ->orderBy('name')
                ->get(['id', 'name', 'type', 'currency']),
            'categories' => $user->categories()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type']),
            'savings' => $user->savings()
                ->where('status', 'active')
                ->with('goalPlan')
                ->orderBy('name')
                ->get()
                ->map(fn (Saving $saving) => [
                    ...$saving->toArray(),
                    'balance' => $balances->savingBalance($saving),
                ]),
            'totals' => $balances->totalsFor($user),
            'fundingNotices' => $user->notifications()
                ->where('type', BudgetFundingNeeded::class)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'data' => $notification->data,
                ]),
        ]);
    }

    public function show(
        Request $request,
        int $plan,
        FundBalanceService $balances,
    ): Response {
        return $this->renderPage($request, $balances, false, $plan);
    }

    public function store(BudgetPlanRequest $request, FundService $funds): RedirectResponse
    {
        $funds->createPlan($request->user(), $request->validated());

        return redirect()->route('budget-plans.index');
    }

    public function update(
        BudgetPlanRequest $request,
        int $plan,
        FundService $funds,
    ): RedirectResponse {
        $record = $request->user()->budgetPlans()->findOrFail($plan);
        $funds->updatePlan($request->user(), $record, $request->validated());

        return back();
    }

    public function status(Request $request, int $plan, FundService $funds): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,paused,ended,completed'],
        ]);
        $record = $request->user()->budgetPlans()->findOrFail($plan);
        $funds->changePlanStatus($request->user(), $record, $data['status']);

        return back();
    }

    public function fund(Request $request, int $period, FundService $funds): RedirectResponse
    {
        $data = $request->validate(['idempotency_key' => ['required', 'uuid']]);
        $record = $this->ownedPeriod($request, $period);
        $funds->fundPeriod($request->user(), $record, $data['idempotency_key']);

        return back();
    }

    public function claim(Request $request, int $period, FundService $funds): RedirectResponse
    {
        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'parts' => ['required', 'array', 'min:1'],
            'parts.*.saving_id' => ['required', 'integer'],
            'parts.*.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $record = $this->ownedPeriod($request, $period);
        $funds->claim(
            $request->user(),
            $record,
            $data['parts'],
            $data['idempotency_key'],
        );

        return back();
    }

    public function release(Request $request, int $period, FundService $funds): RedirectResponse
    {
        $data = $request->validate([
            'idempotency_key' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        ]);
        $record = $this->ownedPeriod($request, $period);
        $funds->releasePeriod(
            $request->user(),
            $record,
            (string) $data['amount'],
            $data['idempotency_key'],
        );

        return back();
    }

    public function reverseClaim(
        Request $request,
        int $claim,
        FundService $funds,
    ): RedirectResponse {
        $data = $request->validate(['idempotency_key' => ['required', 'uuid']]);
        $record = BudgetSavingsClaim::query()
            ->whereKey($claim)
            ->whereHas(
                'period.plan',
                fn ($query) => $query->where('user_id', $request->user()->id),
            )
            ->firstOrFail();
        $funds->reverseClaim($request->user(), $record, $data['idempotency_key']);

        return back();
    }

    public function close(Request $request, int $period, FundService $funds): RedirectResponse
    {
        $funds->closePeriod($request->user(), $this->ownedPeriod($request, $period));

        return back();
    }

    private function ownedPeriod(Request $request, int $period): BudgetPeriod
    {
        return BudgetPeriod::query()
            ->whereKey($period)
            ->whereHas('plan', fn ($query) => $query->where('user_id', $request->user()->id))
            ->firstOrFail();
    }
}
