<?php

namespace App\Http\Controllers;

use App\Services\ReceiptOCRService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function model(ReceiptOCRService $ocr): BinaryFileResponse
    {
        $ocr->assertModelAvailable();

        return response()->file(
            $ocr->modelPath(),
            [
                'Content-Type' => 'application/octet-stream',
                'Cache-Control' => 'private, max-age=3600',
                'Content-Disposition' => 'inline; filename="YOLOv8_receipt.onnx"',
            ],
        );
    }
}
