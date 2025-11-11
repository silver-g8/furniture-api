<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->product = Product::factory()->create(['status' => 'active', 'price' => 1000]);
    $this->warehouse = Warehouse::factory()->create();

    // Create stock for the product
    Stock::create([
        'warehouse_id' => $this->warehouse->id,
        'product_id' => $this->product->id,
        'quantity' => 100,
    ]);
});

test('can list orders', function () {
    Order::factory()->count(3)->create(['customer_id' => $this->customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/orders');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter orders by status', function () {
    Order::factory()->create(['customer_id' => $this->customer->id, 'status' => 'draft']);
    Order::factory()->create(['customer_id' => $this->customer->id, 'status' => 'confirmed']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/orders?status=draft');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('can create draft order with items', function () {
    $data = [
        'customer_id' => $this->customer->id,
        'discount' => 0,
        'tax' => 0,
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 2,
                'price' => 1000,
                'discount' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $data);

    $response->assertCreated()
        ->assertJson([
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);

    $order = Order::first();
    expect($order->items)->toHaveCount(1);
    expect($order->subtotal)->toBe('2000.00');
    expect($order->grand_total)->toBe('2000.00');
});

test('can update draft order items', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 3,
                'price' => 1000,
                'discount' => 100,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/orders/{$order->id}", $data);

    $response->assertOk();

    $order->refresh();
    expect($order->items)->toHaveCount(1);
    expect($order->subtotal)->toBe('2900.00'); // (1000 * 3) - 100
});

test('cannot update confirmed order', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 1,
                'price' => 1000,
                'discount' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/orders/{$order->id}", $data);

    $response->assertUnprocessable()
        ->assertJson([
            'message' => 'Order cannot be modified. Only draft orders can be updated.',
        ]);
});

test('can confirm draft order', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $order->items()->create([
        'product_id' => $this->product->id,
        'qty' => 2,
        'price' => 1000,
        'discount' => 0,
        'total' => 2000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/confirm");

    $response->assertOk()
        ->assertJson([
            'status' => 'confirmed',
        ]);
});

test('cannot confirm order without items', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/confirm");

    $response->assertUnprocessable();
});

test('can deliver confirmed order and deduct stock', function () {
    // Create draft order first
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $order->items()->create([
        'product_id' => $this->product->id,
        'qty' => 5,
        'price' => 1000,
        'discount' => 0,
        'total' => 5000,
    ]);

    // Confirm the order
    $order->status = 'confirmed';
    $order->save();

    $initialStock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first()->quantity;

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/deliver", [
            'warehouse_id' => $this->warehouse->id,
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'delivered',
        ]);

    $stock = Stock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock->quantity)->toBe($initialStock - 5);
});

test('cannot deliver order with insufficient stock', function () {
    // Create draft order first
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $order->items()->create([
        'product_id' => $this->product->id,
        'qty' => 200, // More than available stock (100)
        'price' => 1000,
        'discount' => 0,
        'total' => 200000,
    ]);

    // Confirm the order
    $order->status = 'confirmed';
    $order->save();

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/deliver", [
            'warehouse_id' => $this->warehouse->id,
        ]);

    $response->assertUnprocessable()
        ->assertJsonFragment([
            'message' => "Insufficient stock for product ID {$this->product->id}. Available: 100, Required: 200",
        ]);
});

test('cannot deliver draft order', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/deliver", [
            'warehouse_id' => $this->warehouse->id,
        ]);

    $response->assertUnprocessable();
});

test('can record payment for order', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 5000,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $response->assertOk();

    $order->refresh();
    expect($order->status)->toBe('paid');
    expect($order->payments)->toHaveCount(1);
    expect($order->payments->first()->amount)->toBe('5000.00');
});

test('can record partial payment', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 2000,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $response->assertOk();

    $order->refresh();
    expect($order->status)->toBe('confirmed'); // Still confirmed, not paid
    expect($order->payments)->toHaveCount(1);
});

test('order becomes paid when total payments equal grand total', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    // First payment
    $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 3000,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $order->refresh();
    expect($order->status)->toBe('confirmed');

    // Second payment
    $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 2000,
            'method' => 'bank_transfer',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $order->refresh();
    expect($order->status)->toBe('paid');
    expect($order->payments)->toHaveCount(2);
});

test('cannot pay draft order', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 1000,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $response->assertUnprocessable();
});

test('calculates totals correctly with discount and tax', function () {
    $data = [
        'customer_id' => $this->customer->id,
        'discount' => 200,
        'tax' => 150,
        'items' => [
            [
                'product_id' => $this->product->id,
                'qty' => 2,
                'price' => 1000,
                'discount' => 100,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', $data);

    $response->assertCreated();

    $order = Order::first();
    expect($order->subtotal)->toBe('1900.00'); // (1000 * 2) - 100
    expect($order->grand_total)->toBe('1850.00'); // 1900 - 200 + 150
});

test('requires authentication to access orders', function () {
    $response = $this->getJson('/api/v1/orders');

    $response->assertUnauthorized();
});

test('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/orders', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id', 'items']);
});

test('can delete draft order', function () {
    $order = Order::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/orders/{$order->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('orders', [
        'id' => $order->id,
    ]);
});

test('cannot delete confirmed order', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/orders/{$order->id}");

    $response->assertUnprocessable();
});
