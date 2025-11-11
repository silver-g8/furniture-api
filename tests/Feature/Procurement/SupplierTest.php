<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Purchase;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $staff;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $staffRole = Role::create(['name' => 'staff']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create users with roles
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->manager = User::factory()->create();
        $this->manager->roles()->attach($managerRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);

        $this->customer = User::factory()->create();
        $this->customer->roles()->attach($customerRole);
    }

    public function test_admin_can_list_suppliers(): void
    {
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/suppliers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'is_active'],
                ],
                'meta',
            ]);
    }

    public function test_staff_can_list_suppliers(): void
    {
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/v1/suppliers');

        $response->assertStatus(200);
    }

    public function test_customer_cannot_list_suppliers(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/suppliers');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_supplier(): void
    {
        $data = [
            'code' => 'SUP-TEST',
            'name' => 'Test Supplier',
            'contact_name' => 'John Doe',
            'phone' => '02-123-4567',
            'email' => 'test@supplier.com',
            'address' => '123 Test Street',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'SUP-TEST']);

        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-TEST']);
    }

    public function test_manager_can_create_supplier(): void
    {
        $data = [
            'code' => 'SUP-MGR',
            'name' => 'Manager Supplier',
        ];

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/suppliers', $data);

        $response->assertStatus(201);
    }

    public function test_staff_cannot_create_supplier(): void
    {
        $data = [
            'code' => 'SUP-TEST',
            'name' => 'Test Supplier',
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/suppliers', $data);

        $response->assertStatus(403);
    }

    public function test_supplier_code_must_be_unique(): void
    {
        Supplier::factory()->create(['code' => 'SUP-EXIST']);

        $data = [
            'code' => 'SUP-EXIST',
            'name' => 'Another Supplier',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_supplier_requires_code_and_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/suppliers', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name']);
    }

    public function test_admin_can_view_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => $supplier->code]);
    }

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create(['code' => 'SUP-OLD']);

        $data = [
            'code' => 'SUP-NEW',
            'name' => 'Updated Supplier',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/suppliers/{$supplier->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'SUP-NEW']);

        $this->assertDatabaseHas('suppliers', ['code' => 'SUP-NEW']);
    }

    public function test_staff_cannot_update_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $data = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->staff)
            ->putJson("/api/v1/suppliers/{$supplier->id}", $data);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_supplier_without_purchases(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_cannot_delete_supplier_with_purchases(): void
    {
        $supplier = Supplier::factory()->create();
        Purchase::factory()->create(['supplier_id' => $supplier->id]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_manager_cannot_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        $response = $this->actingAs($this->manager)
            ->deleteJson("/api/v1/suppliers/{$supplier->id}");

        $response->assertStatus(403);
    }

    public function test_can_filter_suppliers_by_active_status(): void
    {
        Supplier::factory()->create(['is_active' => true]);
        Supplier::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/suppliers?is_active=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_active']);
    }

    public function test_can_search_suppliers(): void
    {
        Supplier::factory()->create(['name' => 'Bangkok Supplier', 'code' => 'SUP-BKK']);
        Supplier::factory()->create(['name' => 'Chiang Mai Supplier', 'code' => 'SUP-CNX']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/suppliers?search=Bangkok');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Bangkok', $data[0]['name']);
    }
}
