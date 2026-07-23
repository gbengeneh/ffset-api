<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackService
{
    private const BASE_URL = 'https://api.paystack.co';

    /**
     * @param  array{email: string, amount: int, reference: string, callback_url?: string, metadata?: array<string, mixed>}  $data
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(array $data): array
    {
        $response = Http::withToken($this->secretKey())
            ->post(self::BASE_URL.'/transaction/initialize', $data);

        if (! $response->successful()) {
            throw new RuntimeException('Paystack initialization failed: '.$response->body());
        }

        return $response->json('data');
    }

    /**
     * @return array{status: string, reference: string, amount: int}
     */
    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey())
            ->get(self::BASE_URL."/transaction/verify/{$reference}");

        if (! $response->successful()) {
            throw new RuntimeException('Paystack verification failed: '.$response->body());
        }

        return $response->json('data');
    }

    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha512', $rawBody, $this->secretKey());

        return hash_equals($expected, $signature);
    }

    private function secretKey(): string
    {
        $key = config('services.paystack.secret_key');

        if (! $key) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return $key;
    }
}
