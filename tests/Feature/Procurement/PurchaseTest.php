<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $staffRole = Role::create(['name' => 'staff']);

        // Create users with roles
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->manager = User::factory()->create();
        $this->manager->roles()->attach($managerRole);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($staffRole);
    }

    public function test_admin_can_list_purchases(): void
    {
        Purchase::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/purchases');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'supplier_id', 'status', 'grand_total'],
                ],
                'meta',
            ]);
    }

    public function test_staff_can_list_purchases(): void
    {
        Purchase::factory()->count(3)->create();

        $response = $this->actingAs($this->staff)
            ->getJson('/api/v1/purchases');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_purchase_with_items(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'discount' => 100,
            'tax' => 50,
            'notes' => 'Test purchase',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'price' => 500,
                    'discount' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/purchases', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['status' => 'draft']);

        $this->assertDatabaseHas('purchases', [
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'product_id' => $product->id,
            'qty' => 10,
        ]);
    }

    public function test_manager_can_create_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->manager)
            ->postJson('/api/v1/purchases', $data);

        $response->assertStatus(201);
    }

    public function test_staff_cannot_create_purchase(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 5,
                    'price' => 1000,
                ],
            ],
        ];

        $response = $this->actingAs($this->staff)
            ->postJson('/api/v1/purchases', $data);

        $response->assertStatus(403);
    }

    public function test_purchase_requires_supplier_and_items(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/purchases', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['supplier_id', 'items']);
    }

    public function test_purchase_items_require_product_qty_and_price(): void
    {
        $supplier = Supplier::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'items' => [
                [], // Empty item
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/purchases', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'items.0.product_id',
                'items.0.qty',
                'items.0.price',
            ]);
    }

    public function test_admin_can_view_purchase(): void
    {
        $purchase = Purchase::factory()->create();
        PurchaseItem::factory()->create(['purchase_id' => $purchase->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/purchases/{$purchase->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'supplier',
                    'items',
                    'status',
                ],
            ]);
    }

    public function test_admin_can_update_draft_purchase(): void
    {
        $purchase = Purchase::factory()->draft()->create();
        $product = Product::factory()->create();

        $data = [
            'notes' => 'Updated notes',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 20,
                    'price' => 600,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/purchases/{$purchase->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['notes' => 'Updated notes']);
    }

    public function test_cannot_update_approved_purchase(): void
    {
        $purchase = Purchase::factory()->approved()->create();

        $data = ['notes' => 'Try to update'];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/v1/purchases/{$purchase->id}", $data);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Cannot edit an approved purchase']);
    }

    public function test_admin_can_approve_draft_purchase_with_items(): void
    {
        $purchase = Purchase::factory()->draft()->create();
        PurchaseItem::factory()->create(['purchase_id' => $purchase->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/purchases/{$purchase->id}/approve");

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'approved',
        ]);
    }

    public function test_cannot_approve_purchase_without_items(): void
    {
        $purchase = Purchase::factory()->draft()->create();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/purchases/{$purchase->id}/approve");

        $response->assertStatus(422);
    }

    public function test_cannot_approve_already_approved_purchase(): void
    {
        $purchase = Purchase::factory()->approved()->create();
        PurchaseItem::factory()->create(['purchase_id' => $purchase->id]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/v1/purchases/{$purchase->id}/approve");

        $response->assertStatus(422);
    }

    public function test_admin_can_delete_draft_purchase(): void
    {
        $purchase = Purchase::factory()->draft()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/purchases/{$purchase->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('purchases', ['id' => $purchase->id]);
    }

    public function test_cannot_delete_approved_purchase(): void
    {
        $purchase = Purchase::factory()->approved()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/v1/purchases/{$purchase->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_can_filter_purchases_by_supplier(): void
    {
        $supplier1 = Supplier::factory()->create();
        $supplier2 = Supplier::factory()->create();

        Purchase::factory()->create(['supplier_id' => $supplier1->id]);
        Purchase::factory()->create(['supplier_id' => $supplier2->id]);

        $response = $this->actingAs($this->admin)
            ->getJson("/api/v1/purchases?supplier_id={$supplier1->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($supplier1->id, $data[0]['supplier_id']);
    }

    public function test_can_filter_purchases_by_status(): void
    {
        Purchase::factory()->draft()->create();
        Purchase::factory()->approved()->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/v1/purchases?status=draft');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('draft', $data[0]['status']);
    }

    public function test_purchase_totals_are_calculated_correctly(): void
    {
        $supplier = Supplier::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'discount' => 100,
            'tax' => 70,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 10,
                    'price' => 100, // 10 * 100 = 1000
                    'discount' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/v1/purchases', $data);

        $response->assertStatus(201);

        $purchase = Purchase::first();
        $this->assertEquals(950, $purchase->subtotal); // 1000 - 50
        $this->assertEquals(920, $purchase->grand_total); // 950 - 100 + 70
    }
}
