<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.paystack.secret_key' => 'sk_test_fake_secret']);
    }

    private function pendingSaleWithRegistration(): array
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

        $this->postJson("/api/competitions/{$competition->id}/register", [
            'name' => 'Jane Doe',
            'phone' => '08012345678',
            'email' => 'jane@example.com',
            'gamertag' => 'JaneGamer',
            'game' => 'EA FC',
            'state' => 'Ondo',
        ])->assertCreated();

        $registration = CompetitionRegistration::first();
        $sale = Sale::findOrFail($registration->sale_id);

        return [$sale, $registration];
    }

    public function test_initialize_returns_authorization_url(): void
    {
        [$sale] = $this->pendingSaleWithRegistration();

        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc123',
                    'access_code' => 'abc123',
                    'reference' => 'FFP-TEST',
                ],
            ]),
        ]);

        $response = $this->postJson("/api/payments/sales/{$sale->id}/initialize", [
            'email' => 'jane@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('authorization_url', 'https://checkout.paystack.com/abc123');
        $this->assertNotNull($sale->fresh()->payment_reference);
    }

    public function test_verify_completes_pending_sale_and_marks_registration_paid(): void
    {
        [$sale, $registration] = $this->pendingSaleWithRegistration();
        $sale->update(['payment_reference' => 'FFP-TEST-REF']);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => 'FFP-TEST-REF'],
            ]),
        ]);

        $response = $this->getJson('/api/payments/verify/FFP-TEST-REF');

        $response->assertOk();
        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertSame('paid', $registration->fresh()->payment_status);
    }

    public function test_verify_completes_pending_sale_and_marks_order_paid(): void
    {
        $product = Product::create([
            'name' => 'Chateau Reserve',
            'type' => 'wine',
            'price' => 25000,
            'is_stocked' => true,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'status' => 'active',
        ]);

        $this->postJson('/api/orders', [
            'name' => 'Jane Doe',
            'phone' => '08012345678',
            'email' => 'jane@example.com',
            'fulfillment_type' => 'pickup',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated();

        $order = Order::first();
        $sale = Sale::findOrFail($order->sale_id);
        $sale->update(['payment_reference' => 'FFP-ORDER-REF']);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success', 'reference' => 'FFP-ORDER-REF'],
            ]),
        ]);

        $response = $this->getJson('/api/payments/verify/FFP-ORDER-REF');

        $response->assertOk();
        $this->assertSame('completed', $sale->fresh()->status);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock_quantity);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/api/payments/webhook/paystack', [
            'event' => 'charge.success',
            'data' => ['reference' => 'anything'],
        ], ['X-Paystack-Signature' => 'not-a-valid-signature']);

        $response->assertStatus(400);
    }

    public function test_webhook_completes_sale_on_valid_signature(): void
    {
        [$sale] = $this->pendingSaleWithRegistration();
        $sale->update(['payment_reference' => 'FFP-WEBHOOK-REF']);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => 'FFP-WEBHOOK-REF'],
        ]);

        $signature = hash_hmac('sha512', $payload, 'sk_test_fake_secret');

        $response = $this->call(
            'POST',
            '/api/payments/webhook/paystack',
            [],
            [],
            [],
            [
                'HTTP_X-Paystack-Signature' => $signature,
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertOk();
        $this->assertSame('completed', $sale->fresh()->status);
    }
}
