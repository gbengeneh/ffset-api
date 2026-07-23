<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        $movements = StockMovement::query()
            ->with('product')
            ->when($request->query('product_id'), fn ($query, $id) => $query->where('product_id', $id))
            ->latest()
            ->get();

        return response()->json($movements);
    }
}
