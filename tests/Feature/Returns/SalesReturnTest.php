<?php

declare(strict_types=1);

namespace Tests\Feature\Returns;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesReturn;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private Warehouse $warehouse;

    private Product $product;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->manager = User::factory()->create();
        $this->manager->roles()->attach($managerRole);

        // Create warehouse and product
        $this->warehouse = Warehouse::factory()->create();
        $this->product = Product::factory()->create();

        // Create order with delivered status
        $this->order = Order::factory()->create([
            'status' => 'delivered',
            'grand_total' => 3000.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'qty' => 5,
            'price' => 600.00,
            'total' => 3000.00,
        ]);
    }

    public function test_can_list_sales_returns(): void
    {
        SalesReturn::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/returns/sales');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'order_id', 'warehouse_id', 'status', 'total'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_can_create_sales_return(): void
    {
        $data = [
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Damaged product',
            'notes' => 'Customer reported damage',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 600.00,
                    'remark' => 'Scratched',
                ],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/returns/sales', $data);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '1200.00');

        $this->assertDatabaseHas('sales_returns', [
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('sales_return_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 600.00,
        ]);
    }

    public function test_cannot_create_sales_return_without_items(): void
    {
        $data = [
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/returns/sales', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_can_view_sales_return_details(): void
    {
        $salesReturn = SalesReturn::factory()->create([
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/returns/sales/{$salesReturn->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $salesReturn->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_id',
                    'warehouse_id',
                    'status',
                    'total',
                    'order',
                    'warehouse',
                    'items',
                ],
            ]);
    }

    public function test_can_approve_sales_return_and_adjust_stock(): void
    {
        // Create initial stock
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);

        $salesReturn = SalesReturn::factory()->create([
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        $salesReturn->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 600.00,
        ]);

        $salesReturn->calculateTotals();

        $response = $this->actingAs($this->manager)
            ->postJson("/api/v1/returns/sales/{$salesReturn->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // Verify stock increased (IN)
        $stock = Stock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(12, $stock->quantity); // 10 + 2

        // Verify stock movement created
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'type' => 'in',
            'quantity' => 2,
            'reference_type' => SalesReturn::class,
            'reference_id' => $salesReturn->id,
        ]);
    }

    public function test_cannot_approve_sales_return_exceeding_order_quantity(): void
    {
        $salesReturn = SalesReturn::factory()->create([
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        // Try to return more than ordered (ordered 5, trying to return 10)
        $salesReturn->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 10,
            'price' => 600.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/v1/returns/sales/{$salesReturn->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Failed to approve sales return');
    }

    public function test_can_delete_draft_sales_return(): void
    {
        $salesReturn = SalesReturn::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/returns/sales/{$salesReturn->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('sales_returns', [
            'id' => $salesReturn->id,
        ]);
    }

    public function test_cannot_delete_approved_sales_return(): void
    {
        $salesReturn = SalesReturn::factory()->approved()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/returns/sales/{$salesReturn->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete an approved sales return');
    }

    public function test_can_filter_sales_returns_by_status(): void
    {
        SalesReturn::factory()->draft()->count(2)->create();
        SalesReturn::factory()->approved()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/returns/sales?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_sales_returns_by_reason(): void
    {
        SalesReturn::factory()->create(['reason' => 'Damaged product']);
        SalesReturn::factory()->create(['reason' => 'Wrong item']);
        SalesReturn::factory()->create(['reason' => 'Customer changed mind']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/returns/sales?search=Damaged');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthorized_user_cannot_create_sales_return(): void
    {
        $staff = User::factory()->create();
        $staffRole = Role::create(['name' => 'staff']);
        $staff->roles()->attach($staffRole);

        $data = [
            'order_id' => $this->order->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 600.00,
                ],
            ],
        ];

        $response = $this->actingAs($staff)
            ->postJson('/api/v1/returns/sales', $data);

        $response->assertForbidden();
    }
}
