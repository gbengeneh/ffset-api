<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
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
}
