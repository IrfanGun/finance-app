<?php

namespace App\Http\Controllers;

use App\Services\AiConversationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __invoke(Request $request, AiConversationService $conversations): Response
    {
        $conversation = $conversations->findOwned(
            $request->user(),
            $request->integer('conversation_id') ?: null,
        );

        $workflow = $conversation->workflows()
            ->whereIn('status', ['awaiting_input', 'ready'])
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        $workflowDraft = $workflow?->draft ?? [];

        return Inertia::render('Chat', [
            'conversationId' => $conversation->id,
            'messages' => $conversations->recentMessages($conversation)
                ->filter(fn ($message): bool => $message->role !== 'tool'
                    && ($message->metadata['kind'] ?? null) !== 'tool_calls')
                ->map(fn ($message): array => $conversations->formatMessage($message))
                ->values()
                ->all(),
            'workflow' => $workflow === null ? null : [
                'id' => $workflow->id,
                'action' => $workflow->action,
                'status' => $workflow->status,
                'draft' => $workflowDraft,
                'missing_fields' => $workflow->missing_fields ?? [],
                'pending_transactions' => $workflowDraft['transactions'] ?? [],
                'pending_transaction' => count($workflowDraft['transactions'] ?? []) === 1
                    ? $workflowDraft['transactions'][0]
                    : null,
                'missing_resources' => $workflowDraft['missing_resources'] ?? [],
                'account_selection_required' => $workflowDraft['account_selection_required'] ?? false,
                'account_options' => $workflowDraft['account_options'] ?? [],
                'workflow_ready' => $workflow->status === 'ready',
            ],
        ]);
    }
}
