<?php

namespace App\Services;

use App\Exceptions\PurchaseInvoiceLockedException;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    /**
     * Create a purchase invoice with its line items as "pending" — stock is
     * left untouched until receivePurchaseInvoice() runs, mirroring how a
     * pending Sale doesn't move stock until completeSale().
     *
     * @param  array{supplier_id:int, invoice_number:string, invoice_date:string, due_date?:string|null, notes?:string|null, items: array<int, array{product_id:int, quantity:int, unit_cost:float}>}  $data
     */
    public function createPurchaseInvoice(array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => 0,
                'total' => 0,
            ]);

            $this->syncItems($invoice, $data['items']);

            return $invoice->fresh(['items.product', 'supplier']);
        });
    }

    /**
     * Replace a purchase invoice's header fields and line items. Only
     * allowed while the invoice is still pending — once received, stock has
     * already moved, so retroactively editing quantities would desync the
     * audit trail.
     *
     * @param  array{supplier_id:int, invoice_number:string, invoice_date:string, due_date?:string|null, notes?:string|null, items: array<int, array{product_id:int, quantity:int, unit_cost:float}>}  $data
     */
    public function updatePurchaseInvoice(PurchaseInvoice $invoice, array $data): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice = PurchaseInvoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->status !== 'pending') {
                throw new PurchaseInvoiceLockedException(
                    'Only pending purchase invoices can be edited.'
                );
            }

            $invoice->update([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->items()->delete();
            $this->syncItems($invoice, $data['items']);

            return $invoice->fresh(['items.product', 'supplier']);
        });
    }

    /**
     * Mark a pending invoice received: increments stock for every stocked
     * line item and writes a stock_movements audit row, exactly like a
     * completed Sale's deductStock() but in the opposite direction.
     * Idempotent — calling this twice is a no-op on the second call.
     */
    public function receivePurchaseInvoice(PurchaseInvoice $invoice, ?int $userId = null): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoice, $userId) {
            $invoice = PurchaseInvoice::where('id', $invoice->id)->lockForUpdate()->firstOrFail();

            if ($invoice->status === 'received') {
                return $invoice->fresh(['items.product', 'supplier']);
            }

            if ($invoice->status === 'cancelled') {
                throw new PurchaseInvoiceLockedException(
                    'This purchase invoice has been cancelled and cannot be received.'
                );
            }

            foreach ($invoice->items()->with('product')->get() as $item) {
                $product = Product::lockForUpdate()->findOrFail($item->product_id);

                if ($item->new_price !== null) {
                    $product->update(['price' => $item->new_price]);
                }

                if (! $product->is_stocked) {
                    continue;
                }

                $product->increment('stock_quantity', $item->quantity);

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'restock',
                    'quantity_change' => $item->quantity,
                    'resulting_quantity' => $product->fresh()->stock_quantity,
                    'reason' => "Purchase {$invoice->invoice_number}",
                    'created_by' => $userId,
                ]);
            }

            $invoice->update(['status' => 'received']);

            return $invoice->fresh(['items.product', 'supplier']);
        });
    }

    /**
     * Cancel a still-pending invoice. Once received, stock has already
     * moved, so cancellation is no longer meaningful — reversing that would
     * need a separate return/adjustment flow, not this.
     */
    public function cancelPurchaseInvoice(PurchaseInvoice $invoice): PurchaseInvoice
    {
        if ($invoice->status !== 'pending') {
            throw new PurchaseInvoiceLockedException(
                'Only pending purchase invoices can be cancelled.'
            );
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice->fresh(['items.product', 'supplier']);
    }

    public function updatePaymentStatus(PurchaseInvoice $invoice, string $status): PurchaseInvoice
    {
        $invoice->update(['payment_status' => $status]);

        return $invoice->fresh(['items.product', 'supplier']);
    }

    /**
     * @param  array<int, array{product_id:int, quantity:int, unit_cost:float, new_price?:float|null}>  $items
     */
    private function syncItems(PurchaseInvoice $invoice, array $items): void
    {
        $subtotal = 0;

        foreach ($items as $item) {
            $product = Product::findOrFail($item['product_id']);
            $quantity = (int) $item['quantity'];
            $unitCost = (float) $item['unit_cost'];
            $lineTotal = $unitCost * $quantity;

            $invoice->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'new_price' => isset($item['new_price']) ? (float) $item['new_price'] : null,
                'line_total' => $lineTotal,
            ]);

            $subtotal += $lineTotal;
        }

        $invoice->update(['subtotal' => $subtotal, 'total' => $subtotal]);
    }
}
