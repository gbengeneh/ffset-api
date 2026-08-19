<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InsufficientStockException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SaleService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $orders = Order::query()
            ->with('sale.items.product')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,completed,cancelled'],
        ]);

        if ($validated['status'] === 'paid' && $order->sale && $order->sale->status !== 'completed') {
            try {
                $this->sales->completeSale($order->sale, $request->user()->id);
            } catch (InsufficientStockException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $order->update($validated);

        return response()->json($order->fresh('sale.items.product'));
    }
}
