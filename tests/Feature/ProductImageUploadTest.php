<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_product_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $product = Product::create([
            'name' => 'Test Wine', 'type' => 'wine', 'price' => 10000, 'status' => 'active',
        ]);

        $file = UploadedFile::fake()->create('bottle.jpg', 100, 'image/jpeg');

        $response = $this->postJson("/api/admin/products/{$product->id}/image", [
            'image' => $file,
        ]);

        $response->assertOk();
        $this->assertStringStartsWith('/storage/products/', $response->json('image_url'));

        $path = str_replace('/storage/', '', $response->json('image_url'));
        Storage::disk('public')->assertExists($path);
    }

    public function test_cashier_is_forbidden_from_uploading_product_images(): void
    {
        Storage::fake('public');

        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier, ['*']);

        $product = Product::create([
            'name' => 'Test Wine', 'type' => 'wine', 'price' => 10000, 'status' => 'active',
        ]);

        $response = $this->postJson("/api/admin/products/{$product->id}/image", [
            'image' => UploadedFile::fake()->create('bottle.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertStatus(403);
    }
}
