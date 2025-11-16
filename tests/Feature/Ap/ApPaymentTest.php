<?php

declare(strict_types=1);

use App\Models\ApInvoice;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Supplier;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

describe('AP Payment API', function () {
    test('can list ap payments', function () {
        $payments = ApPayment::factory(3)->create();

        $response = getJson('/api/v1/ap/payments');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'supplier_id',
                        'payment_no',
                        'payment_date',
                        'total_amount',
                        'status',
                    ],
                ],
                'meta',
            ]);
    });

    test('can filter payments by supplier', function () {
        $supplier = Supplier::factory()->create();
        ApPayment::factory(2)->create(['supplier_id' => $supplier->id]);
        ApPayment::factory(3)->create(); // Other suppliers

        $response = getJson("/api/v1/ap/payments?supplier_id={$supplier->id}");

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('can filter payments by status', function () {
        ApPayment::factory(2)->posted()->create();
        ApPayment::factory(3)->create(['status' => 'draft']);

        $response = getJson('/api/v1/ap/payments?status=posted');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('can create an ap payment with allocations', function () {
        $supplier = Supplier::factory()->create();
        $invoice1 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 10000,
        ]);
        $invoice2 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 5000,
        ]);

        $data = [
            'supplier_id' => $supplier->id,
            'payment_no' => 'TEST-PAY-001',
            'payment_date' => now()->toDateString(),
            'total_amount' => 12000,
            'payment_method' => 'transfer',
            'allocations' => [
                [
                    'invoice_id' => $invoice1->id,
                    'allocated_amount' => 10000,
                ],
                [
                    'invoice_id' => $invoice2->id,
                    'allocated_amount' => 2000,
                ],
            ],
        ];

        $response = postJson('/api/v1/ap/payments', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'payment_no',
                    'total_amount',
                    'status',
                ],
                'message',
            ]);

        assertDatabaseHas('ap_payments', [
            'payment_no' => 'TEST-PAY-001',
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        assertDatabaseHas('ap_payment_allocations', [
            'invoice_id' => $invoice1->id,
            'allocated_amount' => 10000,
        ]);

        assertDatabaseHas('ap_payment_allocations', [
            'invoice_id' => $invoice2->id,
            'allocated_amount' => 2000,
        ]);
    });

    test('can view a single ap payment', function () {
        $payment = ApPayment::factory()->create();

        $response = getJson("/api/v1/ap/payments/{$payment->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $payment->id,
                    'payment_no' => $payment->payment_no,
                ],
            ]);
    });

    test('can update a draft ap payment', function () {
        $payment = ApPayment::factory()->create(['status' => 'draft']);

        $data = [
            'total_amount' => 15000,
            'payment_method' => 'cash',
            'note' => 'Updated payment',
        ];

        $response = putJson("/api/v1/ap/payments/{$payment->id}", $data);

        $response->assertOk()
            ->assertJson([
                'message' => 'AP Payment updated successfully',
            ]);

        assertDatabaseHas('ap_payments', [
            'id' => $payment->id,
            'total_amount' => 15000,
            'payment_method' => 'cash',
        ]);
    });

    test('cannot update a posted ap payment', function () {
        $payment = ApPayment::factory()->posted()->create();

        $data = [
            'total_amount' => 15000,
        ];

        $response = putJson("/api/v1/ap/payments/{$payment->id}", $data);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Cannot update a posted payment',
            ]);
    });

    test('can post a draft payment with allocations', function () {
        $supplier = Supplier::factory()->create();
        $invoice = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'grand_total' => 10000,
            'open_amount' => 10000,
        ]);

        $payment = ApPayment::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'total_amount' => 10000,
        ]);

        ApPaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 10000,
        ]);

        $response = postJson("/api/v1/ap/payments/{$payment->id}/post");

        $response->assertOk()
            ->assertJson([
                'message' => 'AP Payment posted successfully',
            ]);

        assertDatabaseHas('ap_payments', [
            'id' => $payment->id,
            'status' => 'posted',
        ]);

        expect($payment->fresh()->posted_at)->not->toBeNull();
    });

    test('cannot post a payment without allocations', function () {
        $payment = ApPayment::factory()->create(['status' => 'draft']);

        $response = postJson("/api/v1/ap/payments/{$payment->id}/post");

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Payment cannot be posted. Ensure it has allocations.',
            ]);
    });

    test('can cancel a posted payment', function () {
        $supplier = Supplier::factory()->create();
        $payment = ApPayment::factory()->posted()->create([
            'supplier_id' => $supplier->id,
        ]);

        $response = postJson("/api/v1/ap/payments/{$payment->id}/cancel");

        $response->assertOk()
            ->assertJson([
                'message' => 'AP Payment cancelled successfully',
            ]);

        assertDatabaseHas('ap_payments', [
            'id' => $payment->id,
            'status' => 'cancelled',
        ]);
    });

    test('validates required fields when creating payment', function () {
        $response = postJson('/api/v1/ap/payments', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_id', 'payment_date', 'total_amount']);
    });

    test('validates allocation amount does not exceed payment amount', function () {
        $supplier = Supplier::factory()->create();
        $invoice = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 10000,
        ]);

        $data = [
            'supplier_id' => $supplier->id,
            'payment_date' => now()->toDateString(),
            'total_amount' => 5000,
            'allocations' => [
                [
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => 10000, // Exceeds payment amount
                ],
            ],
        ];

        $response = postJson('/api/v1/ap/payments', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['allocations']);
    });

    test('can auto-allocate payment to unpaid invoices', function () {
        $supplier = Supplier::factory()->create();

        // Create 3 unpaid invoices
        $invoice1 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->subDays(60),
            'due_date' => now()->subDays(30),
            'open_amount' => 5000,
        ]);

        $invoice2 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->subDays(45),
            'due_date' => now()->subDays(15),
            'open_amount' => 3000,
        ]);

        $invoice3 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'invoice_date' => now()->subDays(20),
            'due_date' => now()->addDays(10),
            'open_amount' => 2000,
        ]);

        $payment = ApPayment::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'total_amount' => 7000,
        ]);

        $response = postJson("/api/v1/ap/payments/{$payment->id}/auto-allocate");

        $response->assertOk()
            ->assertJson([
                'message' => 'Payment auto-allocated successfully',
            ]);

        // Should allocate to oldest invoices first
        assertDatabaseHas('ap_payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice1->id,
            'allocated_amount' => 5000,
        ]);

        assertDatabaseHas('ap_payment_allocations', [
            'payment_id' => $payment->id,
            'invoice_id' => $invoice2->id,
            'allocated_amount' => 2000,
        ]);
    });

    test('can get supplier payment summary', function () {
        $supplier = Supplier::factory()->create();

        ApPayment::factory(2)->posted()->create([
            'supplier_id' => $supplier->id,
            'total_amount' => 10000,
        ]);

        ApInvoice::factory(2)->issued()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 5000,
        ]);

        ApInvoice::factory()->overdue()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 2000,
        ]);

        $response = getJson("/api/v1/ap/payments/supplier/summary?supplier_id={$supplier->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'total_paid',
                    'total_outstanding',
                    'overdue_amount',
                ],
            ]);

        $data = $response->json('data');
        expect($data['total_paid'])->toEqual(20000.0);
        expect($data['total_outstanding'])->toEqual(12000.0);
        expect($data['overdue_amount'])->toEqual(2000.0);
    });
});

