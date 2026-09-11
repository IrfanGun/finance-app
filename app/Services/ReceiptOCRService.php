<?php

namespace App\Services;

use RuntimeException;

class ReceiptOCRService
{
    public function modelPath(): string
    {
        return storage_path('app/models/YOLOv8_receipt.onnx');
    }

    public function modelExists(): bool
    {
        return is_file($this->modelPath());
    }

    public function assertModelAvailable(): void
    {
        if (! $this->modelExists()) {
            throw new RuntimeException(
                'YOLOv8_receipt.onnx tidak ditemukan di storage/app/models.',
            );
        }
    }
}
