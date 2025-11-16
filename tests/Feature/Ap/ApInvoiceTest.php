<?php

declare(strict_types=1);

use App\Models\ApInvoice;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

describe('AP Invoice API', function () {
    test('can list ap invoices', function () {
        $invoices = ApInvoice::factory(3)->create();

        $response = getJson('/api/v1/ap/invoices');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'supplier_id',
                        'invoice_no',
                        'invoice_date',
                        'due_date',
                        'grand_total',
                        'open_amount',
                        'status',
                    ],
                ],
                'meta',
            ]);
    });

    test('can filter invoices by supplier', function () {
        $supplier = Supplier::factory()->create();
        ApInvoice::factory(2)->create(['supplier_id' => $supplier->id]);
        ApInvoice::factory(3)->create(); // Other suppliers

        $response = getJson("/api/v1/ap/invoices?supplier_id={$supplier->id}");

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('can filter invoices by status', function () {
        ApInvoice::factory(2)->issued()->create();
        ApInvoice::factory(3)->create(['status' => 'draft']);

        $response = getJson('/api/v1/ap/invoices?status=issued');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
    });

    test('can filter overdue invoices', function () {
        ApInvoice::factory(2)->overdue()->create();
        ApInvoice::factory(3)->issued()->create();

        $response = getJson('/api/v1/ap/invoices?overdue=1');

        $response->assertOk();
        $data = $response->json('data');
        // Should have at least 2 overdue invoices
        expect(count($data))->toBeGreaterThanOrEqual(2);
        // All returned invoices should be overdue
        foreach ($data as $invoice) {
            expect($invoice['is_overdue'])->toBeTrue();
        }
    });

    test('can create an ap invoice', function () {
        $supplier = Supplier::factory()->create();

        $data = [
            'supplier_id' => $supplier->id,
            'invoice_no' => 'TEST-INV-001',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal_amount' => 10000,
            'discount_amount' => 500,
            'tax_amount' => 665,
            'note' => 'Test invoice',
        ];

        $response = postJson('/api/v1/ap/invoices', $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'invoice_no',
                    'grand_total',
                    'status',
                ],
                'message',
            ]);

        assertDatabaseHas('ap_invoices', [
            'invoice_no' => 'TEST-INV-001',
            'supplier_id' => $supplier->id,
            'grand_total' => 10165, // 10000 - 500 + 665
            'status' => 'draft',
        ]);
    });

    test('can view a single ap invoice', function () {
        $invoice = ApInvoice::factory()->create();

        $response = getJson("/api/v1/ap/invoices/{$invoice->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                ],
            ]);
    });

    test('can update a draft ap invoice', function () {
        $invoice = ApInvoice::factory()->create(['status' => 'draft']);

        $data = [
            'subtotal_amount' => 15000,
            'discount_amount' => 0,
            'tax_amount' => 1050,
            'note' => 'Updated note',
        ];

        $response = putJson("/api/v1/ap/invoices/{$invoice->id}", $data);

        $response->assertOk()
            ->assertJson([
                'message' => 'AP Invoice updated successfully',
            ]);

        assertDatabaseHas('ap_invoices', [
            'id' => $invoice->id,
            'grand_total' => 16050, // 15000 + 1050
        ]);
    });

    test('cannot update an issued ap invoice', function () {
        $invoice = ApInvoice::factory()->issued()->create();

        $data = [
            'subtotal_amount' => 15000,
        ];

        $response = putJson("/api/v1/ap/invoices/{$invoice->id}", $data);

        $response->assertUnprocessable()
            ->assertJson([
                'message' => 'Cannot update an issued or paid invoice',
            ]);
    });

    test('can issue a draft invoice', function () {
        $invoice = ApInvoice::factory()->create([
            'status' => 'draft',
            'grand_total' => 10000,
        ]);

        $response = postJson("/api/v1/ap/invoices/{$invoice->id}/issue");

        $response->assertOk()
            ->assertJson([
                'message' => 'AP Invoice issued successfully',
            ]);

        assertDatabaseHas('ap_invoices', [
            'id' => $invoice->id,
            'status' => 'issued',
        ]);

        expect($invoice->fresh()->issued_at)->not->toBeNull();
    });

    test('cannot issue an already issued invoice', function () {
        $invoice = ApInvoice::factory()->issued()->create();

        $response = postJson("/api/v1/ap/invoices/{$invoice->id}/issue");

        $response->assertUnprocessable();
    });

    test('can cancel a draft invoice', function () {
        $invoice = ApInvoice::factory()->create(['status' => 'draft']);

        $response = postJson("/api/v1/ap/invoices/{$invoice->id}/cancel");

        $response->assertOk()
            ->assertJson([
                'message' => 'AP Invoice cancelled successfully',
            ]);

        assertDatabaseHas('ap_invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);
    });

    test('cannot cancel an invoice with payments', function () {
        $invoice = ApInvoice::factory()->partiallyPaid()->create();

        $response = postJson("/api/v1/ap/invoices/{$invoice->id}/cancel");

        $response->assertUnprocessable();
    });

    test('validates required fields when creating invoice', function () {
        $response = postJson('/api/v1/ap/invoices', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['supplier_id', 'invoice_no', 'invoice_date', 'subtotal_amount']);
    });

    test('validates unique invoice number', function () {
        $existing = ApInvoice::factory()->create(['invoice_no' => 'DUPLICATE-001']);

        $data = [
            'supplier_id' => Supplier::factory()->create()->id,
            'invoice_no' => 'DUPLICATE-001',
            'invoice_date' => now()->toDateString(),
            'subtotal_amount' => 1000,
        ];

        $response = postJson('/api/v1/ap/invoices', $data);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['invoice_no']);
    });

    test('can get aging report for supplier', function () {
        $supplier = Supplier::factory()->create();

        // Create various invoices
        ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'due_date' => now()->subDays(5),
            'open_amount' => 1000,
        ]);

        ApInvoice::factory()->issued()->create([
            'supplier_id' => $supplier->id,
            'due_date' => now()->subDays(45),
            'open_amount' => 2000,
        ]);

        $response = getJson("/api/v1/ap/invoices/aging/report?supplier_id={$supplier->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'current',
                    '1_30',
                    '31_60',
                    '61_90',
                    'over_90',
                ],
            ]);
    });
});

