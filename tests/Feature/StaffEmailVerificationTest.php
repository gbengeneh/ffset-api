<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_staff_account_is_verified_immediately(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/admin/staff', [
            'name' => 'New Cashier',
            'email' => 'newcashier@example.com',
            'password' => 'password123',
            'role' => 'cashier',
        ]);

        $response->assertCreated();

        $staff = User::where('email', 'newcashier@example.com')->firstOrFail();
        $this->assertTrue($staff->hasVerifiedEmail());
    }

    public function test_resend_verification_is_a_no_op_for_an_already_verified_staff_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
        Sanctum::actingAs($admin, ['*']);

        $response = $this->postJson('/api/auth/email/resend');

        $response->assertOk();
        $response->assertJsonPath('message', 'Email already verified.');
    }

    public function test_player_registration_still_sends_verification(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();

        $player = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertFalse($player->hasVerifiedEmail());
    }
}
