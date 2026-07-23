<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleFlowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_completed_sale_deducts_stock_and_records_movement(): void
    {
        $this->admin();

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
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertCreated();
        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity_change' => -3,
            'resulting_quantity' => 7,
        ]);
    }

    public function test_sale_blocks_oversell_and_leaves_stock_unchanged(): void
    {
        $this->admin();

        $product = Product::create([
            'name' => 'Scarce Wine',
            'type' => 'wine',
            'price' => 10000,
            'is_stocked' => true,
            'stock_quantity' => 2,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
    }

    public function test_competition_registration_creates_pending_sale_without_stock_movement(): void
    {
        $entryFeeProduct = Product::create([
            'name' => 'Entry Fee',
            'type' => 'service',
            'price' => 5000,
            'is_stocked' => false,
            'status' => 'active',
        ]);

        $competition = Competition::create([
            'title' => 'Test Cup',
            'entry_fee_product_id' => $entryFeeProduct->id,
            'entry_fee' => 5000,
            'first_prize' => 100000,
            'second_prize' => 50000,
            'third_prize' => 30000,
            'status' => 'open',
        ]);

        $response = $this->postJson("/api/competitions/{$competition->id}/register", [
            'name' => 'Jane Doe',
            'phone' => '08012345678',
            'email' => 'jane@example.com',
            'gamertag' => 'JaneGamer',
            'game' => 'EA FC',
            'state' => 'Ondo',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sales', [
            'source' => 'competition_entry',
            'status' => 'pending',
            'total' => 5000,
        ]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_admin_routes_reject_unauthenticated_requests(): void
    {
        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(401);
    }
}
