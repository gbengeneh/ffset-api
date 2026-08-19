<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    private function supplier(): Supplier
    {
        return Supplier::create(['name' => 'Golden Vines Ltd']);
    }

    private function product(int $stock = 10): Product
    {
        return Product::create([
            'name' => 'Test Wine', 'type' => 'wine', 'price' => 50000,
            'is_stocked' => true, 'stock_quantity' => $stock, 'status' => 'active',
        ]);
    }

    public function test_creating_a_purchase_invoice_leaves_stock_untouched(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $response = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-001',
            'invoice_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000],
            ],
            'receive_immediately' => false,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'pending');
        $response->assertJsonPath('total', '200000.00');
        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_receiving_a_purchase_invoice_increments_stock_and_logs_movement(): void
    {
        $admin = $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $invoice = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-002',
            'invoice_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000],
            ],
            'receive_immediately' => false,
        ])->json();

        $response = $this->patchJson("/api/admin/purchase-invoices/{$invoice['id']}/status", [
            'status' => 'received',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'received');
        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'restock',
            'quantity_change' => 5,
            'resulting_quantity' => 15,
            'reason' => 'Purchase INV-002',
        ]);
    }

    public function test_receiving_twice_is_idempotent(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $invoice = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-003',
            'invoice_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000]],
            'receive_immediately' => false,
        ])->json();

        $this->patchJson("/api/admin/purchase-invoices/{$invoice['id']}/status", ['status' => 'received'])->assertOk();
        $this->patchJson("/api/admin/purchase-invoices/{$invoice['id']}/status", ['status' => 'received'])->assertOk();

        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_updating_a_received_invoice_is_rejected(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $invoice = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-004',
            'invoice_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000]],
            'receive_immediately' => false,
        ])->json();

        $this->patchJson("/api/admin/purchase-invoices/{$invoice['id']}/status", ['status' => 'received'])->assertOk();

        $response = $this->putJson("/api/admin/purchase-invoices/{$invoice['id']}", [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-004',
            'invoice_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 99, 'unit_cost' => 1]],
        ]);

        $response->assertStatus(422);
    }

    public function test_deleting_a_received_invoice_is_rejected(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $invoice = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-005',
            'invoice_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000]],
            'receive_immediately' => false,
        ])->json();

        $this->patchJson("/api/admin/purchase-invoices/{$invoice['id']}/status", ['status' => 'received'])->assertOk();

        $this->deleteJson("/api/admin/purchase-invoices/{$invoice['id']}")->assertStatus(422);
    }

    public function test_cashier_is_forbidden_from_purchase_invoices(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier, ['*']);

        $this->getJson('/api/admin/purchase-invoices')->assertStatus(403);
    }

    public function test_creating_with_receive_immediately_updates_stock_and_price_right_away(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $response = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-006',
            'invoice_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000, 'new_price' => 55000],
            ],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('status', 'received');
        $this->assertSame(15, $product->fresh()->stock_quantity);
        $this->assertSame('55000.00', $product->fresh()->price);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'restock',
            'quantity_change' => 5,
            'reason' => 'Purchase INV-006',
        ]);
    }

    public function test_omitting_new_price_leaves_the_product_price_unchanged(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-007',
            'invoice_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000],
            ],
        ])->assertCreated();

        $this->assertSame('50000.00', $product->fresh()->price);
    }

    public function test_new_price_is_stored_but_not_applied_until_manually_received(): void
    {
        $this->admin();
        $supplier = $this->supplier();
        $product = $this->product(10);

        $invoice = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-008',
            'invoice_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 40000, 'new_price' => 60000],
            ],
            'receive_immediately' => false,
        ])->json();

        $this->assertSame('50000.00', $product->fresh()->price);

        $this->patchJson("/api/admin/purchase-invoices/{$invoice['id']}/status", ['status' => 'received'])->assertOk();

        $this->assertSame('60000.00', $product->fresh()->price);
    }
}
