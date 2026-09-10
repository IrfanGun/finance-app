<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Http\Requests\TransactionRequest;

class TransactionController extends Controller
{
    public function store(TransactionRequest $request, TransactionService $transactions): RedirectResponse
    {
        $transactions->create($request->user(), $request->validated());
        return back();
    }
}
