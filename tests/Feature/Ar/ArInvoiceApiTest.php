<?php

declare(strict_types=1);

use App\Models\ArInvoice;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('can list invoices', function () {
    $customer = Customer::factory()->create();
    ArInvoice::factory()->count(3)->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/invoices');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer_id',
                    'invoice_no',
                    'invoice_date',
                    'grand_total',
                    'status',
                ],
            ],
        ]);
});

test('can filter invoices by customer', function () {
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    ArInvoice::factory()->count(2)->create(['customer_id' => $customer1->id]);
    ArInvoice::factory()->count(1)->create(['customer_id' => $customer2->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/ar/invoices?customer_id={$customer1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter invoices by status', function () {
    $customer = Customer::factory()->create();
    ArInvoice::factory()->issued()->count(2)->create(['customer_id' => $customer->id]);
    ArInvoice::factory()->paid()->count(1)->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/invoices?status=issued');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter overdue invoices', function () {
    $customer = Customer::factory()->create();
    ArInvoice::factory()->overdue()->count(2)->create(['customer_id' => $customer->id]);
    ArInvoice::factory()->issued()->count(1)->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/invoices?overdue_only=true');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can create invoice from payload', function () {
    $customer = Customer::factory()->create();

    $data = [
        'customer_id' => $customer->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'subtotal_amount' => 30000,
        'discount_amount' => 1000,
        'tax_amount' => 2030,
        'note' => 'Test invoice',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/ar/invoices', $data);

    $response->assertCreated()
        ->assertJsonStructure([
            'id',
            'customer_id',
            'invoice_no',
            'invoice_date',
            'grand_total',
            'status',
        ]);

    $this->assertDatabaseHas('ar_invoices', [
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);
});

test('can create invoice from sales order', function () {
    $customer = Customer::factory()->create();
    $salesOrder = SalesOrder::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 50000,
    ]);

    $data = [
        'customer_id' => $customer->id,
        'sales_order_id' => $salesOrder->id,
        'invoice_date' => now()->toDateString(),
        'discount_amount' => 1000,
        'tax_amount' => 3430,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/ar/invoices', $data);

    $response->assertCreated()
        ->assertJson([
            'customer_id' => $customer->id,
            'sales_order_id' => $salesOrder->id,
            'status' => 'draft',
        ]);

    $this->assertDatabaseHas('ar_invoices', [
        'customer_id' => $customer->id,
        'sales_order_id' => $salesOrder->id,
    ]);
});

test('can show invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/ar/invoices/{$invoice->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $invoice->id,
            'invoice_no' => $invoice->invoice_no,
        ]);
});

test('can update draft invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    $data = [
        'subtotal_amount' => 40000,
        'discount_amount' => 2000,
        'tax_amount' => 2660,
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/ar/invoices/{$invoice->id}", $data);

    $response->assertOk();

    $invoice->refresh();
    $this->assertEqualsWithDelta(40660.0, (float) $invoice->grand_total, 0.01);
});

test('cannot update non-draft invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/ar/invoices/{$invoice->id}", [
            'subtotal_amount' => 50000,
        ]);

    $response->assertForbidden();
});

test('can issue invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/ar/invoices/{$invoice->id}/issue");

    $response->assertOk()
        ->assertJson([
            'id' => $invoice->id,
            'status' => 'issued',
        ]);

    $this->assertDatabaseHas('ar_invoices', [
        'id' => $invoice->id,
        'status' => 'issued',
    ]);
});

test('can cancel invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'paid_total' => 0,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/ar/invoices/{$invoice->id}/cancel");

    $response->assertOk()
        ->assertJson([
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);

    $this->assertDatabaseHas('ar_invoices', [
        'id' => $invoice->id,
        'status' => 'cancelled',
    ]);
});

test('cannot cancel invoice with payments', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->partiallyPaid()->create([
        'customer_id' => $customer->id,
        'paid_total' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/ar/invoices/{$invoice->id}/cancel");

    $response->assertForbidden();
});

test('requires authentication to access invoices', function () {
    $response = $this->getJson('/api/v1/ar/invoices');

    $response->assertUnauthorized();
});

test('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/ar/invoices', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id', 'invoice_date', 'subtotal_amount']);
});

test('can search invoices by invoice number', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-TEST-001',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/invoices?search=TEST-001');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.invoice_no', 'INV-TEST-001');
});

