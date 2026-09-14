<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

class ReceiptOCRApiService
{
    /**
     * Send a receipt image to the private OCR service.
     *
     * @return array<string, mixed>
     */
    public function process(UploadedFile $image): array
    {
        $url = rtrim((string) config('services.ocr.url'), '/').'/ocr';
        $token = (string) config('services.ocr.token');

        $client = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(60);

        if ($token !== '') {
            $client = $client->withHeader('X-OCR-Token', $token);
        }

        try {
            $response = $client
                ->attach(
                    'file',
                    $image->getContent(),
                    $image->hashName(),
                )
                ->post($url);
        } catch (ConnectionException $exception) {
            throw new ReceiptOCRServiceException(
                'OCR service tidak dapat dihubungi.',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            throw new ReceiptOCRServiceException(
                'OCR service gagal memproses gambar.',
                previous: $exception,
            );
        }

        if (! $response->successful()) {
            $message = $response->json('detail')
                ?? $response->json('message')
                ?? 'OCR service mengembalikan error.';

            throw new ReceiptOCRServiceException(
                (string) $message,
                $response->status() === 422 ? 422 : 502,
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new ReceiptOCRServiceException(
                'Respons OCR service tidak valid.',
            );
        }

        return $payload;
    }
}
