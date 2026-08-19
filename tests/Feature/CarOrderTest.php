<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarOrderTest extends TestCase
{
    use RefreshDatabase;

    private function car(array $overrides = []): Car
    {
        $depositProduct = Product::create([
            'name' => 'Deposit — Test Car',
            'type' => 'service',
            'price' => 1000000,
            'is_stocked' => false,
            'status' => 'active',
        ]);

        return Car::create(array_merge([
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'price' => 30000000,
            'deposit_amount' => 1000000,
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'status' => 'available',
            'deposit_product_id' => $depositProduct->id,
        ], $overrides));
    }

    private function reservationPayload(): array
    {
        return [
            'name' => 'Jane Doe',
            'phone' => '08012345678',
            'email' => 'jane@example.com',
            'notes' => 'Interested in a test drive first.',
        ];
    }

    public function test_guest_can_reserve_an_available_car_with_a_deposit(): void
    {
        $car = $this->car();

        $response = $this->postJson("/api/cars/{$car->id}/reserve", $this->reservationPayload());

        $response->assertCreated();
        $response->assertJsonPath('status', 'pending');
        $this->assertNotNull($response->json('reference_code'));

        $carOrder = CarOrder::first();
        $this->assertNotNull($carOrder->sale_id);
        $this->assertSame('pending', $carOrder->sale->status);
        $this->assertSame('car_deposit', $carOrder->sale->source);
        $this->assertSame('reserved', $car->fresh()->status);
    }

    public function test_cannot_reserve_a_car_that_is_already_reserved(): void
    {
        $car = $this->car(['status' => 'reserved']);

        $this->postJson("/api/cars/{$car->id}/reserve", $this->reservationPayload())
            ->assertStatus(422);
    }

    public function test_admin_marking_paid_completes_sale_and_marks_car_order_paid(): void
    {
        $car = $this->car();
        $this->postJson("/api/cars/{$car->id}/reserve", $this->reservationPayload())->assertCreated();

        $carOrder = CarOrder::first();
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/admin/car-orders/{$carOrder->id}", ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('status', 'paid');

        $this->assertSame('completed', $carOrder->fresh('sale')->sale->status);
    }

    public function test_admin_marking_completed_marks_the_car_sold(): void
    {
        $car = $this->car();
        $this->postJson("/api/cars/{$car->id}/reserve", $this->reservationPayload())->assertCreated();
        $carOrder = CarOrder::first();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/admin/car-orders/{$carOrder->id}", ['status' => 'paid'])->assertOk();
        $this->patchJson("/api/admin/car-orders/{$carOrder->id}", ['status' => 'completed'])->assertOk();

        $this->assertSame('sold', $car->fresh()->status);
    }

    public function test_admin_cancelling_frees_up_the_car_again(): void
    {
        $car = $this->car();
        $this->postJson("/api/cars/{$car->id}/reserve", $this->reservationPayload())->assertCreated();
        $carOrder = CarOrder::first();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/admin/car-orders/{$carOrder->id}", ['status' => 'cancelled'])->assertOk();

        $this->assertSame('available', $car->fresh()->status);
    }
}
