<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    private function carPayload(array $overrides = []): array
    {
        return array_merge([
            'make' => 'Toyota',
            'model' => 'Camry',
            'year' => 2023,
            'price' => 30000000,
            'deposit_amount' => 1000000,
            'mileage' => 5000,
            'condition' => 'used',
            'transmission' => 'automatic',
            'fuel_type' => 'petrol',
            'color' => 'Black',
            'status' => 'available',
        ], $overrides);
    }

    public function test_admin_can_create_a_car_and_it_provisions_a_deposit_product(): void
    {
        $this->admin();

        $response = $this->postJson('/api/admin/cars', $this->carPayload());

        $response->assertCreated();
        $car = Car::first();
        $this->assertNotNull($car->deposit_product_id);
        $this->assertSame('1000000.00', $car->depositProduct->price);
    }

    public function test_updating_deposit_amount_updates_the_linked_product_price(): void
    {
        $this->admin();

        $carId = $this->postJson('/api/admin/cars', $this->carPayload())->json('id');
        $car = Car::find($carId);

        $this->patchJson("/api/admin/cars/{$carId}", $this->carPayload(['deposit_amount' => 1500000]))
            ->assertOk();

        $this->assertSame('1500000.00', $car->fresh()->depositProduct->price);
    }

    public function test_public_can_list_and_view_cars(): void
    {
        $this->admin();
        $carId = $this->postJson('/api/admin/cars', $this->carPayload())->json('id');

        $this->getJson('/api/cars')->assertOk()->assertJsonCount(1);
        $this->getJson("/api/cars/{$carId}")->assertOk()->assertJsonPath('make', 'Toyota');
    }

    public function test_admin_can_upload_and_delete_a_car_image(): void
    {
        Storage::fake('public');
        $this->admin();

        $carId = $this->postJson('/api/admin/cars', $this->carPayload())->json('id');

        $uploadResponse = $this->postJson("/api/admin/cars/{$carId}/image", [
            'image' => UploadedFile::fake()->create('car.jpg', 100, 'image/jpeg'),
        ]);

        $uploadResponse->assertOk();
        $imageId = $uploadResponse->json('images.0.id');
        $this->assertNotNull($imageId);

        $deleteResponse = $this->deleteJson("/api/admin/cars/{$carId}/images/{$imageId}");
        $deleteResponse->assertOk();
        $this->assertCount(0, $deleteResponse->json('images'));
    }

    public function test_cashier_is_forbidden_from_managing_cars(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier, ['*']);

        $this->postJson('/api/admin/cars', $this->carPayload())->assertStatus(403);
    }
}
