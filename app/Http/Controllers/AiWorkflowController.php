<?php

namespace App\Http\Controllers;

use App\Models\AiWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiWorkflowController extends Controller
{
    public function cancel(Request $request, AiWorkflow $workflow): JsonResponse
    {
        abort_unless(
            $workflow->conversation()->where('user_id', $request->user()->id)->exists(),
            404,
        );

        $workflow = DB::transaction(function () use ($workflow): AiWorkflow {
            $workflow = AiWorkflow::query()
                ->whereKey($workflow->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($workflow->status, ['awaiting_input', 'ready'], true)) {
                $workflow->update([
                    'status' => 'cancelled',
                    'version' => $workflow->version + 1,
                ]);
            }

            return $workflow->fresh();
        });

        return response()->json([
            'success' => true,
            'workflow_id' => $workflow->id,
            'status' => $workflow->fresh()->status,
        ]);
    }
}
