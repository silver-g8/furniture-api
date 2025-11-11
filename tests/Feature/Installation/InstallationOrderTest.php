<?php

declare(strict_types=1);

use App\Enums\InstallationStatus;
use App\Models\Customer;
use App\Models\InstallationOrder;
use App\Models\SalesOrder;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->salesOrder = SalesOrder::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'paid',
    ]);
});

test('can list installation orders', function () {
    InstallationOrder::factory()->count(3)->create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/installations');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can filter installation orders by status', function () {
    InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Draft,
        'installation_address_override' => '123 Test St',
    ]);

    $salesOrder2 = SalesOrder::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'completed',
    ]);

    InstallationOrder::create([
        'sales_order_id' => $salesOrder2->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Completed,
        'installation_address_override' => '456 Test Ave',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/installations?status=draft');

    $response->assertOk()
        ->assertJsonCount(1, 'data');
});

test('can create installation order from paid sales order', function () {
    $data = [
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'installation_address_override' => '123 Main Street',
        'installation_contact_name' => 'John Doe',
        'installation_contact_phone' => '0812345678',
        'notes' => 'Please call before arrival',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/installations', $data);

    $response->assertCreated()
        ->assertJson([
            'sales_order_id' => $this->salesOrder->id,
            'customer_id' => $this->customer->id,
            'status' => 'draft',
        ]);
});

test('cannot create installation order from unpaid sales order', function () {
    $unpaidSalesOrder = SalesOrder::factory()->create([
        'customer_id' => $this->customer->id,
        'status' => 'draft',
    ]);

    $data = [
        'sales_order_id' => $unpaidSalesOrder->id,
        'customer_id' => $this->customer->id,
        'installation_address_override' => '123 Main Street',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/installations', $data);

    $response->assertUnprocessable()
        ->assertJsonFragment([
            'message' => 'Sales order must be in paid or completed status to create installation order.',
        ]);
});

test('can view installation order details', function () {
    $installation = InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Draft,
        'installation_address_override' => '123 Test St',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/installations/{$installation->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $installation->id,
            'status' => 'draft',
        ]);
});

test('can update installation order status', function () {
    $installation = InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Draft,
        'installation_address_override' => '123 Test St',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/installations/{$installation->id}/status", [
            'status' => 'scheduled',
            'notes' => 'Scheduled for next week',
        ]);

    $response->assertOk()
        ->assertJson([
            'status' => 'scheduled',
        ]);

    $installation->refresh();
    expect($installation->status)->toBe(InstallationStatus::Scheduled);
});

test('cannot transition to invalid status', function () {
    $installation = InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Draft,
        'installation_address_override' => '123 Test St',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/installations/{$installation->id}/status", [
            'status' => 'completed', // Cannot go from draft to completed
        ]);

    $response->assertUnprocessable();
});

test('cannot update completed installation order', function () {
    $installation = InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Completed,
        'installation_address_override' => '123 Test St',
    ]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/installations/{$installation->id}", [
            'notes' => 'Trying to update',
        ]);

    $response->assertUnprocessable()
        ->assertJsonFragment([
            'message' => 'Cannot update completed installation order.',
        ]);
});

test('can soft delete installation order', function () {
    $installation = InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Draft,
        'installation_address_override' => '123 Test St',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/installations/{$installation->id}", [
            'deletion_reason' => 'Customer cancelled',
        ]);

    $response->assertNoContent();

    $this->assertSoftDeleted('installation_orders', [
        'id' => $installation->id,
    ]);
});

test('cannot delete completed installation order', function () {
    $installation = InstallationOrder::create([
        'sales_order_id' => $this->salesOrder->id,
        'customer_id' => $this->customer->id,
        'status' => InstallationStatus::Completed,
        'installation_address_override' => '123 Test St',
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/installations/{$installation->id}", [
            'deletion_reason' => 'Trying to delete',
        ]);

    $response->assertUnprocessable();
});

test('requires authentication to access installations', function () {
    $response = $this->getJson('/api/v1/installations');

    $response->assertUnauthorized();
});

test('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/installations', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sales_order_id', 'customer_id']);
});

test('requires either address_id or address_override', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/installations', [
            'sales_order_id' => $this->salesOrder->id,
            'customer_id' => $this->customer->id,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['installation_address_id', 'installation_address_override']);
});
