<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\CashShift;
use App\Models\Sale;
use App\Models\User;
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
            ->when($request->user()->role === User::ROLE_CASHIER, fn ($query) => $query->where('staff_id', $request->user()->id))
            ->latest()
            ->paginate(20);

        return response()->json($sales);
    }

    public function store(StoreSaleRequest $request)
    {
        $cashShiftId = null;

        if ($request->user()->role === User::ROLE_CASHIER) {
            $openShift = CashShift::where('cashier_id', $request->user()->id)
                ->where('status', 'open')
                ->first();

            if (! $openShift) {
                return response()->json(['message' => 'Open a till shift before recording sales.'], 422);
            }

            $cashShiftId = $openShift->id;
        }

        $sale = $this->sales->createSale([
            ...$request->validated(),
            'staff_id' => $request->user()->id,
            'cash_shift_id' => $cashShiftId,
        ]);

        return response()->json($sale, 201);
    }

    public function show(Request $request, Sale $sale)
    {
        if ($request->user()->role === User::ROLE_CASHIER && $sale->staff_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

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
