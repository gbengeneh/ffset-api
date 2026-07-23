<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    /**
     * Create a sale with its line items. Stock is only deducted immediately
     * when the sale is created as "completed" (in-lounge POS sale). Sales
     * created as "pending" (public wine reservations, competition entries
     * awaiting payment) leave stock untouched until completeSale() runs.
     *
     * @param  array{staff_id?:int|null, customer_name?:string|null, source:string, reference_id?:int|null, payment_method?:string|null, status?:string, items: array<int, array{product_id:int, quantity:int}>}  $data
     */
    public function createSale(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::create([
                'sale_number' => 'FF-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'staff_id' => $data['staff_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'source' => $data['source'] ?? 'pos',
                'reference_id' => $data['reference_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'subtotal' => 0,
                'total' => 0,
            ]);

            $subtotal = 0;

            foreach ($data['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                $quantity = (int) $item['quantity'];
                $lineTotal = $product->price * $quantity;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ]);

                if ($sale->status === 'completed') {
                    $this->deductStock($product, $quantity, $data['staff_id'] ?? null);
                }

                $subtotal += $lineTotal;
            }

            $sale->update(['subtotal' => $subtotal, 'total' => $subtotal]);

            return $sale->fresh('items.product');
        });
    }

    /**
     * Transition a pending sale to completed, deducting stock for every
     * stocked line item at this point.
     */
    public function completeSale(Sale $sale, ?int $userId = null): Sale
    {
        return DB::transaction(function () use ($sale, $userId) {
            $sale = Sale::where('id', $sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === 'completed') {
                return $sale;
            }

            foreach ($sale->items()->with('product')->get() as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);
                $this->deductStock($product, $item->quantity, $userId);
            }

            $sale->update(['status' => 'completed']);

            return $sale->fresh('items.product');
        });
    }

    /**
     * Add stock to a product (admin restock), recorded in the audit trail.
     */
    public function restock(Product $product, int $quantity, ?string $reason = null, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($product, $quantity, $reason, $userId) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $product->increment('stock_quantity', $quantity);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'restock',
                'quantity_change' => $quantity,
                'resulting_quantity' => $product->fresh()->stock_quantity,
                'reason' => $reason,
                'created_by' => $userId,
            ]);

            return $product->fresh();
        });
    }

    private function deductStock(Product $product, int $quantity, ?int $userId = null): void
    {
        if (! $product->is_stocked) {
            return;
        }

        if ($product->stock_quantity < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock for \"{$product->name}\": requested {$quantity}, available {$product->stock_quantity}."
            );
        }

        $product->decrement('stock_quantity', $quantity);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity_change' => -$quantity,
            'resulting_quantity' => $product->fresh()->stock_quantity,
            'created_by' => $userId,
        ]);
    }
}
