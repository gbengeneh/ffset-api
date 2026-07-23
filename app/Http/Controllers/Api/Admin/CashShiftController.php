<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseShiftRequest;
use App\Http\Requests\OpenShiftRequest;
use App\Models\CashShift;
use App\Models\User;
use Illuminate\Http\Request;

class CashShiftController extends Controller
{
    public function index(Request $request)
    {
        $shifts = CashShift::query()
            ->with('cashier:id,name')
            ->when($request->user()->role === User::ROLE_CASHIER, fn ($query) => $query->where('cashier_id', $request->user()->id))
            ->latest('opened_at')
            ->paginate(20);

        return response()->json($shifts);
    }

    public function current(Request $request)
    {
        $shift = CashShift::where('cashier_id', $request->user()->id)
            ->where('status', 'open')
            ->first();

        return response()->json($shift);
    }

    public function open(OpenShiftRequest $request)
    {
        $existing = CashShift::where('cashier_id', $request->user()->id)
            ->where('status', 'open')
            ->exists();

        if ($existing) {
            return response()->json(['message' => 'You already have an open till shift.'], 422);
        }

        $shift = CashShift::create([
            'cashier_id' => $request->user()->id,
            'opening_float' => $request->input('opening_float'),
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return response()->json($shift, 201);
    }

    public function close(CloseShiftRequest $request, CashShift $cashShift)
    {
        if ($request->user()->role === User::ROLE_CASHIER && $cashShift->cashier_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($cashShift->status === 'closed') {
            return response()->json(['message' => 'This shift is already closed.'], 422);
        }

        $cashSalesTotal = (float) $cashShift->sales()
            ->where('payment_method', 'cash')
            ->where('status', 'completed')
            ->sum('total');

        $expectedCash = (float) $cashShift->opening_float + $cashSalesTotal;
        $closingCount = (float) $request->input('closing_count');

        $cashShift->update([
            'expected_cash' => $expectedCash,
            'closing_count' => $closingCount,
            'discrepancy' => $closingCount - $expectedCash,
            'notes' => $request->input('notes'),
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json($cashShift);
    }
}
