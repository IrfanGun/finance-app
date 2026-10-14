<?php

namespace App\Services;

use App\Models\AiMessage;
use App\Models\AiUserMemory;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class AiMemoryService
{
    private const RULES = [
        'food.preparation_preference' => [
            'mode' => ['required', 'in:cook,buy'],
            'reason' => ['nullable', 'string', 'max:500'],
        ],
        'transport.preferred_mode' => [
            'mode' => ['required', 'string', 'max:100'],
        ],
        'finance.advice_style' => [
            'style' => ['required', 'in:practical,detailed'],
        ],
        'finance.spending_priority' => [
            'items' => ['required', 'array', 'max:20'],
            'items.*' => ['required', 'string', 'max:100'],
        ],
        'communication.language' => [
            'language' => ['required', 'in:id,en'],
        ],
    ];

    public function storeUpdates(User $user, array $updates, ?AiMessage $source): void
    {
        foreach ($updates as $update) {
            if (! is_array($update)) {
                continue;
            }

            $key = $update['memory_key'] ?? null;
            $value = $update['value'] ?? null;

            if (! is_string($key) || ! array_key_exists($key, self::RULES) || ! is_array($value)) {
                continue;
            }

            $validator = Validator::make($value, self::RULES[$key]);

            if ($validator->fails()) {
                continue;
            }

            AiUserMemory::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'memory_key' => $key,
                ],
                [
                    'value' => Arr::only($value, array_keys(self::RULES[$key])),
                    'source_message_id' => $source?->id,
                    'expires_at' => null,
                ],
            );
        }
    }
}
