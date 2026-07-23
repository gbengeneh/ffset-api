<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_top_products_ranks_by_revenue(): void
    {
        $this->admin();

        $expensive = Product::create([
            'name' => 'Expensive Wine', 'type' => 'wine', 'price' => 100000,
            'is_stocked' => true, 'stock_quantity' => 10, 'status' => 'active',
        ]);
        $cheap = Product::create([
            'name' => 'Cheap Wine', 'type' => 'wine', 'price' => 1000,
            'is_stocked' => true, 'stock_quantity' => 10, 'status' => 'active',
        ]);

        $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [
                ['product_id' => $expensive->id, 'quantity' => 1],
                ['product_id' => $cheap->id, 'quantity' => 1],
            ],
        ])->assertCreated();

        $response = $this->getJson('/api/admin/analytics/top-products');

        $response->assertOk();
        $rows = $response->json('rows');
        $this->assertSame($expensive->id, $rows[0]['product_id']);
    }

    public function test_competitions_analytics_reports_registration_counts(): void
    {
        $this->admin();

        $competition = Competition::create([
            'title' => 'Test Cup', 'entry_fee' => 5000,
            'first_prize' => 100000, 'second_prize' => 50000, 'third_prize' => 30000,
            'status' => 'open',
        ]);

        CompetitionRegistration::create([
            'competition_id' => $competition->id, 'name' => 'A', 'phone' => '1', 'email' => 'a@example.com',
            'gamertag' => 'A', 'game' => 'EA FC', 'state' => 'Ondo', 'payment_status' => 'paid',
            'reference_code' => 'FFC-A',
        ]);
        CompetitionRegistration::create([
            'competition_id' => $competition->id, 'name' => 'B', 'phone' => '2', 'email' => 'b@example.com',
            'gamertag' => 'B', 'game' => 'EA FC', 'state' => 'Ondo', 'payment_status' => 'pending',
            'reference_code' => 'FFC-B',
        ]);

        $response = $this->getJson('/api/admin/analytics/competitions');

        $response->assertOk();
        $response->assertJsonFragment([
            'total_registrations' => 2,
            'paid_registrations' => 1,
            'pending_registrations' => 1,
        ]);
    }

    public function test_bookings_analytics_groups_by_status(): void
    {
        $this->admin();

        Booking::create([
            'name' => 'A', 'phone' => '1', 'date' => now()->addDay(), 'time' => '19:00',
            'guests' => 2, 'status' => 'pending',
        ]);
        Booking::create([
            'name' => 'B', 'phone' => '2', 'date' => now()->addDay(), 'time' => '20:00',
            'guests' => 4, 'status' => 'confirmed',
        ]);

        $response = $this->getJson('/api/admin/analytics/bookings');

        $response->assertOk();
        $response->assertJsonCount(2, 'by_status');
    }

    public function test_players_analytics_reports_total_count(): void
    {
        $this->admin();
        User::factory()->count(3)->create(['role' => 'player']);

        $response = $this->getJson('/api/admin/analytics/players');

        $response->assertOk();
        $response->assertJsonPath('total_players', 3);
    }
}
