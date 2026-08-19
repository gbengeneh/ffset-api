<?php

namespace App\Services;

use App\Models\MarketplaceNotificationLog;
use App\Models\MarketplaceOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MarketplaceNotificationService
{
    public function send(MarketplaceOrder $order, string $event): void
    {
        $order->loadMissing(['items', 'deliveryZone']);
        $message = $this->message($order, $event);

        $this->email($order, $event, $message);

        if ($order->whatsapp_opt_in) {
            $this->whatsapp($order, $event, $message);
        }
    }

    private function email(MarketplaceOrder $order, string $event, string $message): void
    {
        try {
            Mail::raw($message, fn ($mail) => $mail
                ->to($order->email, $order->name)
                ->subject($this->title($event).' - '.$order->reference_code));
            $this->log($order, 'email', $event, $order->email, 'sent');
        } catch (Throwable $exception) {
            Log::warning('Marketplace email failed', ['order' => $order->id, 'error' => $exception->getMessage()]);
            $this->log($order, 'email', $event, $order->email, 'failed', $exception->getMessage());
        }
    }

    private function whatsapp(MarketplaceOrder $order, string $event, string $message): void
    {
        $token = config('services.whatsapp.token');
        $phoneId = config('services.whatsapp.phone_number_id');
        $recipient = $this->phone($order->phone);

        if (! $token || ! $phoneId) {
            $this->log($order, 'whatsapp', $event, $recipient, 'skipped', 'WhatsApp credentials are not configured.');

            return;
        }

        try {
            $version = config('services.whatsapp.graph_version', 'v23.0');
            $template = config("services.whatsapp.templates.{$event}");
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
            ];

            if ($template) {
                $payload += [
                    'type' => 'template',
                    'template' => [
                        'name' => $template,
                        'language' => ['code' => config('services.whatsapp.language', 'en')],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $order->name],
                                ['type' => 'text', 'text' => $order->reference_code],
                                ['type' => 'text', 'text' => 'NGN '.number_format((float) $order->total, 2)],
                                ['type' => 'text', 'text' => str_replace('_', ' ', $order->status)],
                            ],
                        ]],
                    ],
                ];
            } else {
                $payload += ['type' => 'text', 'text' => ['preview_url' => false, 'body' => $message]];
            }

            Http::withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", $payload)
                ->throw();
            $this->log($order, 'whatsapp', $event, $recipient, 'sent');
        } catch (Throwable $exception) {
            Log::warning('Marketplace WhatsApp failed', ['order' => $order->id, 'error' => $exception->getMessage()]);
            $this->log($order, 'whatsapp', $event, $recipient, 'failed', $exception->getMessage());
        }
    }

    private function message(MarketplaceOrder $order, string $event): string
    {
        $items = $order->items->map(function ($item) {
            $options = count($item->selected_options ?? [])
                ? ' ('.collect($item->selected_options)->map(fn ($value, $key) => ucfirst($key).": {$value}")->implode(', ').')'
                : '';

            return "- {$item->listing_name} x{$item->quantity}{$options}";
        })->implode("\n");

        return $this->title($event)
            ."\n\nHello {$order->name},"
            ."\nOrder: {$order->reference_code}"
            ."\n{$items}"
            .'\nTotal: NGN '.number_format((float) $order->total, 2)
            .'\nStatus: '.str_replace('_', ' ', $order->status)
            .($order->tracking_reference ? "\nTracking: {$order->tracking_reference}" : '')
            ."\n\nThank you for choosing FFSET Store.";
    }

    private function title(string $event): string
    {
        return match ($event) {
            'order_received' => 'Order received',
            'payment_confirmed' => 'Payment confirmed',
            'processing' => 'Your order is being prepared',
            'ready_for_pickup' => 'Your order is ready for pickup',
            'dispatched' => 'Your order has been dispatched',
            'delivered' => 'Order delivered',
            'cancelled' => 'Order cancelled',
            'refunded' => 'Payment refunded',
            default => 'FFSET Store order update',
        };
    }

    private function phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '0') ? '234'.substr($digits, 1) : $digits;
    }

    private function log(MarketplaceOrder $order, string $channel, string $event, string $recipient, string $status, ?string $error = null): void
    {
        MarketplaceNotificationLog::updateOrCreate(
            ['order_id' => $order->id, 'channel' => $channel, 'event' => $event],
            ['recipient' => $recipient, 'status' => $status, 'error' => $error, 'sent_at' => $status === 'sent' ? now() : null],
        );
    }
}
