<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private function wine(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Chateau Reserve',
            'type' => 'wine',
            'price' => 25000,
            'is_stocked' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'status' => 'active',
        ], $overrides));
    }

    private function validPayload(Product $product, int $quantity = 1): array
    {
        return [
            'name' => 'Jane Doe',
            'phone' => '08012345678',
            'email' => 'jane@example.com',
            'fulfillment_type' => 'pickup',
            'items' => [
                ['product_id' => $product->id, 'quantity' => $quantity],
            ],
        ];
    }

    public function test_guest_can_place_an_order_and_no_stock_is_deducted_yet(): void
    {
        $product = $this->wine();

        $response = $this->postJson('/api/orders', $this->validPayload($product, 2));

        $response->assertCreated();
        $response->assertJsonPath('status', 'pending');
        $this->assertNotNull($response->json('reference_code'));

        $order = Order::first();
        $this->assertNotNull($order->sale_id);
        $this->assertSame('pending', $order->sale->status);
        $this->assertSame('website_order', $order->sale->source);

        $this->assertSame(10, $product->fresh()->stock_quantity);
    }

    public function test_delivery_requires_an_address(): void
    {
        $product = $this->wine();

        $payload = $this->validPayload($product);
        $payload['fulfillment_type'] = 'delivery';

        $this->postJson('/api/orders', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['delivery_address']);
    }

    public function test_service_type_products_cannot_be_ordered(): void
    {
        $service = Product::create([
            'name' => 'Entry Fee',
            'type' => 'service',
            'price' => 5000,
            'is_stocked' => false,
            'status' => 'active',
        ]);

        $this->postJson('/api/orders', $this->validPayload($service))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_reserve_only_products_cannot_be_ordered(): void
    {
        $product = $this->wine(['status' => 'reserve_only']);

        $this->postJson('/api/orders', $this->validPayload($product))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_ordering_more_than_available_stock_is_rejected(): void
    {
        $product = $this->wine(['stock_quantity' => 3]);

        $this->postJson('/api/orders', $this->validPayload($product, 5))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_admin_can_list_orders(): void
    {
        $product = $this->wine();
        $this->postJson('/api/orders', $this->validPayload($product))->assertCreated();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_marking_paid_completes_sale_and_deducts_stock(): void
    {
        $product = $this->wine(['stock_quantity' => 10]);
        $this->postJson('/api/orders', $this->validPayload($product, 3))->assertCreated();

        $order = Order::first();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertSame(7, $product->fresh()->stock_quantity);
        $this->assertSame('completed', $order->fresh('sale')->sale->status);
    }

    public function test_admin_marking_paid_with_insufficient_stock_returns_friendly_error(): void
    {
        $product = $this->wine(['stock_quantity' => 5]);
        $this->postJson('/api/orders', $this->validPayload($product, 5))->assertCreated();

        $order = Order::first();

        // Stock sold out from under the order before admin gets to confirm it.
        $product->update(['stock_quantity' => 0]);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/admin/orders/{$order->id}", ['status' => 'paid'])
            ->assertStatus(422);

        $this->assertSame('pending', $order->fresh()->status);
    }
}
