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
        $transactionData = $validated['transactions'] ?? [
            $validated['transaction'],
        ];
        $createdTransactions = $transactions->completeChatTransactions(
            $request->user(),
            $transactionData,
            $validated['resources'] ?? [],
        );

        $response = [
            'transaction' => $createdTransactions[0],
        ];

        if (count($createdTransactions) > 1) {
            $response['transactions'] = $createdTransactions;
        }

        return response()->json($response, 201);
    }
}
