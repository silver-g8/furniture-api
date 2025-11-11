<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GRNTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $staffRole = Role::create(['name' => 'staff']);

        // Create users with roles
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);
    }

    public function test_admin_can_list_goods_receipts(): void
    {
        GoodsReceipt::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/grn');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'purchase_id', 'received_at', 'received_by'],
                ],
                'meta',
            ]);
    }

    public function test_staff_can_list_goods_receipts(): void
    {
        GoodsReceipt::factory()->count(3)->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/v1/grn');

        $response->assertStatus(200);
    }

    public function test_staff_can_receive_goods_for_approved_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $purchase = Purchase::factory()->approved()->create([
            'supplier_id' => $supplier->id,
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'qty' => 10,
        ]);

        $data = [
            'purchase_id' => $purchase->id,
            'received_at' => now()->toDateTimeString(),
            'notes' => 'Goods received',
            'items' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'qty' => 10,
                    'remarks' => 'All items received',
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['purchase_id' => $purchase->id]);

        $this->assertDatabaseHas('goods_receipts', [
            'purchase_id' => $purchase->id,
        ]);

        $this->assertDatabaseHas('goods_receipt_items', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'qty' => 10,
        ]);
    }

    public function test_receiving_goods_updates_stock(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $purchase = Purchase::factory()->approved()->create([
            'supplier_id' => $supplier->id,
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'qty' => 15,
        ]);

        $data = [
            'purchase_id' => $purchase->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'qty' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(201);

        // Verify stock was created/updated
        $stock = Stock::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(15, $stock->quantity);

        // Verify stock movement was recorded
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'type' => 'in',
            'quantity' => 15,
        ]);
    }

    public function test_receiving_goods_increments_existing_stock(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        // Create existing stock
        $stock = Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $purchase = Purchase::factory()->approved()->create([
            'supplier_id' => $supplier->id,
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'qty' => 20,
        ]);

        $data = [
            'purchase_id' => $purchase->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'qty' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(201);

        // Verify stock was incremented
        $stock->refresh();
        $this->assertEquals(30, $stock->quantity); // 10 + 20
    }

    public function test_cannot_receive_goods_for_draft_purchase(): void
    {
        $purchase = Purchase::factory()->draft()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $data = [
            'purchase_id' => $purchase->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'qty' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot receive goods for non-approved purchase']);
    }

    public function test_cannot_receive_goods_twice_for_same_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();

        $purchase = Purchase::factory()->approved()->create([
            'supplier_id' => $supplier->id,
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
        ]);

        // First receipt
        GoodsReceipt::factory()->create([
            'purchase_id' => $purchase->id,
        ]);

        // Try second receipt
        $data = [
            'purchase_id' => $purchase->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'qty' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Goods already received for this purchase']);
    }

    public function test_admin_can_view_goods_receipt(): void
    {
        $grn = GoodsReceipt::factory()->create();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/grn/{$grn->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_grn_requires_purchase_and_items(): void
    {
        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['purchase_id', 'items']);
    }

    public function test_grn_items_require_product_warehouse_and_qty(): void
    {
        $purchase = Purchase::factory()->approved()->create();

        $data = [
            'purchase_id' => $purchase->id,
            'items' => [
                [], // Empty item
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/grn', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items.0.product_id',
                'items.0.warehouse_id',
                'items.0.qty',
            ]);
    }

    public function test_can_filter_grn_by_purchase(): void
    {
        $purchase1 = Purchase::factory()->approved()->create();
        $purchase2 = Purchase::factory()->approved()->create();

        GoodsReceipt::factory()->create(['purchase_id' => $purchase1->id]);
        GoodsReceipt::factory()->create(['purchase_id' => $purchase2->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/grn?purchase_id={$purchase1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($purchase1->id, $data[0]['purchase_id']);
    }

    public function test_can_filter_grn_by_date_range(): void
    {
        GoodsReceipt::factory()->create([
            'received_at' => now()->subDays(10),
        ]);
        GoodsReceipt::factory()->create([
            'received_at' => now()->subDays(5),
        ]);
        GoodsReceipt::factory()->create([
            'received_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/grn?from_date='.now()->subDays(7)->toDateString().'&to_date='.now()->toDateString());

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }
}
