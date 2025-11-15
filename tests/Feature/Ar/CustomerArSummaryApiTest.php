<?php

declare(strict_types=1);

use App\Models\ArInvoice;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('can get customer AR summary', function () {
    $customer = Customer::factory()->create();

    ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 0,
        'open_amount' => 10000,
    ]);

    ArInvoice::factory()->partiallyPaid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 20000,
        'paid_total' => 10000,
        'open_amount' => 10000,
    ]);

    ArInvoice::factory()->paid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 15000,
        'paid_total' => 15000,
        'open_amount' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/customers/{$customer->id}/ar-summary");

    $response->assertOk()
        ->assertJsonStructure([
            'customer_id',
            'total_invoiced',
            'total_paid',
            'total_outstanding',
        ])
        ->assertJson([
            'customer_id' => $customer->id,
            'total_invoiced' => 45000.0,
            'total_paid' => 25000.0,
            'total_outstanding' => 20000.0,
        ]);
});

test('returns zero summary for customer with no invoices', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/customers/{$customer->id}/ar-summary");

    $response->assertOk()
        ->assertJson([
            'customer_id' => $customer->id,
            'total_invoiced' => 0.0,
            'total_paid' => 0.0,
            'total_outstanding' => 0.0,
        ]);
});

test('excludes cancelled invoices from summary', function () {
    $customer = Customer::factory()->create();

    ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
    ]);

    ArInvoice::factory()->cancelled()->create([
        'customer_id' => $customer->id,
        'grand_total' => 20000,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/customers/{$customer->id}/ar-summary");

    $response->assertOk();
    
    $data = $response->json();
    $this->assertEqualsWithDelta(10000.0, (float) $data['total_invoiced'], 0.01);
    $this->assertEqualsWithDelta(10000.0, (float) $data['total_outstanding'], 0.01);
});

test('requires authentication to access AR summary', function () {
    $customer = Customer::factory()->create();

    $response = $this->getJson("/api/v1/customers/{$customer->id}/ar-summary");

    $response->assertUnauthorized();
});

test('returns 404 for non-existent customer', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/customers/99999/ar-summary');

    $response->assertNotFound();
});

