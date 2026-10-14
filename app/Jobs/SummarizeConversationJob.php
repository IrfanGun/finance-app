<?php

namespace App\Jobs;

use App\Models\AiConversation;
use App\Services\AiConversationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SummarizeConversationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $uniqueFor = 300;

    public function __construct(public int $conversationId)
    {
        $this->onQueue('ai-memory');
    }

    public function uniqueId(): string
    {
        return 'conversation:'.$this->conversationId;
    }

    public function handle(AiConversationService $conversations): void
    {
        $conversation = AiConversation::query()->find($this->conversationId);

        if ($conversation === null) {
            return;
        }

        $summaryVersion = $conversation->summary_version;

        $messages = $conversation->messages()
            ->where('id', '>', $conversation->summary_through_message_id ?? 0)
            ->oldest('id')
            ->get();

        if ($messages->count() < 2) {
            return;
        }

        $groups = [];
        $currentGroup = [];

        foreach ($messages as $message) {
            if ($message->role === 'user' && $currentGroup !== []) {
                $groups[] = $currentGroup;
                $currentGroup = [];
            }

            $currentGroup[] = $message;
        }

        if ($currentGroup !== []) {
            $groups[] = $currentGroup;
        }

        $cutoffGroups = array_slice($groups, 0, max(0, count($groups) - 1));
        $cutoffMessages = [];

        foreach ($cutoffGroups as $group) {
            if (count($cutoffMessages) + count($group) > 40) {
                break;
            }

            $cutoffMessages = [...$cutoffMessages, ...$group];
        }

        $cutoff = collect($cutoffMessages);

        if ($cutoff->isEmpty()) {
            return;
        }

        $response = Http::acceptJson()
            ->connectTimeout((int) config('services.ai.connect_timeout', 3))
            ->timeout((int) config('services.ai.timeout', 90))
            ->withHeaders([
                'X-AI-Service-Token' => (string) config('services.ai.token'),
                'X-AI-User-ID' => (string) $conversation->user_id,
            ])
            ->post(rtrim((string) config('services.ai.url'), '/').'/ai/summarize', [
                'summary' => $conversation->summary,
                'messages' => $cutoff
                    ->map(fn ($message): array => $conversations->formatContextMessage($message))
                    ->values()
                    ->all(),
            ]);

        if (! $response->successful() || ! is_string($response->json('summary'))) {
            return;
        }

        $summary = trim($response->json('summary'));

        if ($summary === '' || mb_strlen($summary) > 12000) {
            return;
        }

        AiConversation::query()
            ->whereKey($conversation->id)
            ->where('summary_version', $summaryVersion)
            ->update([
                'summary' => Str::limit($summary, 12000, ''),
                'summary_through_message_id' => $cutoff->last()->id,
                'summary_version' => $summaryVersion + 1,
            ]);
    }
}
