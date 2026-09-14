<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessReceiptOCRRequest;
use App\Services\ReceiptOCRApiService;
use App\Services\ReceiptOCRServiceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReceiptController extends Controller
{
    public function scan(Request $request): InertiaResponse
    {
        return Inertia::render('Receipt/Scan', [
            'categories' => $request->user()
                ->categories()
                ->where('type', 'expense')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'accounts' => $request->user()
                ->financialAccounts()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Process a receipt image through the private OCR service.
     */
    public function ocr(
        ProcessReceiptOCRRequest $request,
        ReceiptOCRApiService $ocr,
    ): JsonResponse {
        try {
            return response()->json(
                $ocr->process($request->file('image')),
            );
        } catch (ReceiptOCRServiceException $exception) {
            report($exception);

            return response()->json(
                [
                    'message' => $exception->getMessage(),
                ],
                $exception->statusCode,
            );
        }
    }
}
