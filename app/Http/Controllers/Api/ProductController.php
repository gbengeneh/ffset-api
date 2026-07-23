<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->where('status', '!=', 'inactive')
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }
}
