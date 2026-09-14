<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialAccountRequest;
use App\Models\FinancialAccount;
use App\Services\FinancialAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialAccountController extends Controller
{
    public function index(Request $request, FinancialAccountService $accounts): Response
    {
        return Inertia::render('Assets/Index', ['accounts' => $accounts->listFor($request->user())]);
    }

    public function store(FinancialAccountRequest $request, FinancialAccountService $accounts): RedirectResponse
    {
        $accounts->create($request->user(), $request->validated());

        return back();
    }

    public function update(FinancialAccountRequest $request, FinancialAccount $account, FinancialAccountService $accounts): RedirectResponse
    {
        abort_unless($account->user_id === $request->user()->id, 403);
        $accounts->update($account, $request->validated());

        return back();
    }

    public function destroy(Request $request, FinancialAccount $account, FinancialAccountService $accounts): RedirectResponse
    {
        abort_unless($account->user_id === $request->user()->id, 403);
        $accounts->delete($account, $request->boolean('delete_transactions'));

        return back();
    }
}
