<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarOrder;
use App\Services\SaleService;
use Illuminate\Http\Request;

class CarOrderController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $carOrders = CarOrder::query()
            ->with('car', 'sale.items.product')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return response()->json($carOrders);
    }

    public function update(Request $request, CarOrder $carOrder)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,paid,completed,cancelled'],
        ]);

        if ($validated['status'] === 'paid' && $carOrder->sale && $carOrder->sale->status !== 'completed') {
            $this->sales->completeSale($carOrder->sale, $request->user()->id);
        }

        if ($validated['status'] === 'completed') {
            $carOrder->car()->update(['status' => 'sold']);
        }

        if ($validated['status'] === 'cancelled' && $carOrder->car->status === 'reserved') {
            $carOrder->car()->update(['status' => 'available']);
        }

        $carOrder->update($validated);

        return response()->json($carOrder->fresh('car', 'sale.items.product'));
    }
}
