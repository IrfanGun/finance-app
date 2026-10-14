<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiConversationService
{
    public function latestOrCreate(User $user): AiConversation
    {
        return $user->aiConversations()
            ->latest('updated_at')
            ->firstOrCreate([], [
                'title' => 'Percakapan baru',
            ]);
    }

    public function findOwned(User $user, ?int $conversationId): AiConversation
    {
        if ($conversationId === null) {
            return $this->latestOrCreate($user);
        }

        return $user->aiConversations()->findOrFail($conversationId);
    }

    /**
     * @return Collection<int, AiMessage>
     */
    public function recentMessages(AiConversation $conversation, int $limit = 40): Collection
    {
        return $conversation->messages()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values();
    }

    /**
     * @return Collection<int, AiMessage>
     */
    public function contextMessages(AiConversation $conversation, int $limit = 40): Collection
    {
        return $conversation->messages()
            ->latest('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->values();
    }

    public function appendUserMessage(
        AiConversation $conversation,
        string $content,
        ?string $clientRequestId,
    ): AiMessage {
        return $conversation->messages()->create([
            'role' => 'user',
            'content' => $content,
            'client_request_id' => $clientRequestId,
            'request_status' => $clientRequestId === null ? null : 'processing',
        ]);
    }

    public function appendAssistantMessage(
        AiConversation $conversation,
        ?string $content,
        array $metadata = [],
    ): AiMessage {
        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    public function appendToolMessage(
        AiConversation $conversation,
        string $content,
        array $metadata = [],
    ): AiMessage {
        return $conversation->messages()->create([
            'role' => 'tool',
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    public function titleFromMessage(AiConversation $conversation, string $message): void
    {
        if ($conversation->messages()->count() === 1) {
            $conversation->update([
                'title' => Str::limit($message, 255, ''),
            ]);
        }
    }

    public function formatMessage(AiMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'text' => $message->content,
            'time' => $message->created_at?->format('H:i'),
            'metadata' => $message->metadata,
        ];
    }

    public function acquireLease(AiConversation $conversation, string $requestId): void
    {
        $metadata = $conversation->metadata ?? [];
        $activeLease = $metadata['active_request'] ?? null;

        if (is_array($activeLease)
            && ($activeLease['request_id'] ?? null) !== $requestId
            && isset($activeLease['expires_at'])
            && now()->lt($activeLease['expires_at'])) {
            abort(409, 'Percakapan sedang diproses. Silakan tunggu respons sebelumnya.');
        }

        $conversation->update([
            'metadata' => array_merge($metadata, [
                'active_request' => [
                    'request_id' => $requestId,
                    'expires_at' => now()
                        ->addSeconds((int) config('services.ai.lease_seconds', 120))
                        ->toISOString(),
                ],
            ]),
        ]);
    }

    public function releaseLease(AiConversation $conversation, string $requestId): void
    {
        DB::transaction(function () use ($conversation, $requestId): void {
            $lockedConversation = AiConversation::query()
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->first();

            if ($lockedConversation === null) {
                return;
            }

            $metadata = $lockedConversation->metadata ?? [];

            if (($metadata['active_request']['request_id'] ?? null) !== $requestId) {
                return;
            }

            unset($metadata['active_request']);
            $lockedConversation->update(['metadata' => $metadata]);
        });
    }

    public function findClientRequest(
        AiConversation $conversation,
        string $clientRequestId,
    ): ?AiMessage {
        return $conversation->messages()
            ->where('role', 'user')
            ->where('client_request_id', $clientRequestId)
            ->lockForUpdate()
            ->first();
    }

    public function markRequest(
        AiMessage $userMessage,
        string $status,
        ?AiMessage $response = null,
    ): void {
        $userMessage->update([
            'request_status' => $status,
            'response_message_id' => $response?->id,
        ]);
    }

    public function formatContextMessage(AiMessage $message): array
    {
        $metadata = $message->metadata ?? [];

        if ($message->role === 'assistant' && ($metadata['kind'] ?? null) === 'tool_calls') {
            return [
                'role' => 'assistant',
                'content' => $message->content,
                'tool_calls' => $metadata['tool_calls'] ?? [],
            ];
        }

        if ($message->role === 'tool') {
            return [
                'role' => 'tool',
                'content' => $message->content ?? '',
                'tool_call_id' => $metadata['tool_call_id'] ?? null,
                'name' => $metadata['name'] ?? null,
            ];
        }

        return [
            'role' => $message->role,
            'content' => $message->content ?? '',
        ];
    }
}
