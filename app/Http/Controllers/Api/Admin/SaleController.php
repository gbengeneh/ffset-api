<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $sales = Sale::query()
            ->with('items.product')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('from'), fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest()
            ->get();

        return response()->json($sales);
    }

    public function store(StoreSaleRequest $request)
    {
        $sale = $this->sales->createSale([
            ...$request->validated(),
            'staff_id' => $request->user()->id,
        ]);

        return response()->json($sale, 201);
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load('items.product'));
    }

    public function updateStatus(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:completed,refunded'],
        ]);

        if ($validated['status'] === 'completed') {
            $sale = $this->sales->completeSale($sale, $request->user()->id);
        } else {
            $sale->update(['status' => 'refunded']);
        }

        return response()->json($sale->load('items.product'));
    }
}
