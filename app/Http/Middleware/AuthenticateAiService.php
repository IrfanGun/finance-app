<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAiService
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.ai.token');
        $providedToken = (string) $request->header('X-AI-Service-Token');

        if (
            $configuredToken === ''
            || $providedToken === ''
            || ! hash_equals($configuredToken, $providedToken)
        ) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $userId = $request->header('X-AI-User-ID');

        if ($userId === null || ! ctype_digit($userId) || (int) $userId < 1) {
            return response()->json([
                'message' => 'AI user context is invalid.',
            ], 401);
        }

        $user = User::find((int) $userId);

        if ($user === null) {
            return response()->json([
                'message' => 'AI user context is invalid.',
            ], 401);
        }

        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }
}
