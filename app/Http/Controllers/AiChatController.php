<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);
        $serviceUrl = rtrim((string) config('services.ai.url'), '/').'/ai/chat';
        $serviceToken = (string) config('services.ai.token');

        if ($serviceToken === '') {
            return response()->json([
                'message' => 'AI service belum dikonfigurasi.',
            ], 503);
        }

        $connectTimeout = (int) config('services.ai.connect_timeout', 3);
        $timeout = (int) config('services.ai.timeout', 90);
        $startedAt = hrtime(true);

        try {
            $response = Http::acceptJson()
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->withHeaders([
                    'X-AI-Service-Token' => $serviceToken,
                    'X-AI-User-ID' => (string) $request->user()->getAuthIdentifier(),
                ])
                ->post($serviceUrl, [
                    'message' => $validated['message'],
                ]);
        } catch (ConnectionException $exception) {
            $reason = preg_replace(
                '/https?:\/\/\S+/i',
                '[service-url]',
                $exception->getMessage(),
            );

            Log::warning('AI service tidak dapat dihubungi.', [
                'exception' => $exception::class,
                'reason' => $reason ?? 'Connection failed.',
                'elapsed_ms' => (int) ((hrtime(true) - $startedAt) / 1_000_000),
                'connect_timeout_seconds' => $connectTimeout,
                'timeout_seconds' => $timeout,
            ]);

            return response()->json([
                'message' => 'AI service tidak dapat dihubungi.',
            ], 502);
        }

        if (! $response->successful()) {
            Log::warning('AI service mengembalikan error.', [
                'status' => $response->status(),
            ]);

            return response()->json([
                'message' => 'AI service gagal memproses pesan.',
            ], 502);
        }

        $payload = $response->json();

        if (! is_array($payload) || ! is_string($payload['response'] ?? null)) {
            return response()->json([
                'message' => 'Respons AI service tidak valid.',
            ], 502);
        }

        return response()->json($payload);
    }
}
