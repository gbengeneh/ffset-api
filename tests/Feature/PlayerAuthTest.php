<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use App\Models\MarketplaceCategory;
use App\Models\MarketplaceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlayerAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_can_register_and_login(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated();
        $register->assertJsonPath('user.role', 'player');
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com', 'role' => 'player']);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk();
        $login->assertJsonPath('user.role', 'player');
    }

    public function test_player_only_sees_their_own_registrations_and_bookings(): void
    {
        $me = User::factory()->create(['role' => 'player']);
        $someoneElse = User::factory()->create(['role' => 'player']);

        Booking::create([
            'player_id' => $me->id,
            'name' => 'Me',
            'phone' => '080',
            'date' => now()->addDay(),
            'time' => '19:00',
            'guests' => 2,
            'status' => 'pending',
        ]);

        Booking::create([
            'player_id' => $someoneElse->id,
            'name' => 'Someone Else',
            'phone' => '081',
            'date' => now()->addDay(),
            'time' => '20:00',
            'guests' => 4,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($me, ['*']);

        $response = $this->getJson('/api/player/bookings');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'Me']);
        $response->assertJsonMissing(['name' => 'Someone Else']);
    }

    public function test_player_can_save_a_store_profile(): void
    {
        $player = User::factory()->create(['role' => User::ROLE_PLAYER]);
        Sanctum::actingAs($player, ['*']);

        $this->putJson('/api/customer/profile', [
            'name' => 'Repeat Customer',
            'email' => $player->email,
            'phone' => '08012345678',
            'delivery_address' => '12 Store Street, Akure',
            'preferred_fulfillment_type' => 'delivery',
            'preferred_delivery_zone_id' => null,
            'whatsapp_opt_in' => true,
        ])->assertOk()
            ->assertJsonPath('delivery_address', '12 Store Street, Akure')
            ->assertJsonPath('whatsapp_opt_in', true);

        $this->assertDatabaseHas('users', [
            'id' => $player->id,
            'phone' => '08012345678',
            'preferred_fulfillment_type' => 'delivery',
        ]);
    }

    public function test_player_only_sees_their_own_marketplace_orders(): void
    {
        $me = User::factory()->create(['role' => User::ROLE_PLAYER]);
        $someoneElse = User::factory()->create(['role' => User::ROLE_PLAYER]);
        $category = MarketplaceCategory::firstOrCreate(['slug' => 'gadgets'], ['name' => 'Gadgets']);
        $listing = $category->listings()->create(['name' => 'Phone', 'slug' => 'account-phone', 'price' => 100000, 'condition' => 'new', 'status' => 'active', 'stock_quantity' => 3]);

        Sanctum::actingAs($me, ['*']);
        $mine = $this->postJson('/api/marketplace/orders', [
            'name' => $me->name,
            'phone' => '08012345678',
            'email' => $me->email,
            'fulfillment_type' => 'pickup',
            'items' => [['listing_id' => $listing->id, 'quantity' => 1, 'purchase_type' => 'full']],
        ])->assertCreated();

        MarketplaceOrder::create([
            'player_id' => $someoneElse->id,
            'reference_code' => 'FFM-PRIVATE',
            'name' => $someoneElse->name,
            'phone' => '08000000000',
            'email' => $someoneElse->email,
            'fulfillment_type' => 'pickup',
            'subtotal' => 1,
            'delivery_fee' => 0,
            'total' => 1,
        ]);

        $this->getJson('/api/customer/orders')
            ->assertOk()
            ->assertJsonPath('data.0.reference_code', $mine->json('reference_code'))
            ->assertJsonMissing(['reference_code' => 'FFM-PRIVATE']);

        $this->getJson('/api/customer/orders/'.MarketplaceOrder::where('reference_code', 'FFM-PRIVATE')->value('id'))->assertNotFound();
    }
}
