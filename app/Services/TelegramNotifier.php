<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    /**
     * Ported from ffset/app/api/telegram/route.ts so form submissions keep
     * landing in the same Telegram chat once the frontend talks to this API.
     *
     * @param  array<string, string>  $fields  label => value
     */
    public function send(string $formType, array $fields): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $token) {
            Log::warning('Telegram bot token is not configured; skipping notification.');

            return;
        }

        $lines = ["FFSET {$formType}", ''];
        foreach ($fields as $label => $value) {
            $lines[] = "{$label}: ".($value !== '' ? $value : '-');
        }

        try {
            $response = Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => implode("\n", $lines),
            ]);

            if (! $response->successful()) {
                Log::error('Telegram delivery failed.', ['body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('Telegram delivery threw an exception.', ['message' => $e->getMessage()]);
        }
    }
}
