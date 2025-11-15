<?php

declare(strict_types=1);

use App\Models\ArInvoice;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\Ar\ArInvoiceService;
use App\Services\Ar\CustomerBalanceService;

beforeEach(function () {
    $this->balanceService = new CustomerBalanceService();
    $this->invoiceService = new ArInvoiceService($this->balanceService);
});

test('can create invoice from sales order', function () {
    $customer = Customer::factory()->create();
    $salesOrder = SalesOrder::factory()->create([
        'customer_id' => $customer->id,
        'total_amount' => 50000,
    ]);

    $invoice = $this->invoiceService->createFromSalesOrder($salesOrder, [
        'invoice_date' => now()->toDateString(),
        'discount_amount' => 1000,
        'tax_amount' => 3430,
    ]);

    expect($invoice)
        ->toBeInstanceOf(ArInvoice::class)
        ->customer_id->toBe($customer->id)
        ->sales_order_id->toBe($salesOrder->id)
        ->status->toBe('draft')
        ->reference_type->toBe('sales_order')
        ->reference_id->toBe($salesOrder->id);

    $this->assertEqualsWithDelta(50000.0, (float) $invoice->subtotal_amount, 0.01);
    $this->assertEqualsWithDelta(1000.0, (float) $invoice->discount_amount, 0.01);
    $this->assertEqualsWithDelta(3430.0, (float) $invoice->tax_amount, 0.01);
    $this->assertEqualsWithDelta(52430.0, (float) $invoice->grand_total, 0.01);

    $this->assertDatabaseHas('ar_invoices', [
        'id' => $invoice->id,
        'customer_id' => $customer->id,
        'sales_order_id' => $salesOrder->id,
        'status' => 'draft',
    ]);
});

test('can create manual invoice from payload', function () {
    $customer = Customer::factory()->create();

    $data = [
        'customer_id' => $customer->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'subtotal_amount' => 30000,
        'discount_amount' => 500,
        'tax_amount' => 2065,
        'currency' => 'THB',
        'note' => 'Manual invoice',
    ];

    $invoice = $this->invoiceService->createFromPayload($data);

    expect($invoice)
        ->toBeInstanceOf(ArInvoice::class)
        ->customer_id->toBe($customer->id)
        ->status->toBe('draft')
        ->note->toBe('Manual invoice');

    $this->assertEqualsWithDelta(30000.0, (float) $invoice->subtotal_amount, 0.01);
    $this->assertEqualsWithDelta(500.0, (float) $invoice->discount_amount, 0.01);
    $this->assertEqualsWithDelta(2065.0, (float) $invoice->tax_amount, 0.01);
    $this->assertEqualsWithDelta(31565.0, (float) $invoice->grand_total, 0.01);
    $this->assertEqualsWithDelta(31565.0, (float) $invoice->open_amount, 0.01);

    $this->assertDatabaseHas('ar_invoices', [
        'id' => $invoice->id,
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);
});

test('can issue invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
        'grand_total' => 10000,
    ]);

    $issuedInvoice = $this->invoiceService->issue($invoice);

    expect($issuedInvoice)
        ->status->toBe('issued')
        ->issued_at->not->toBeNull();

    $this->assertDatabaseHas('ar_invoices', [
        'id' => $invoice->id,
        'status' => 'issued',
    ]);
});

test('cannot issue invoice that is not draft', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
    ]);

    expect(fn () => $this->invoiceService->issue($invoice))
        ->toThrow(InvalidArgumentException::class, 'Invoice cannot be issued');
});

test('cannot issue invoice with zero total', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
        'grand_total' => 0,
    ]);

    expect(fn () => $this->invoiceService->issue($invoice))
        ->toThrow(InvalidArgumentException::class, 'Invoice cannot be issued');
});

test('can cancel invoice with no payments', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'paid_total' => 0,
    ]);

    $cancelledInvoice = $this->invoiceService->cancel($invoice);

    expect($cancelledInvoice)
        ->status->toBe('cancelled')
        ->cancelled_at->not->toBeNull();

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

    expect(fn () => $this->invoiceService->cancel($invoice))
        ->toThrow(InvalidArgumentException::class, 'Invoice cannot be cancelled');
});

test('can recalculate invoice balance', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 0,
        'open_amount' => 10000,
    ]);

    // Create allocations from posted receipt
    $receipt = \App\Models\ArReceipt::factory()->posted()->create(['customer_id' => $customer->id]);
    \App\Models\ArReceiptAllocation::factory()->create([
        'receipt_id' => $receipt->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 3000,
    ]);

    $this->invoiceService->recalculateBalance($invoice);
    $invoice->refresh();

    $this->assertEqualsWithDelta(3000.0, (float) $invoice->paid_total, 0.01);
    $this->assertEqualsWithDelta(7000.0, (float) $invoice->open_amount, 0.01);
    expect($invoice->status)->toBe('partially_paid');
});

test('recalculate sets status to paid when fully paid', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 0,
        'open_amount' => 10000,
    ]);

    // Create full payment allocation from posted receipt
    $receipt = \App\Models\ArReceipt::factory()->posted()->create(['customer_id' => $customer->id]);
    \App\Models\ArReceiptAllocation::factory()->create([
        'receipt_id' => $receipt->id,
        'invoice_id' => $invoice->id,
        'allocated_amount' => 10000,
    ]);

    $this->invoiceService->recalculateBalance($invoice);
    $invoice->refresh();

    $this->assertEqualsWithDelta(10000.0, (float) $invoice->paid_total, 0.01);
    $this->assertEqualsWithDelta(0.0, (float) $invoice->open_amount, 0.01);
    expect($invoice->status)->toBe('paid');
});

test('generates unique invoice numbers', function () {
    $customer = Customer::factory()->create();
    $salesOrder = SalesOrder::factory()->create(['customer_id' => $customer->id]);

    $invoice1 = $this->invoiceService->createFromSalesOrder($salesOrder);
    $invoice2 = $this->invoiceService->createFromSalesOrder($salesOrder);

    expect($invoice1->invoice_no)
        ->not->toBe($invoice2->invoice_no)
        ->toStartWith('INV-');
});

