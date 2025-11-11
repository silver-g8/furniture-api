<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
});

test('can list payments', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    Payment::factory()->count(3)->create(['order_id' => $order->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/payments');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter payments by order', function () {
    $order1 = Order::factory()->confirmed()->create(['customer_id' => $this->customer->id]);
    $order2 = Order::factory()->confirmed()->create(['customer_id' => $this->customer->id]);

    Payment::factory()->count(2)->create(['order_id' => $order1->id]);
    Payment::factory()->count(1)->create(['order_id' => $order2->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/payments?order_id={$order1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter payments by method', function () {
    $order = Order::factory()->confirmed()->create(['customer_id' => $this->customer->id]);

    Payment::factory()->create(['order_id' => $order->id, 'method' => 'cash']);
    Payment::factory()->create(['order_id' => $order->id, 'method' => 'credit_card']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/payments?method=cash');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('can show a payment', function () {
    $order = Order::factory()->confirmed()->create(['customer_id' => $this->customer->id]);
    $payment = Payment::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $payment->id,
            'order_id' => $order->id,
            'amount' => $payment->amount,
            'method' => $payment->method,
        ]);
});

test('payment includes order and customer relationship', function () {
    $order = Order::factory()->confirmed()->create(['customer_id' => $this->customer->id]);
    $payment = Payment::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/payments/{$payment->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'id',
            'order_id',
            'amount',
            'method',
            'order' => [
                'id',
                'customer_id',
                'customer' => [
                    'id',
                    'name',
                ],
            ],
        ]);
});

test('requires authentication to access payments', function () {
    $response = $this->getJson('/api/v1/payments');

    $response->assertUnauthorized();
});

test('payment validates amount is positive', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 0,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('payment validates required fields', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount', 'method', 'paid_at']);
});

test('tracks total paid amount correctly', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 5000,
    ]);

    // First payment
    $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 2000,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    // Second payment
    $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 1500,
            'method' => 'credit_card',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $order->refresh();
    expect($order->total_paid)->toBe(3500.0);
    expect($order->remaining_amount)->toBe(1500.0);
    expect($order->status)->toBe('confirmed'); // Not fully paid yet
});

test('order status changes to paid when fully paid', function () {
    $order = Order::factory()->confirmed()->create([
        'customer_id' => $this->customer->id,
        'grand_total' => 3000,
    ]);

    $this->actingAs($this->user)
        ->postJson("/api/v1/orders/{$order->id}/pay", [
            'amount' => 3000,
            'method' => 'cash',
            'paid_at' => now()->toDateTimeString(),
        ]);

    $order->refresh();
    expect($order->status)->toBe('paid');
    expect($order->isFullyPaid())->toBeTrue();
});
