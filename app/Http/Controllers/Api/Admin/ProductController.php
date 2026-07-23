<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestockProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UploadProductImageRequest;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->boolean('low_stock'), fn ($query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
            ->orderBy('name')
            ->get();

        return response()->json($products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        return response()->json($product, 201);
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(null, 204);
    }

    public function restock(RestockProductRequest $request, Product $product)
    {
        $product = $this->sales->restock(
            $product,
            $request->integer('quantity'),
            $request->string('reason')->toString() ?: null,
            $request->user()->id,
        );

        return response()->json($product);
    }

    public function uploadImage(UploadProductImageRequest $request, Product $product)
    {
        if ($product->image_url && Str::startsWith($product->image_url, '/storage/')) {
            Storage::disk('public')->delete(Str::after($product->image_url, '/storage/'));
        }

        $path = $request->file('image')->store('products', 'public');

        $product->update(['image_url' => Storage::url($path)]);

        return response()->json($product);
    }
}
