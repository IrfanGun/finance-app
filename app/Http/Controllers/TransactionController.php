<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'asset_id' => ['nullable', 'array'],
            'asset_id.*' => ['integer', 'exists:financial_accounts,id'],
            'category_id' => ['nullable', 'array'],
            'category_id.*' => ['integer', 'exists:categories,id'],
        ]);

        $transactions = $request->user()
            ->transactions()
            ->with(['category', 'account'])
            ->when($request->filled('from'), fn ($query) => $query->whereDate('date', '>=', $request->string('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('date', '<=', $request->string('to')))
            ->when($request->filled('asset_id'), fn ($query) => $query->whereIn('account_id', $request->input('asset_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->whereIn('category_id', $request->input('category_id')))
            ->latest('date')
            ->latest('id')
            ->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => $request->only(['from', 'to', 'asset_id', 'category_id']),
            'assets' => $request->user()->financialAccounts()->orderBy('name')->get(['id', 'name']),
            'categories' => $request->user()->categories()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(TransactionRequest $request, TransactionService $transactions): RedirectResponse
    {
        $transactions->create($request->user(), $request->validated());

        return back();
    }
}
