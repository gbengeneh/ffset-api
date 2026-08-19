<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InventoryPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function inventoryManager(): User
    {
        $user = User::factory()->create(['role' => 'inventory']);
        Sanctum::actingAs($user, ['*']);

        return $user;
    }

    public function test_inventory_manager_can_create_and_update_a_product(): void
    {
        $this->inventoryManager();

        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Wine',
            'type' => 'wine',
            'price' => 25000,
            'is_stocked' => true,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        $response->assertCreated();
        $product = Product::first();

        $this->patchJson("/api/admin/products/{$product->id}", [
            'name' => 'New Wine',
            'type' => 'wine',
            'price' => 30000,
            'is_stocked' => true,
            'stock_quantity' => 10,
            'status' => 'active',
        ])->assertOk();

        $this->assertSame('30000.00', $product->fresh()->price);
    }

    public function test_inventory_manager_can_record_a_purchase_invoice(): void
    {
        $this->inventoryManager();

        $supplier = Supplier::create(['name' => 'Golden Vines Ltd']);
        $product = Product::create([
            'name' => 'Test Wine', 'type' => 'wine', 'price' => 25000,
            'is_stocked' => true, 'stock_quantity' => 10, 'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/purchase-invoices', [
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-INVMGR-1',
            'invoice_date' => now()->toDateString(),
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 20000]],
        ]);

        $response->assertCreated();
        $this->assertSame(15, $product->fresh()->stock_quantity);
    }

    public function test_inventory_manager_can_browse_the_read_only_product_catalog(): void
    {
        $this->inventoryManager();

        $this->getJson('/api/admin/products')->assertOk();
    }

    public function test_inventory_manager_is_forbidden_from_admin_only_routes(): void
    {
        $this->inventoryManager();

        $this->getJson('/api/admin/bookings')->assertStatus(403);
        $this->getJson('/api/admin/staff')->assertStatus(403);
    }

    public function test_inventory_manager_is_forbidden_from_pos_routes(): void
    {
        $this->inventoryManager();

        $this->getJson('/api/admin/sales')->assertStatus(403);
        $this->postJson('/api/admin/shifts/open', ['opening_float' => 10000])->assertStatus(403);
    }
}
