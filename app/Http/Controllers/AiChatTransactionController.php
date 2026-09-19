<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmAiChatTransactionRequest;
use App\Services\AiTransactionService;
use Illuminate\Http\JsonResponse;

class AiChatTransactionController extends Controller
{
    public function confirm(
        ConfirmAiChatTransactionRequest $request,
        AiTransactionService $transactions,
    ): JsonResponse {
        $validated = $request->validated();
        $transaction = $transactions->completeChatTransaction(
            $request->user(),
            $validated['transaction'],
            $validated['resources'] ?? [],
        );

        return response()->json([
            'transaction' => $transaction,
        ], 201);
    }
}