describe('AP Invoice Business Logic', function () {
    test('calculates due date from supplier payment terms', function () {
        $supplier = Supplier::factory()->create([
            'credit_days' => 45,
        ]);

        $data = [
            'supplier_id' => $supplier->id,
            'invoice_no' => 'AUTO-DUE-001',
            'invoice_date' => '2025-01-01',
            'subtotal_amount' => 10000,
        ];

        $response = postJson('/api/v1/ap/invoices', $data);

        $response->assertCreated();

        $invoice = ApInvoice::where('invoice_no', 'AUTO-DUE-001')->first();
        expect($invoice->due_date->toDateString())->toBe('2025-02-15');
    });

    test('invoice is marked as overdue when past due date', function () {
        $invoice = ApInvoice::factory()->create([
            'status' => 'issued',
            'due_date' => now()->subDays(10),
            'open_amount' => 5000,
        ]);

        expect($invoice->is_overdue)->toBeTrue();
    });

    test('invoice is not overdue when within due date', function () {
        $invoice = ApInvoice::factory()->create([
            'status' => 'issued',
            'due_date' => now()->addDays(10),
            'open_amount' => 5000,
        ]);

        expect($invoice->is_overdue)->toBeFalse();
    });

    test('paid invoice is not overdue even if past due date', function () {
        $invoice = ApInvoice::factory()->create([
            'status' => 'paid',
            'due_date' => now()->subDays(10),
            'open_amount' => 0,
        ]);

        expect($invoice->is_overdue)->toBeFalse();
    });
});
