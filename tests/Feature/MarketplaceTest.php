<?php

namespace Tests\Feature;

use App\Models\MarketplaceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_only_returns_active_listings(): void
    {
        $category = MarketplaceCategory::firstOrCreate(['slug' => 'autos'], ['name' => 'Autos']);
        $active = $category->listings()->create(['name' => 'Range Rover', 'slug' => 'range-rover', 'price' => 50000000, 'condition' => 'used', 'status' => 'active']);
        $category->listings()->create(['name' => 'Draft Phone', 'slug' => 'draft-phone', 'price' => 1000, 'status' => 'draft']);
        $active->vehicleDetails()->create(['make' => 'Land Rover', 'model' => 'Range Rover', 'year' => 2024]);

        $this->getJson('/api/marketplace/listings?category=autos')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.details.type', 'vehicle');
        $this->getJson('/api/marketplace/listings/range-rover')->assertOk()->assertJsonPath('name', 'Range Rover');
        $this->getJson('/api/marketplace/listings/draft-phone')->assertNotFound();
    }

    public function test_admin_can_create_an_auto_listing_with_vehicle_details(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']), ['*']);
        $category = MarketplaceCategory::firstOrCreate(['slug' => 'autos'], ['name' => 'Autos']);

        $this->postJson('/api/admin/marketplace/listings', [
            'category_id' => $category->id, 'name' => 'Toyota Camry', 'slug' => 'toyota-camry',
            'price' => 30000000, 'deposit_amount' => 1000000, 'condition' => 'used', 'status' => 'active',
            'vehicle' => ['make' => 'Toyota', 'model' => 'Camry', 'year' => 2023, 'transmission' => 'automatic'],
        ])->assertCreated()->assertJsonPath('details.make', 'Toyota');
    }
}
