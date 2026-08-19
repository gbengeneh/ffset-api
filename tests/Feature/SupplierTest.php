<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin, ['*']);

        return $admin;
    }

    public function test_admin_can_create_list_and_update_a_supplier(): void
    {
        $this->admin();

        $response = $this->postJson('/api/admin/suppliers', [
            'name' => 'Golden Vines Ltd',
            'contact_name' => 'Jane Doe',
            'phone' => '08012345678',
        ]);
        $response->assertCreated();
        $supplierId = $response->json('id');

        $this->getJson('/api/admin/suppliers')->assertOk()->assertJsonFragment(['name' => 'Golden Vines Ltd']);

        $this->patchJson("/api/admin/suppliers/{$supplierId}", ['name' => 'Golden Vines Limited'])
            ->assertOk()
            ->assertJsonPath('name', 'Golden Vines Limited');
    }

    public function test_deactivating_a_supplier_hides_it_from_the_default_list_but_preserves_it(): void
    {
        $this->admin();
        $supplier = Supplier::create(['name' => 'Test Supplier']);

        $this->deleteJson("/api/admin/suppliers/{$supplier->id}")->assertOk();

        $this->assertFalse($supplier->fresh()->is_active);
        $this->getJson('/api/admin/suppliers')->assertOk()->assertJsonMissing(['name' => 'Test Supplier']);
        $this->getJson('/api/admin/suppliers?include_inactive=1')->assertOk()->assertJsonFragment(['name' => 'Test Supplier']);
    }

    public function test_cashier_is_forbidden_from_managing_suppliers(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        Sanctum::actingAs($cashier, ['*']);

        $this->getJson('/api/admin/suppliers')->assertStatus(403);
    }
}
