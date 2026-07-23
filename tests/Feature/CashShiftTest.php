<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashShiftTest extends TestCase
{
    use RefreshDatabase;

    private function cashier(): User
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier, ['*']);

        return $cashier;
    }

    public function test_cashier_cannot_sell_without_an_open_shift(): void
    {
        $this->cashier();

        $product = Product::create([
            'name' => 'Test Wine', 'type' => 'wine', 'price' => 10000,
            'is_stocked' => true, 'stock_quantity' => 10, 'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
    }

    public function test_opening_and_closing_a_shift_computes_expected_cash_and_discrepancy(): void
    {
        $this->cashier();

        $this->postJson('/api/admin/shifts/open', ['opening_float' => 10000])
            ->assertCreated();

        $shift = $this->getJson('/api/admin/shifts/current')->json();
        $this->assertSame('open', $shift['status']);

        $product = Product::create([
            'name' => 'Test Wine', 'type' => 'wine', 'price' => 5000,
            'is_stocked' => true, 'stock_quantity' => 10, 'status' => 'active',
        ]);

        $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'payment_method' => 'cash',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        // A card sale should not count toward the cash reconciliation.
        $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'payment_method' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        // Expected cash: 10000 opening float + 10000 (2 x 5000 cash sale) = 20000.
        $response = $this->postJson("/api/admin/shifts/{$shift['id']}/close", [
            'closing_count' => 19500,
        ]);

        $response->assertOk();
        $response->assertJsonPath('expected_cash', '20000.00');
        $response->assertJsonPath('closing_count', '19500.00');
        $response->assertJsonPath('discrepancy', '-500.00');
        $response->assertJsonPath('status', 'closed');
    }

    public function test_cashier_cannot_close_another_cashiers_shift(): void
    {
        $owner = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($owner, ['*']);
        $shift = $this->postJson('/api/admin/shifts/open', ['opening_float' => 5000])->json();

        $intruder = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($intruder, ['*']);

        $response = $this->postJson("/api/admin/shifts/{$shift['id']}/close", ['closing_count' => 5000]);

        $response->assertStatus(403);
    }
}
