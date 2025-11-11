<?php

declare(strict_types=1);

namespace Tests\Feature\Returns;

use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\Role;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private Warehouse $warehouse;

    private Product $product;

    private Purchase $purchase;

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

        // Create warehouse, supplier, and product
        $this->warehouse = Warehouse::factory()->create();
        $supplier = Supplier::factory()->create();
        $this->product = Product::factory()->create();

        // Create purchase with goods receipt
        $this->purchase = Purchase::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'approved',
            'grand_total' => 5000.00,
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $this->purchase->id,
            'product_id' => $this->product->id,
            'qty' => 10,
            'price' => 500.00,
        ]);

        // Create goods receipt to mark as received
        GoodsReceipt::factory()->create([
            'purchase_id' => $this->purchase->id,
        ]);

        // Create stock for the product
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
        ]);
    }

    public function test_can_list_purchase_returns(): void
    {
        PurchaseReturn::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/returns/purchases');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'purchase_id', 'warehouse_id', 'status', 'total'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_can_create_purchase_return(): void
    {
        $data = [
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Defective items',
            'notes' => 'Quality issues found',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'price' => 500.00,
                    'remark' => 'Broken parts',
                ],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/returns/purchases', $data);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.total', '1500.00');

        $this->assertDatabaseHas('purchase_returns', [
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('purchase_return_items', [
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 500.00,
        ]);
    }

    public function test_cannot_create_purchase_return_without_items(): void
    {
        $data = [
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/returns/purchases', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);
    }

    public function test_can_view_purchase_return_details(): void
    {
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/returns/purchases/{$purchaseReturn->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $purchaseReturn->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'purchase_id',
                    'warehouse_id',
                    'status',
                    'total',
                    'purchase',
                    'warehouse',
                    'items',
                ],
            ]);
    }

    public function test_can_approve_purchase_return_and_adjust_stock(): void
    {
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        $purchaseReturn->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 3,
            'price' => 500.00,
        ]);

        $purchaseReturn->calculateTotals();

        $response = $this->actingAs($this->manager)
            ->postJson("/api/v1/returns/purchases/{$purchaseReturn->id}/approve");

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // Verify stock decreased (OUT)
        $stock = Stock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->first();

        $this->assertEquals(7, $stock->quantity); // 10 - 3

        // Verify stock movement created
        $this->assertDatabaseHas('stock_movements', [
            'stock_id' => $stock->id,
            'type' => 'out',
            'quantity' => 3,
            'reference_type' => PurchaseReturn::class,
            'reference_id' => $purchaseReturn->id,
        ]);
    }

    public function test_cannot_approve_purchase_return_exceeding_purchase_quantity(): void
    {
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        // Try to return more than purchased (purchased 10, trying to return 15)
        $purchaseReturn->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 15,
            'price' => 500.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/v1/returns/purchases/{$purchaseReturn->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Failed to approve purchase return');
    }

    public function test_cannot_approve_purchase_return_with_insufficient_stock(): void
    {
        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        // Update stock to have only 2 items
        Stock::where('warehouse_id', $this->warehouse->id)
            ->where('product_id', $this->product->id)
            ->update(['quantity' => 2]);

        // Try to return 5 items (more than available stock)
        $purchaseReturn->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 5,
            'price' => 500.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/v1/returns/purchases/{$purchaseReturn->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Failed to approve purchase return');
    }

    public function test_can_delete_draft_purchase_return(): void
    {
        $purchaseReturn = PurchaseReturn::factory()->create([
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/returns/purchases/{$purchaseReturn->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('purchase_returns', [
            'id' => $purchaseReturn->id,
        ]);
    }

    public function test_cannot_delete_approved_purchase_return(): void
    {
        $purchaseReturn = PurchaseReturn::factory()->approved()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/returns/purchases/{$purchaseReturn->id}");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot delete an approved purchase return');
    }

    public function test_can_filter_purchase_returns_by_status(): void
    {
        PurchaseReturn::factory()->draft()->count(2)->create();
        PurchaseReturn::factory()->approved()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/returns/purchases?status=draft');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_purchase_returns_by_reason(): void
    {
        PurchaseReturn::factory()->create(['reason' => 'Defective items']);
        PurchaseReturn::factory()->create(['reason' => 'Wrong specification']);
        PurchaseReturn::factory()->create(['reason' => 'Quality issues']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/returns/purchases?search=Defective');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unauthorized_user_cannot_create_purchase_return(): void
    {
        $staff = User::factory()->create();
        $staffRole = Role::create(['name' => 'staff']);
        $staff->roles()->attach($staffRole);

        $data = [
            'purchase_id' => $this->purchase->id,
            'warehouse_id' => $this->warehouse->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 500.00,
                ],
            ],
        ];

        $response = $this->actingAs($staff)
            ->postJson('/api/v1/returns/purchases', $data);

        $response->assertForbidden();
    }

    public function test_cannot_return_from_purchase_without_goods_receipt(): void
    {
        // Create a new purchase without goods receipt
        $newPurchase = Purchase::factory()->create([
            'status' => 'approved',
        ]);

        PurchaseItem::factory()->create([
            'purchase_id' => $newPurchase->id,
            'product_id' => $this->product->id,
            'qty' => 5,
            'price' => 500.00,
        ]);

        $purchaseReturn = PurchaseReturn::factory()->create([
            'purchase_id' => $newPurchase->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
        ]);

        $purchaseReturn->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 500.00,
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/v1/returns/purchases/{$purchaseReturn->id}/approve");

        $response->assertUnprocessable()
            ->assertJsonPath('message', 'Failed to approve purchase return');
    }
}
