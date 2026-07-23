<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashierPermissionsTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier, ['*']);

        $this->postJson('/api/admin/shifts/open', ['opening_float' => 10000])->assertCreated();

        return $cashier;
    }

    public function test_cashier_can_create_a_pos_sale(): void
    {
        $this->cashier();

        $product = Product::create([
            'name' => 'Test Wine',
            'type' => 'wine',
            'price' => 10000,
            'is_stocked' => true,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated();
    }

    public function test_cashier_is_forbidden_from_admin_only_routes(): void
    {
        $this->cashier();

        $response = $this->postJson('/api/admin/products', [
            'name' => 'New Wine',
            'type' => 'wine',
            'price' => 5000,
            'status' => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_cashier_sales_list_only_shows_their_own_sales(): void
    {
        $cashier = $this->cashier();
        $otherCashier = User::factory()->create(['role' => 'cashier']);

        $product = Product::create([
            'name' => 'Test Wine',
            'type' => 'wine',
            'price' => 10000,
            'is_stocked' => true,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);

        \App\Models\Sale::create([
            'sale_number' => 'FF-OTHER-1',
            'staff_id' => $otherCashier->id,
            'source' => 'pos',
            'status' => 'completed',
            'subtotal' => 1000,
            'total' => 1000,
        ]);

        $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $response = $this->getJson('/api/admin/sales');

        $response->assertOk();
        $sales = $response->json('data');
        $this->assertCount(1, $sales);
        $this->assertSame($cashier->id, $sales[0]['staff_id']);
    }
}
