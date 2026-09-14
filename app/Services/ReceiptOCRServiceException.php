<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class ReceiptOCRServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 502,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