describe('AP Payment Business Logic', function () {
    test('posting payment updates related invoices', function () {
        $supplier = Supplier::factory()->create();
        $invoice = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'grand_total' => 10000,
            'open_amount' => 10000,
            'paid_total' => 0,
        ]);

        $payment = ApPayment::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'total_amount' => 10000,
        ]);

        ApPaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 10000,
        ]);

        postJson("/api/v1/ap/payments/{$payment->id}/post");

        $invoice->refresh();

        expect($invoice->paid_total)->toBe(10000.0);
        expect($invoice->open_amount)->toBe(0.0);
        expect($invoice->status)->toBe('paid');
    });

    test('partial payment updates invoice status', function () {
        $supplier = Supplier::factory()->create();
        $invoice = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'grand_total' => 10000,
            'open_amount' => 10000,
            'paid_total' => 0,
        ]);

        $payment = ApPayment::factory()->create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'total_amount' => 5000,
        ]);

        ApPaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 5000,
        ]);

        postJson("/api/v1/ap/payments/{$payment->id}/post");

        $invoice->refresh();

        expect($invoice->paid_total)->toBe(5000.0);
        expect($invoice->open_amount)->toBe(5000.0);
        expect($invoice->status)->toBe('partially_paid');
    });

    test('calculates total allocated amount correctly', function () {
        $supplier = Supplier::factory()->create();
        $payment = ApPayment::factory()->create([
            'supplier_id' => $supplier->id,
            'total_amount' => 15000,
        ]);

        $invoice1 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 10000,
        ]);

        $invoice2 = ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'open_amount' => 5000,
        ]);

        ApPaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice1->id,
            'allocated_amount' => 10000,
        ]);

        ApPaymentAllocation::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice2->id,
            'allocated_amount' => 3000,
        ]);

        expect($payment->total_allocated)->toBe(13000.0);
        expect($payment->unallocated_amount)->toBe(2000.0);
    });
});
