<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiMemoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'memories' => $request->user()
                ->aiMemories()
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->latest('updated_at')
                ->get(),
        ]);
    }

    public function destroy(Request $request, int $memory): JsonResponse
    {
        $deleted = $request->user()
            ->aiMemories()
            ->whereKey($memory)
            ->delete();

        abort_unless($deleted > 0, 404);

        return response()->json([
            'success' => true,
            'memory_id' => $memory,
        ]);
    }
}
