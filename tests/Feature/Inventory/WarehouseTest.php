<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\Role;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);
        $customerRole = Role::create(['name' => 'customer']);

        // Create users with roles
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);

        $this->customer = User::factory()->create();
        $this->customer->roles()->attach($customerRole);
    }

    public function test_admin_can_list_warehouses(): void
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/warehouses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'code', 'name', 'is_active'],
                ],
                'meta',
            ]);
    }

    public function test_staff_can_list_warehouses(): void
    {
        Warehouse::factory()->count(3)->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/v1/warehouses');

        $response->assertStatus(200);
    }

    public function test_customer_cannot_list_warehouses(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/warehouses');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_warehouse(): void
    {
        $data = [
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/warehouses', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'WH-TEST']);

        $this->assertDatabaseHas('warehouses', ['code' => 'WH-TEST']);
    }

    public function test_staff_cannot_create_warehouse(): void
    {
        $data = [
            'code' => 'WH-TEST',
            'name' => 'Test Warehouse',
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/warehouses', $data);

        $response->assertStatus(403);
    }

    public function test_warehouse_code_must_be_unique(): void
    {
        Warehouse::factory()->create(['code' => 'WH-EXIST']);

        $data = [
            'code' => 'WH-EXIST',
            'name' => 'Another Warehouse',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/warehouses', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_admin_can_view_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => $warehouse->code]);
    }

    public function test_admin_can_update_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create(['code' => 'WH-OLD']);

        $data = [
            'code' => 'WH-NEW',
            'name' => 'Updated Warehouse',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/warehouses/{$warehouse->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'WH-NEW']);

        $this->assertDatabaseHas('warehouses', ['code' => 'WH-NEW']);
    }

    public function test_staff_cannot_update_warehouse(): void
    {
        $warehouse = Warehouse::factory()->create();

        $data = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->staff)
            ->putJson("/api/v1/warehouses/{$warehouse->id}", $data);

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_warehouse_without_stocks(): void
    {
        $warehouse = Warehouse::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('warehouses', ['id' => $warehouse->id]);
    }

    public function test_cannot_delete_warehouse_with_stocks(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/warehouses/{$warehouse->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('warehouses', ['id' => $warehouse->id]);
    }

    public function test_can_filter_warehouses_by_active_status(): void
    {
        Warehouse::factory()->create(['is_active' => true]);
        Warehouse::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/warehouses?is_active=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_active']);
    }

    public function test_can_search_warehouses(): void
    {
        Warehouse::factory()->create(['name' => 'Bangkok Warehouse', 'code' => 'WH-BKK']);
        Warehouse::factory()->create(['name' => 'Chiang Mai Warehouse', 'code' => 'WH-CNX']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/warehouses?search=Bangkok');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Bangkok', $data[0]['name']);
    }
}
