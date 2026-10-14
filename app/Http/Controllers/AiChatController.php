<?php

namespace App\Http\Controllers;

use App\Jobs\SummarizeConversationJob;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiWorkflow;
use App\Services\AiConversationService;
use App\Services\AiMemoryService;
use App\Services\AiWorkflowService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AiChatController extends Controller
{
    public function __invoke(
        Request $request,
        AiConversationService $conversations,
        AiMemoryService $memories,
        AiWorkflowService $workflows,
    ): JsonResponse {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'conversation_id' => ['nullable', 'integer', 'min:1'],
            'client_request_id' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ]);
        $requestId = $validated['client_request_id'] ?? Str::uuid()->toString();
        $conversation = $conversations->findOwned(
            $request->user(),
            isset($validated['conversation_id']) ? (int) $validated['conversation_id'] : null,
        );
        $leaseAcquired = false;
        $userMessage = null;

        try {
            $stored = DB::transaction(function () use (
                $conversation,
                $conversations,
                $validated,
                $requestId,
            ): array {
                $lockedConversation = AiConversation::query()
                    ->whereKey($conversation->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $conversations->acquireLease($lockedConversation, $requestId);
                $clientRequestId = $requestId;
                $existing = $conversations->findClientRequest($lockedConversation, $clientRequestId);

                if ($existing !== null) {
                    if ($existing->request_status === 'processing') {
                        abort(409, 'Pesan sedang diproses. Silakan tunggu respons sebelumnya.');
                    }

                    if ($existing->request_status === 'succeeded' && $existing->response_message_id !== null) {
                        $assistant = $lockedConversation->messages()
                            ->whereKey($existing->response_message_id)
                            ->firstOrFail();

                        return [
                            'conversation' => $lockedConversation,
                            'duplicate' => $assistant,
                            'history' => [],
                            'user_message' => $existing,
                        ];
                    }

                    $existing->update([
                        'request_status' => 'processing',
                        'response_message_id' => null,
                    ]);

                    return [
                        'conversation' => $lockedConversation,
                        'duplicate' => null,
                        'history' => $conversations->contextMessages(
                            $lockedConversation,
                            min((int) config('services.ai.context_recent_messages', 40), 40),
                        )
                            ->filter(fn (AiMessage $message): bool => $message->id < $existing->id)
                            ->map(fn (AiMessage $message): array => $conversations->formatContextMessage($message))
                            ->values()
                            ->all(),
                        'user_message' => $existing,
                    ];
                }

                $history = $conversations->contextMessages(
                    $lockedConversation,
                    min((int) config('services.ai.context_recent_messages', 40), 40),
                )
                    ->map(fn (AiMessage $message): array => $conversations->formatContextMessage($message))
                    ->values()
                    ->all();
                $userMessage = $conversations->appendUserMessage(
                    $lockedConversation,
                    $validated['message'],
                    $clientRequestId,
                );
                $conversations->titleFromMessage($lockedConversation, $validated['message']);

                return [
                    'conversation' => $lockedConversation,
                    'duplicate' => null,
                    'history' => $history,
                    'user_message' => $userMessage,
                ];
            });
            $leaseAcquired = true;
            $userMessage = $stored['user_message'];

            if ($stored['duplicate'] !== null) {
                return response()->json([
                    'conversation_id' => $conversation->id,
                    'message' => $conversations->formatMessage($stored['duplicate']),
                    'response' => $stored['duplicate']->content,
                    'request_status' => 'succeeded',
                    'client_request_id' => $requestId,
                ]);
            }

            $activeWorkflow = $conversation->workflows()
                ->whereIn('status', ['awaiting_input', 'ready'])
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest('id')
                ->first();

            if ($activeWorkflow !== null) {
                $workflowPayload = DB::transaction(function () use (
                    $activeWorkflow,
                    $conversation,
                    $conversations,
                    $request,
                    $validated,
                    $userMessage,
                    $workflows,
                ): ?array {
                    $lockedWorkflow = AiWorkflow::query()
                        ->whereKey($activeWorkflow->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $workflowUpdate = $workflows->applyChatUpdate(
                        $request->user(),
                        $lockedWorkflow,
                        $validated['message'],
                    );

                    if ($workflowUpdate !== null) {
                        $message = $workflowUpdate['needs_clarification'] ?? false
                            ? $workflowUpdate['clarification']
                            : ($workflowUpdate['ready']
                                ? 'Draft transaksi sudah lengkap dan siap dikonfirmasi.'
                                : 'Draft transaksi diperbarui. Saya masih membutuhkan: '.implode(', ', $workflowUpdate['missing_fields']).'.');
                        $assistant = $conversations->appendAssistantMessage(
                            $conversation,
                            $message,
                            ['workflow' => $workflowUpdate, 'request_status' => 'succeeded'],
                        );
                        $conversations->markRequest($userMessage, 'succeeded', $assistant);

                        return [
                            'response' => $assistant->content,
                            'conversation_id' => $conversation->id,
                            'workflow_id' => $lockedWorkflow->id,
                            'pending_transactions' => $workflowUpdate['transactions'],
                            'workflow_ready' => $workflowUpdate['ready'] ?? false,
                            'missing_resources' => $workflowUpdate['missing_resources'] ?? [],
                            'account_selection_required' => $workflowUpdate['account_selection_required'] ?? false,
                            'account_options' => $workflowUpdate['account_options'] ?? [],
                            'workflow' => $lockedWorkflow->fresh(),
                            'message' => $conversations->formatMessage($assistant),
                        ];
                    }

                    if (! $workflows->isTopicSwitch($validated['message'])) {
                        return null;
                    }

                    $assistant = $conversations->appendAssistantMessage(
                        $conversation,
                        'Masih ada draft transaksi yang menunggu. Ketik lanjutkan untuk mengisinya, atau batalkan draft sebelum berpindah topik.',
                        [
                            'workflow_id' => $lockedWorkflow->id,
                            'status' => 'awaiting_input',
                            'request_status' => 'succeeded',
                        ],
                    );
                    $conversations->markRequest($userMessage, 'succeeded', $assistant);

                    return [
                        'response' => $assistant->content,
                        'conversation_id' => $conversation->id,
                        'workflow_id' => $lockedWorkflow->id,
                        'workflow' => $lockedWorkflow,
                        'message' => $conversations->formatMessage($assistant),
                    ];
                });

                if ($workflowPayload !== null) {
                    return response()->json($workflowPayload);
                }
            }

            $serviceToken = (string) config('services.ai.token');

            if ($serviceToken === '') {
                return $this->failRequest(
                    $conversation,
                    $userMessage,
                    $conversations,
                    'AI service belum dikonfigurasi.',
                    503,
                );
            }

            $requestBody = ['message' => $validated['message']];

            if ($stored['history'] !== []) {
                $requestBody['history'] = $stored['history'];
            }

            if ($conversation->summary !== null) {
                $requestBody['summary'] = $conversation->summary;
            }

            $memoriesContext = $request->user()
                ->aiMemories()
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->get(['memory_key', 'value'])
                ->map(fn ($memory): array => [
                    'memory_key' => $memory->memory_key,
                    'value' => $memory->value,
                ])
                ->values()
                ->all();

            if ($memoriesContext !== []) {
                $requestBody['memories'] = $memoriesContext;
            }

            if ($activeWorkflow !== null) {
                $requestBody['workflow'] = [
                    'id' => $activeWorkflow->id,
                    'action' => $activeWorkflow->action,
                    'draft' => $activeWorkflow->draft,
                    'missing_fields' => $activeWorkflow->missing_fields,
                ];
            }

            $startedAt = hrtime(true);

            $response = Http::acceptJson()
                ->connectTimeout((int) config('services.ai.connect_timeout', 3))
                ->timeout((int) config('services.ai.timeout', 90))
                ->withHeaders([
                    'X-AI-Service-Token' => $serviceToken,
                    'X-AI-User-ID' => (string) $request->user()->getAuthIdentifier(),
                ])
                ->post(rtrim((string) config('services.ai.url'), '/').'/ai/chat', $requestBody);

            if (! $response->successful()) {
                Log::warning('AI service mengembalikan error.', ['status' => $response->status()]);

                return $this->failRequest(
                    $conversation,
                    $userMessage,
                    $conversations,
                    'AI service gagal memproses pesan.',
                    502,
                );
            }

            $payload = $response->json();

            if (! is_array($payload) || ! is_string($payload['response'] ?? null)) {
                return $this->failRequest(
                    $conversation,
                    $userMessage,
                    $conversations,
                    'Respons AI service tidak valid.',
                    502,
                );
            }

            if (($payload['request_status'] ?? 'succeeded') === 'failed') {
                return $this->failRequest(
                    $conversation,
                    $userMessage,
                    $conversations,
                    $payload['response'],
                    502,
                );
            }

            $payload = DB::transaction(function () use (
                $conversation,
                $conversations,
                $memories,
                $payload,
                $request,
                $userMessage,
            ): array {
                $this->persistToolTrace($conversation, $conversations, $payload['tool_trace'] ?? []);
                $assistant = $conversations->appendAssistantMessage(
                    $conversation,
                    $payload['response'],
                    collect($payload)
                        ->except(['response', 'tool_trace'])
                        ->put('request_status', 'succeeded')
                        ->all(),
                );
                $memories->storeUpdates(
                    $request->user(),
                    is_array($payload['memory_updates'] ?? null) ? $payload['memory_updates'] : [],
                    $userMessage,
                );

                if (($payload['pending_transaction'] ?? null) !== null
                    || ($payload['pending_transactions'] ?? null) !== null) {
                    $pendingDraft = $payload['pending_transactions']
                        ?? [$payload['pending_transaction']];
                    $pendingDraft = array_map(function (mixed $transaction): array {
                        $transaction = is_array($transaction) ? $transaction : [];
                        $transaction['draft_id'] ??= (string) Str::uuid();

                        return $transaction;
                    }, $pendingDraft);
                    $missingFields = [];

                    if (($payload['account_selection_required'] ?? false) === true) {
                        $missingFields[] = 'account_id';
                    }

                    if (($payload['missing_resources'] ?? []) !== []) {
                        $missingFields[] = 'resources';
                    }

                    AiWorkflow::query()->updateOrCreate(
                        ['idempotency_key' => 'chat:'.$conversation->id.':'.$assistant->id],
                        [
                            'conversation_id' => $conversation->id,
                            'action' => 'create_transaction',
                            'status' => $missingFields === [] ? 'ready' : 'awaiting_input',
                            'draft' => [
                                'transactions' => $pendingDraft,
                                'missing_resources' => $payload['missing_resources'] ?? [],
                                'account_selection_required' => $payload['account_selection_required'] ?? false,
                                'account_options' => $payload['account_options'] ?? [],
                            ],
                            'missing_fields' => $missingFields,
                            'expires_at' => now()->addHours((int) config('services.ai.workflow_ttl_hours', 24)),
                        ],
                    );
                    $workflow = AiWorkflow::query()
                        ->where('idempotency_key', 'chat:'.$conversation->id.':'.$assistant->id)
                        ->firstOrFail();
                    $payload['workflow_id'] = $workflow->id;
                }

                $conversations->markRequest($userMessage, 'succeeded', $assistant);
                $payload['conversation_id'] = $conversation->id;
                $payload['request_status'] = 'succeeded';
                $payload['client_request_id'] = $userMessage->client_request_id;
                $payload['message'] = $conversations->formatMessage($assistant);

                return $payload;
            });

            if ($conversation->messages()->count() >= 12) {
                SummarizeConversationJob::dispatch($conversation->id);
            }

            return response()->json($payload);
        } catch (ConnectionException $exception) {
            $reason = preg_replace(
                '/https?:\/\/\S+/i',
                '[service-url]',
                $exception->getMessage(),
            );
            Log::warning('AI service tidak dapat dihubungi.', [
                'exception' => $exception::class,
                'reason' => $reason ?? 'Connection failed.',
                'elapsed_ms' => isset($startedAt)
                    ? (int) ((hrtime(true) - $startedAt) / 1_000_000)
                    : 0,
                'connect_timeout_seconds' => (int) config('services.ai.connect_timeout', 3),
                'timeout_seconds' => (int) config('services.ai.timeout', 90),
            ]);

            return $this->failRequest(
                $conversation,
                $userMessage,
                $conversations,
                'AI service tidak dapat dihubungi.',
                502,
            );
        } catch (ValidationException|HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Pemrosesan AI chat gagal.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if ($userMessage instanceof AiMessage) {
                return $this->failRequest(
                    $conversation,
                    $userMessage,
                    $conversations,
                    'AI service gagal memproses pesan.',
                    500,
                );
            }

            throw $exception;
        } finally {
            if ($leaseAcquired) {
                $conversations->releaseLease($conversation, $requestId);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $trace
     */
    private function persistToolTrace(
        AiConversation $conversation,
        AiConversationService $conversations,
        array $trace,
    ): void {
        $calls = collect($trace)
            ->filter(fn ($item): bool => is_array($item) && ($item['type'] ?? null) === 'tool_call')
            ->map(fn (array $item): array => [
                'id' => $item['id'] ?? null,
                'type' => 'function',
                'function' => [
                    'name' => $item['name'] ?? null,
                    'arguments' => json_encode($item['arguments'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ])
            ->values()
            ->all();

        if ($calls !== []) {
            $conversations->appendAssistantMessage($conversation, null, [
                'kind' => 'tool_calls',
                'tool_calls' => $calls,
            ]);
        }

        foreach ($trace as $item) {
            if (! is_array($item) || ($item['type'] ?? null) !== 'tool_result') {
                continue;
            }

            $conversations->appendToolMessage(
                $conversation,
                json_encode($item['result'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                [
                    'tool_call_id' => $item['id'] ?? null,
                    'name' => $item['name'] ?? null,
                ],
            );
        }
    }

    private function failRequest(
        AiConversation $conversation,
        ?AiMessage $userMessage,
        AiConversationService $conversations,
        string $message,
        int $status,
    ): JsonResponse {
        $payload = DB::transaction(function () use (
            $conversation,
            $userMessage,
            $conversations,
            $message,
        ): array {
            $assistant = $conversations->appendAssistantMessage(
                $conversation,
                $message,
                ['request_status' => 'failed'],
            );

            if ($userMessage instanceof AiMessage && $userMessage->client_request_id !== null) {
                $conversations->markRequest($userMessage, 'failed', $assistant);
            }

            return [
                'message' => $message,
                'response' => $message,
                'conversation_id' => $conversation->id,
                'assistant_message_id' => $assistant->id,
                'request_status' => 'failed',
                'client_request_id' => $userMessage?->client_request_id,
            ];
        });

        return response()->json($payload, $status);
    }
}
