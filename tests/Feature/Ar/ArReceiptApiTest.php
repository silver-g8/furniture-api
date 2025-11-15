<?php

declare(strict_types=1);

use App\Models\ArInvoice;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Customer;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('can list receipts', function () {
    $customer = Customer::factory()->create();
    ArReceipt::factory()->count(3)->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/receipts');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'customer_id',
                    'receipt_no',
                    'receipt_date',
                    'total_amount',
                    'status',
                ],
            ],
        ]);
});

test('can filter receipts by customer', function () {
    $customer1 = Customer::factory()->create();
    $customer2 = Customer::factory()->create();

    ArReceipt::factory()->count(2)->create(['customer_id' => $customer1->id]);
    ArReceipt::factory()->count(1)->create(['customer_id' => $customer2->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/ar/receipts?customer_id={$customer1->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can filter receipts by status', function () {
    $customer = Customer::factory()->create();
    ArReceipt::factory()->count(2)->create(['customer_id' => $customer->id, 'status' => 'draft']);
    ArReceipt::factory()->posted()->count(1)->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/receipts?status=draft');

    $response->assertOk()
        ->assertJsonCount(2, 'data');
});

test('can create receipt with allocations', function () {
    $customer = Customer::factory()->create();
    $invoice1 = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'open_amount' => 10000,
    ]);
    $invoice2 = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 5000,
        'open_amount' => 5000,
    ]);

    $data = [
        'customer_id' => $customer->id,
        'receipt_date' => now()->toDateString(),
        'total_amount' => 12000,
        'payment_method' => 'transfer',
        'reference_no' => 'TRF-001',
        'note' => 'Test receipt',
        'allocations' => [
            ['invoice_id' => $invoice1->id, 'allocated_amount' => 8000],
            ['invoice_id' => $invoice2->id, 'allocated_amount' => 4000],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/ar/receipts', $data);

    $response->assertCreated()
        ->assertJsonStructure([
            'id',
            'customer_id',
            'receipt_no',
            'total_amount',
            'status',
            'allocations',
        ]);

    $this->assertDatabaseHas('ar_receipts', [
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    $receiptId = $response->json('id');
    $this->assertDatabaseHas('ar_receipt_allocations', [
        'receipt_id' => $receiptId,
        'invoice_id' => $invoice1->id,
        'allocated_amount' => 8000,
    ]);
});

test('cannot create receipt with allocations exceeding total', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create(['customer_id' => $customer->id]);

    $data = [
        'customer_id' => $customer->id,
        'receipt_date' => now()->toDateString(),
        'total_amount' => 10000,
        'allocations' => [
            ['invoice_id' => $invoice->id, 'allocated_amount' => 15000],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/ar/receipts', $data);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['allocations']);
});

test('can show receipt', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/ar/receipts/{$receipt->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $receipt->id,
            'receipt_no' => $receipt->receipt_no,
        ]);
});

test('can update draft receipt', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    $data = [
        'total_amount' => 15000,
        'payment_method' => 'cash',
        'note' => 'Updated note',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/ar/receipts/{$receipt->id}", $data);

    $response->assertOk();

    $receipt->refresh();
    $this->assertEqualsWithDelta(15000.0, (float) $receipt->total_amount, 0.01);
});

test('cannot update non-draft receipt', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->posted()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/ar/receipts/{$receipt->id}", [
            'total_amount' => 20000,
        ]);

    $response->assertForbidden();
});

test('can post receipt', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'open_amount' => 10000,
    ]);

    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 5000,
        'status' => 'draft',
    ]);

    ArReceiptAllocation::factory()->create([
        'receipt_id' => $receipt->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/ar/receipts/{$receipt->id}/post");

    $response->assertOk()
        ->assertJson([
            'id' => $receipt->id,
            'status' => 'posted',
        ]);

    $this->assertDatabaseHas('ar_receipts', [
        'id' => $receipt->id,
        'status' => 'posted',
    ]);

    $invoice->refresh();
    $this->assertEqualsWithDelta(5000.0, (float) $invoice->paid_total, 0.01);
    expect($invoice->status)->toBe('partially_paid');
});

test('can cancel receipt', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->partiallyPaid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 5000,
        'open_amount' => 5000,
    ]);

    $receipt = ArReceipt::factory()->posted()->create([
        'customer_id' => $customer->id,
        'total_amount' => 5000,
    ]);

    ArReceiptAllocation::factory()->create([
        'receipt_id' => $receipt->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 5000,
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/ar/receipts/{$receipt->id}/cancel");

    $response->assertOk()
        ->assertJson([
            'id' => $receipt->id,
            'status' => 'cancelled',
        ]);

    $this->assertDatabaseHas('ar_receipts', [
        'id' => $receipt->id,
        'status' => 'cancelled',
    ]);

    $invoice->refresh();
    $this->assertEqualsWithDelta(0.0, (float) $invoice->paid_total, 0.01);
    expect($invoice->status)->toBe('issued');
});

test('cannot cancel non-posted receipt', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/ar/receipts/{$receipt->id}/cancel");

    $response->assertForbidden();
});

test('requires authentication to access receipts', function () {
    $response = $this->getJson('/api/v1/ar/receipts');

    $response->assertUnauthorized();
});

test('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/ar/receipts', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['customer_id', 'receipt_date', 'total_amount', 'allocations']);
});

test('can search receipts by receipt number', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'receipt_no' => 'RCP-TEST-001',
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/ar/receipts?search=TEST-001');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.receipt_no', 'RCP-TEST-001');
});

test('posting receipt updates customer balance', function () {
    $customer = Customer::factory()->create([
        'outstanding_balance' => 10000,
    ]);

    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'open_amount' => 10000,
    ]);

    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 10000,
        'status' => 'draft',
    ]);

    ArReceiptAllocation::factory()->create([
        'receipt_id' => $receipt->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 10000,
    ]);

    $this->actingAs($this->user)
        ->postJson("/api/v1/ar/receipts/{$receipt->id}/post");

    $customer->refresh();
    $this->assertEqualsWithDelta(0.0, (float) $customer->outstanding_balance, 0.01);
});

