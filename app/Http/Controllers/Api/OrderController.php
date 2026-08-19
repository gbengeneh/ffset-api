<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        private SaleService $sales,
        private TelegramNotifier $telegram,
    ) {}

    public function store(StoreOrderRequest $request)
    {
        /** @var User|null $player */
        $player = $request->user('sanctum');
        $player = $player && $player->role === User::ROLE_PLAYER ? $player : null;

        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated, $player) {
            $order = Order::create([
                'player_id' => $player?->id,
                'reference_code' => 'FFO-'.strtoupper(Str::random(8)),
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'fulfillment_type' => $validated['fulfillment_type'],
                'delivery_address' => $validated['delivery_address'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
            ]);

            $sale = $this->sales->createSale([
                'source' => 'website_order',
                'reference_id' => $order->id,
                'status' => 'pending',
                'customer_name' => $order->name,
                'customer_email' => $order->email,
                'items' => $validated['items'],
            ]);

            $order->update(['sale_id' => $sale->id]);

            return $order;
        });

        $order->load('sale.items.product');

        $itemLines = $order->sale->items->map(
            fn ($item) => "{$item->product->name} x{$item->quantity} — ₦".number_format((float) $item->line_total, 2)
        )->implode("\n");

        $this->telegram->send('Website Order', [
            'Order Reference' => $order->reference_code,
            'Full Name' => $order->name,
            'Phone Number' => $order->phone,
            'Email' => $order->email,
            'Fulfillment' => $order->fulfillment_type,
            'Delivery Address' => $order->delivery_address ?? '-',
            'Notes' => $order->notes ?? '-',
            'Items' => $itemLines,
            'Total' => '₦'.number_format((float) $order->sale->total, 2),
        ]);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }
}
