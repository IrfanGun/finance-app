<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\User;
use App\Services\MealRecordService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class AiFinanceController extends Controller
{
    public function mealRecords(Request $request, MealRecordService $mealRecords): JsonResponse
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:buy,homemade'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($request->filled('min_price'), ['gte:min_price']),
            ],
            'at' => ['nullable', 'date'],
            'open_only' => [
                'nullable',
                Rule::in(['true', 'false', 'True', 'False', '1', '0']),
            ],
        ]);
        $filters['open_only'] = $request->boolean('open_only');

        return response()->json(
            $mealRecords->recommendations($request->user(), $filters),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->toDateString()
            : now()->toDateString();
        $user = $request->user();
        $accounts = $this->accountBalances($user);
        $periods = $this->totalsByCurrency($user, $from, $to, $accounts);
        $currencyTotals = $this->balanceTotalsByCurrency($accounts);
        $singleCurrencyTotals = $this->singleCurrencyTotals($periods);
        $singleCurrencyBalance = $this->singleCurrencyBalance($currencyTotals, $accounts);

        return response()->json([
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'currency' => $singleCurrencyTotals['currency'] ?? null,
            'balance_currency' => $singleCurrencyBalance['currency'] ?? null,
            'income' => $singleCurrencyTotals['income'] ?? null,
            'expense' => $singleCurrencyTotals['expense'] ?? null,
            'net' => $singleCurrencyTotals['net'] ?? null,
            'current_balance' => $singleCurrencyBalance['balance'] ?? null,
            'balance' => $singleCurrencyBalance['balance'] ?? null,
            'totals_by_currency' => $periods,
            'balances_by_currency' => $currencyTotals,
            'accounts' => $accounts,
            'budget' => $this->budgetSummary($user, $from, $to),
        ]);
    }

    /**
     * @return array<int, array{currency: string|null, account_type: string, investment_unit: string|null, income: float, expense: float, net: float}>
     */
    private function totalsByCurrency(
        User $user,
        string $from,
        string $to,
        Collection $accounts,
    ): array {
        $currencyExpression = "CASE WHEN financial_accounts.type = 'investment' THEN NULL ELSE COALESCE(financial_accounts.currency, 'IDR') END";
        $accountTypeExpression = "CASE WHEN financial_accounts.type = 'investment' THEN 'investment' ELSE 'cashflow' END";
        $unitExpression = "CASE WHEN financial_accounts.type = 'investment' THEN financial_accounts.investment_unit ELSE NULL END";

        $totals = $user->transactions()
            ->join('financial_accounts', 'financial_accounts.id', '=', 'transactions.account_id')
            ->whereBetween('transactions.date', [$from, $to])
            ->selectRaw("{$currencyExpression} as currency")
            ->selectRaw("{$accountTypeExpression} as account_type")
            ->selectRaw("{$unitExpression} as investment_unit")
            ->selectRaw("SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount ELSE 0 END) as expense")
            ->groupByRaw($currencyExpression)
            ->groupByRaw($accountTypeExpression)
            ->groupByRaw($unitExpression)
            ->get()
            ->map(fn ($totals): array => [
                'currency' => $totals->currency,
                'account_type' => $totals->account_type,
                'investment_unit' => $totals->investment_unit,
                'income' => (float) $totals->income,
                'expense' => (float) $totals->expense,
                'net' => (float) $totals->income - (float) $totals->expense,
            ])
            ->values()
            ->all();
        $knownCurrencies = $accounts
            ->pluck('currency')
            ->filter()
            ->unique()
            ->values();

        foreach ($knownCurrencies as $currency) {
            $hasTotals = collect($totals)->contains(
                fn (array $row): bool => $row['currency'] === $currency,
            );

            if (! $hasTotals) {
                $totals[] = [
                    'currency' => $currency,
                    'account_type' => 'cashflow',
                    'investment_unit' => null,
                    'income' => 0.0,
                    'expense' => 0.0,
                    'net' => 0.0,
                ];
            }
        }

        return collect($totals)
            ->sortBy(fn (array $row): string => implode('|', [
                $row['currency'] ?? '',
                $row['account_type'],
                $row['investment_unit'] ?? '',
            ]))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{ id: int, name: string, type: string, currency: string|null, investment_unit: string|null, balance: float }>
     */
    private function accountBalances(User $user): Collection
    {
        return $user->financialAccounts()
            ->withSum([
                'transactions as income_total' => fn (Builder $query) => $query->where('type', 'income'),
                'transactions as expense_total' => fn (Builder $query) => $query->where('type', 'expense'),
            ], 'amount')
            ->get()
            ->map(fn ($account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->type,
                'currency' => $account->type === 'investment'
                    ? null
                    : ($account->currency ?? 'IDR'),
                'investment_unit' => $account->investment_unit,
                'balance' => (float) $account->opening_balance
                    + (float) ($account->income_total ?? 0)
                    - (float) ($account->expense_total ?? 0),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $accounts
     * @return array<int, array{currency: string, balance: float}>
     */
    private function balanceTotalsByCurrency(Collection $accounts): array
    {
        return $accounts
            ->filter(fn (array $account): bool => $account['currency'] !== null)
            ->groupBy('currency')
            ->map(fn (Collection $currencyAccounts, string $currency): array => [
                'currency' => $currency,
                'balance' => (float) $currencyAccounts->sum('balance'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{currency: string|null, account_type: string, investment_unit: string|null, income: float, expense: float, net: float}>  $periods
     * @return array{currency: string, income: float, expense: float, net: float}|null
     */
    private function singleCurrencyTotals(array $periods): ?array
    {
        if (count($periods) !== 1 || $periods[0]['currency'] === null) {
            return null;
        }

        return [
            'currency' => $periods[0]['currency'],
            'income' => $periods[0]['income'],
            'expense' => $periods[0]['expense'],
            'net' => $periods[0]['net'],
        ];
    }

    /**
     * @param  array<int, array{currency: string, balance: float}>  $totals
     * @return array{currency: string, balance: float}|null
     */
    private function singleCurrencyBalance(array $totals, Collection $accounts): ?array
    {
        if (count($totals) !== 1
            || $accounts->contains(fn (array $account): bool => $account['type'] === 'investment')) {
            return null;
        }

        return $totals[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function budgetSummary(User $user, string $from, string $to): array
    {
        $budgets = $user->budgets()
            ->whereDate('period_start', '<=', $to)
            ->whereDate('period_end', '>=', $from)
            ->with('category:id,name')
            ->get();

        if ($budgets->isEmpty()) {
            return [
                'available' => false,
                'currency' => 'IDR',
                'amount' => 0,
                'spent' => 0,
                'remaining' => 0,
                'items' => [],
                'message' => 'Budget belum dikonfigurasi untuk user ini.',
            ];
        }

        $items = $budgets->map(function (Budget $budget) use ($user, $from, $to): array {
            $periodFrom = max($from, $budget->period_start->toDateString());
            $periodTo = min($to, $budget->period_end->toDateString());
            $transactions = $user->transactions()
                ->whereBetween('date', [$periodFrom, $periodTo])
                ->where('type', 'expense')
                ->whereHas('account', function (Builder $query): void {
                    $query->where(function (Builder $currencyQuery): void {
                        $currencyQuery
                            ->whereRaw('UPPER(currency) = ?', ['IDR'])
                            ->orWhere(function (Builder $legacyAccountQuery): void {
                                $legacyAccountQuery
                                    ->whereNull('currency')
                                    ->where('type', '!=', 'investment');
                            });
                    });
                });

            if ($budget->category_id !== null) {
                $transactions->where('category_id', $budget->category_id);
            }

            $spent = (float) $transactions->sum('amount');

            return [
                'id' => $budget->id,
                'name' => $budget->name,
                'category' => $budget->category?->name,
                'amount' => (float) $budget->amount,
                'spent' => $spent,
                'remaining' => (float) $budget->amount - $spent,
                'period' => [
                    'from' => $budget->period_start->toDateString(),
                    'to' => $budget->period_end->toDateString(),
                ],
            ];
        })->values()->all();

        return [
            'available' => true,
            'currency' => 'IDR',
            'amount' => (float) collect($items)->sum('amount'),
            'spent' => (float) collect($items)->sum('spent'),
            'remaining' => (float) collect($items)->sum('remaining'),
            'items' => $items,
        ];
    }
}
