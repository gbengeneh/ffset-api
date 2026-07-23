<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompetitionAutomationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCompetition(array $overrides = []): Competition
    {
        return Competition::create(array_merge([
            'title' => 'Test Cup',
            'entry_fee' => 5000,
            'first_prize' => 100000,
            'second_prize' => 50000,
            'third_prize' => 30000,
            'status' => 'upcoming',
        ], $overrides));
    }

    public function test_command_opens_registration_when_the_open_date_has_passed(): void
    {
        $competition = $this->makeCompetition([
            'registration_opens_at' => now()->subHour(),
        ]);

        $this->artisan('competitions:update-statuses');

        $this->assertSame('open', $competition->fresh()->status);
    }

    public function test_command_closes_registration_when_the_close_date_has_passed(): void
    {
        $competition = $this->makeCompetition([
            'status' => 'open',
            'registration_closes_at' => now()->subHour(),
        ]);

        $this->artisan('competitions:update-statuses');

        $this->assertSame('closed', $competition->fresh()->status);
    }

    public function test_command_leaves_competitions_with_no_dates_untouched(): void
    {
        $competition = $this->makeCompetition();

        $this->artisan('competitions:update-statuses');

        $this->assertSame('upcoming', $competition->fresh()->status);
    }

    public function test_command_leaves_not_yet_due_competitions_untouched(): void
    {
        $competition = $this->makeCompetition([
            'registration_opens_at' => now()->addDay(),
        ]);

        $this->artisan('competitions:update-statuses');

        $this->assertSame('upcoming', $competition->fresh()->status);
    }

    public function test_admin_can_mark_a_competition_completed_via_status_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $competition = $this->makeCompetition(['status' => 'closed']);

        $response = $this->patchJson("/api/admin/competitions/{$competition->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertOk();
        $this->assertSame('completed', $competition->fresh()->status);
    }
}
