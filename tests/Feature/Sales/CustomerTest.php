<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('can list customers', function () {
    Customer::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/customers');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter active customers', function () {
    Customer::factory()->count(2)->create(['is_active' => true]);
    Customer::factory()->count(1)->create(['is_active' => false]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/customers?is_active=1');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can show a customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/customers/{$customer->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'email' => $customer->email,
        ]);
});

test('can create a customer', function () {
    $data = [
        'code' => 'CUST-001',
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '0812345678',
        'address' => '123 Test St',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/customers', $data);

    $response->assertCreated()
        ->assertJson($data);

    $this->assertDatabaseHas('customers', $data);
});

test('can update a customer', function () {
    $customer = Customer::factory()->create();

    $data = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/customers/{$customer->id}", $data);

    $response->assertOk()
        ->assertJson($data);

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('can delete a customer', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/customers/{$customer->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('customers', [
        'id' => $customer->id,
    ]);
});

test('cannot create customer with duplicate code', function () {
    $customer = Customer::factory()->create(['code' => 'CUST-001']);

    $data = [
        'code' => 'CUST-001',
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '0812345678',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/customers', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

test('cannot create customer with duplicate email', function () {
    $customer = Customer::factory()->create(['email' => 'test@example.com']);

    $data = [
        'code' => 'CUST-002',
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '0812345678',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/customers', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('requires authentication to access customers', function () {
    $response = $this->getJson('/api/v1/customers');

    $response->assertUnauthorized();
});

test('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/customers', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'name', 'email', 'phone']);
});

test('customer includes orders relationship', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/customers/{$customer->id}");

    $response->assertOk()
        ->assertJsonStructure([
            'id',
            'code',
            'name',
            'email',
            'orders',
        ]);
});
