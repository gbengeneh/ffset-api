<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\CompetitionRegistration;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompetitionRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function registerPlayer(): array
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

        return [$response, $competition];
    }

    public function test_reference_code_is_hidden_until_admin_approves(): void
    {
        [$response] = $this->registerPlayer();

        $response->assertCreated();
        $response->assertJsonPath('reference_code', null);

        $registration = CompetitionRegistration::first();
        $this->assertNotNull($registration->reference_code);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $this->patchJson("/api/admin/competition-registrations/{$registration->id}", [
            'payment_status' => 'paid',
        ])->assertOk();

        $player = User::factory()->create(['role' => 'player']);
        $registration->update(['player_id' => $player->id]);
        Sanctum::actingAs($player, ['*']);

        $listing = $this->getJson('/api/player/registrations');

        $listing->assertOk();
        $listing->assertJsonFragment(['reference_code' => $registration->reference_code]);
    }

    public function test_low_stock_alert_fires_once_when_stock_crosses_threshold(): void
    {
        config(['services.telegram.bot_token' => 'test-token', 'services.telegram.chat_id' => '123']);
        Http::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $product = Product::create([
            'name' => 'Scarce Wine',
            'type' => 'wine',
            'price' => 10000,
            'is_stocked' => true,
            'stock_quantity' => 6,
            'low_stock_threshold' => 5,
            'status' => 'active',
        ]);

        // First sale (6 -> 5): still above/at threshold boundary crossing point.
        $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        // Second sale (5 -> 4): still low stock, should not re-fire.
        $this->postJson('/api/admin/sales', [
            'status' => 'completed',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        Http::assertSentCount(1);
    }
}
