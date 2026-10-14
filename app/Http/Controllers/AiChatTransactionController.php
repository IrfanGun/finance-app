<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmAiChatTransactionRequest;
use App\Models\AiTransactionOperation;
use App\Models\AiWorkflow;
use App\Services\AiTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiChatTransactionController extends Controller
{
    public function confirm(
        ConfirmAiChatTransactionRequest $request,
        AiTransactionService $transactions,
    ): JsonResponse {
        $validated = $request->validated();
        $transactionData = $validated['transactions'] ?? [$validated['transaction']];
        $operationKey = $this->operationKey($validated, $transactionData);

        $createdTransactions = DB::transaction(function () use (
            $request,
            $validated,
            $transactionData,
            $transactions,
            $operationKey,
        ): array {
            DB::table('ai_transaction_operations')->insertOrIgnore([
                'user_id' => $request->user()->id,
                'operation_key' => $operationKey,
                'status' => 'processing',
                'result' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $operation = AiTransactionOperation::query()
                ->where('user_id', $request->user()->id)
                ->where('operation_key', $operationKey)
                ->lockForUpdate()
                ->firstOrFail();

            if ($operation->status === 'succeeded') {
                return collect($operation->result['transactions'] ?? [])
                    ->map(fn (array $transaction): object => (object) $transaction)
                    ->all();
            }

            $workflow = null;

            if (isset($validated['workflow_id'])) {
                $workflow = AiWorkflow::query()
                    ->whereKey($validated['workflow_id'])
                    ->whereHas('conversation', fn ($query) => $query->where('user_id', $request->user()->id))
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($workflow->status === 'completed' && is_array($workflow->result['transactions'] ?? null)) {
                    $result = $workflow->result['transactions'];
                    $operation->update([
                        'status' => 'succeeded',
                        'result' => ['transactions' => $result],
                    ]);

                    return collect($result)
                        ->map(fn (array $transaction): object => (object) $transaction)
                        ->all();
                }

                if (! in_array($workflow->status, ['awaiting_input', 'ready'], true)
                    || ($workflow->expires_at !== null && $workflow->expires_at->isPast())) {
                    throw ValidationException::withMessages([
                        'workflow_id' => 'Workflow transaksi sudah tidak dapat dilanjutkan.',
                    ]);
                }
            }

            $createdTransactions = $transactions->completeChatTransactions(
                $request->user(),
                $transactionData,
                $validated['resources'] ?? [],
            );
            $result = [
                'transactions' => collect($createdTransactions)
                    ->map(fn ($transaction): array => $transaction->toArray())
                    ->all(),
            ];

            $operation->update([
                'status' => 'succeeded',
                'result' => $result,
            ]);

            if ($workflow !== null) {
                $workflow->update([
                    'status' => 'completed',
                    'result' => $result,
                    'version' => $workflow->version + 1,
                ]);
            }

            return $createdTransactions;
        });

        $response = [
            'transaction' => $createdTransactions[0],
            'operation_id' => $operationKey,
        ];

        if (count($createdTransactions) > 1) {
            $response['transactions'] = $createdTransactions;
        }

        return response()->json($response, 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, array<string, mixed>>  $transactionData
     */
    private function operationKey(array $validated, array $transactionData): string
    {
        if (is_string($validated['operation_id'] ?? null) && $validated['operation_id'] !== '') {
            return $validated['operation_id'];
        }

        if (isset($validated['workflow_id'])) {
            return 'workflow:'.$validated['workflow_id'];
        }

        return 'payload:'.hash('sha256', json_encode([
            'conversation_id' => $validated['conversation_id'] ?? null,
            'transactions' => $transactionData,
            'resources' => $validated['resources'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
