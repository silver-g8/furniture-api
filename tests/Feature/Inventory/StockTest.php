<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\Role;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private User $customer;

    private Warehouse $warehouse;

    private Product $product;

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

        // Create warehouse and product
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();
    }

    public function test_admin_can_list_stocks(): void
    {
        Stock::factory()->count(3)->create([
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/stocks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'warehouse_id', 'product_id', 'quantity'],
                ],
                'meta',
            ]);
    }

    public function test_staff_can_list_stocks(): void
    {
        Stock::factory()->count(3)->create([
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->actingAs($this->staff)
            ->getJson('/api/v1/stocks');

        $response->assertStatus(200);
    }

    public function test_customer_cannot_list_stocks(): void
    {
        $response = $this->actingAs($this->customer)
            ->getJson('/api/v1/stocks');

        $response->assertStatus(403);
    }

    public function test_admin_can_add_stock_in(): void
    {
        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
            'reference_type' => 'purchase_order',
            'reference_id' => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/in', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['quantity' => 100]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'in',
            'quantity' => 100,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_staff_can_add_stock_in(): void
    {
        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/stocks/in', $data);

        $response->assertStatus(201);
    }

    public function test_customer_cannot_add_stock_in(): void
    {
        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ];

        $response = $this->actingAs($this->customer)
            ->postJson('/api/v1/stocks/in', $data);

        $response->assertStatus(403);
    }

    public function test_can_add_stock_to_existing_stock(): void
    {
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/in', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['quantity' => 80]);
    }

    public function test_admin_can_remove_stock_out(): void
    {
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 30,
            'reference_type' => 'sales_order',
            'reference_id' => 1,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/out', $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['quantity' => 70]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 70,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'out',
            'quantity' => 30,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_cannot_remove_more_stock_than_available(): void
    {
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/out', $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Insufficient stock quantity']);

        // Stock should remain unchanged
        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
        ]);
    }

    public function test_cannot_remove_stock_from_nonexistent_stock(): void
    {
        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/out', $data);

        $response->assertStatus(404);
    }

    public function test_stock_quantity_cannot_go_negative(): void
    {
        $stock = Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/out', $data);

        $response->assertStatus(422);

        // Verify stock hasn't changed
        $stock->refresh();
        $this->assertEquals(10, $stock->quantity);
    }

    public function test_can_view_stock_with_movements(): void
    {
        $stock = Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ]);

        StockMovement::factory()->count(3)->create([
            'stock_id' => $stock->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/stocks/{$stock->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'quantity',
                    'movements' => [
                        '*' => ['id', 'type', 'quantity', 'user_id'],
                    ],
                ],
            ]);
    }

    public function test_can_filter_stocks_by_warehouse(): void
    {
        $warehouse2 = Warehouse::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
        ]);

        Stock::factory()->create([
            'warehouse_id' => $warehouse2->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/stocks?warehouse_id={$this->warehouse->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->warehouse->id, $data[0]['warehouse_id']);
    }

    public function test_can_filter_stocks_by_product(): void
    {
        $product2 = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
        ]);

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product2->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/stocks?product_id={$this->product->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->product->id, $data[0]['product_id']);
    }

    public function test_stock_movement_records_user(): void
    {
        $data = [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 100,
        ];

        $this->actingAs($this->admin)
            ->postJson('/api/v1/stocks/in', $data);

        $this->assertDatabaseHas('stock_movements', [
            'type' => 'in',
            'quantity' => 100,
            'user_id' => $this->admin->id,
        ]);
    }
}
