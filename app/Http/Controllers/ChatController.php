<?php

namespace App\Http\Controllers;

use App\Services\AiConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __invoke(
        Request $request,
        AiConversationService $conversations,
    ): Response|JsonResponse {
        $validated = [];

        if ($request->expectsJson()) {
            $validated = $request->validate([
                'conversation_id' => ['required', 'integer', 'min:1'],
                'before_message_id' => ['required', 'integer', 'min:1'],
            ]);
        }

        $conversation = $conversations->findOwned(
            $request->user(),
            $request->integer('conversation_id') ?: null,
        );

        $messagePage = $conversations->visibleMessagePage(
            $conversation,
            $request->expectsJson()
                ? (int) $validated['before_message_id']
                : null,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'messages' => $messagePage['messages']
                    ->map(fn ($message): array => $conversations->formatMessage($message))
                    ->all(),
                'has_more' => $messagePage['has_more'],
            ]);
        }

        $workflow = $conversation->workflows()
            ->whereIn('status', ['awaiting_input', 'ready'])
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();

        $workflowDraft = $workflow?->draft ?? [];

        $view = $request->is('cms/chat')
            ? 'Cms/ChatCMS'
            : 'Chat';

        return Inertia::render($view, [
            'conversationId' => $conversation->id,
            'messages' => $messagePage['messages']
                ->map(fn ($message): array => $conversations->formatMessage($message))
                ->values()
                ->all(),
            'hasMoreMessages' => $messagePage['has_more'],
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
