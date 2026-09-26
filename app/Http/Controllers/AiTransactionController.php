<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiTransactionRequest;
use App\Http\Requests\StoreAiTransactionsRequest;
use App\Http\Requests\UpdateAiTransactionRequest;
use App\Services\AiTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiTransactionController extends Controller
{
    public function index(Request $request, AiTransactionService $transactions): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'transactions' => $transactions->list(
                $request->user(),
                (int) ($validated['limit'] ?? 20),
            ),
        ]);
    }

    public function store(
        StoreAiTransactionRequest $request,
        AiTransactionService $transactions,
    ): JsonResponse {
        $data = $request->validated();

        if (! is_string($data['account'] ?? null) || trim($data['account']) === '') {
            return response()->json([
                'code' => 'account_selection_required',
                'account_options' => $transactions->accountOptions(
                    $request->user(),
                ),
                'missing_resources' => $transactions->missingResources(
                    $request->user(),
                    $data,
                ),
            ], 409);
        }

        $missingResources = $transactions->missingResources(
            $request->user(),
            $data,
        );

        if ($missingResources !== []) {
            return response()->json([
                'code' => 'missing_resources',
                'missing_resources' => $missingResources,
            ], 409);
        }

        return response()->json([
            'transaction' => $transactions->create(
                $request->user(),
                $data,
            ),
        ], 201);
    }

    public function storeBatch(
        StoreAiTransactionsRequest $request,
        AiTransactionService $transactions,
    ): JsonResponse {
        $data = $request->validated()['transactions'];
        $requiresAccountSelection = collect($data)->contains(
            fn (array $transaction): bool => ! is_string($transaction['account'] ?? null)
                || trim($transaction['account']) === '',
        );
        $missingResources = $transactions->missingResourcesForBatch(
            $request->user(),
            $data,
        );

        if ($requiresAccountSelection) {
            return response()->json([
                'code' => 'account_selection_required',
                'account_options' => $transactions->accountOptions(
                    $request->user(),
                ),
                'missing_resources' => $missingResources,
            ], 409);
        }

        if ($missingResources !== []) {
            return response()->json([
                'code' => 'missing_resources',
                'missing_resources' => $missingResources,
            ], 409);
        }

        return response()->json([
            'transactions' => $transactions->createBatch(
                $request->user(),
                $data,
            ),
        ], 201);
    }

    public function update(
        UpdateAiTransactionRequest $request,
        int $transaction,
        AiTransactionService $transactions,
    ): JsonResponse {
        return response()->json([
            'transaction' => $transactions->update(
                $request->user(),
                $transaction,
                $request->validated(),
            ),
        ]);
    }

    public function destroy(
        Request $request,
        int $transaction,
        AiTransactionService $transactions,
    ): JsonResponse {
        $transactions->delete($request->user(), $transaction);

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction,
        ]);
    }
}
