<?php

declare(strict_types=1);

use App\Models\ArInvoice;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Customer;
use App\Services\Ar\ArInvoiceService;
use App\Services\Ar\ArReceiptService;
use App\Services\Ar\CustomerBalanceService;

beforeEach(function () {
    $this->balanceService = new CustomerBalanceService();
    $this->invoiceService = new ArInvoiceService($this->balanceService);
    $this->receiptService = new ArReceiptService($this->balanceService, $this->invoiceService);
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

    $receipt = $this->receiptService->createWithAllocations($data);

    expect($receipt)
        ->toBeInstanceOf(ArReceipt::class)
        ->customer_id->toBe($customer->id)
        ->status->toBe('draft')
        ->allocations->toHaveCount(2);

    $this->assertEqualsWithDelta(12000.0, (float) $receipt->total_amount, 0.01);

    $this->assertDatabaseHas('ar_receipts', [
        'id' => $receipt->id,
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    $this->assertDatabaseHas('ar_receipt_allocations', [
        'receipt_id' => $receipt->id,
        'invoice_id' => $invoice1->id,
        'allocated_amount' => 8000,
    ]);
});

test('cannot create receipt with allocations exceeding total amount', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
    ]);

    $data = [
        'customer_id' => $customer->id,
        'receipt_date' => now()->toDateString(),
        'total_amount' => 10000,
        'allocations' => [
            ['invoice_id' => $invoice->id, 'allocated_amount' => 15000],
        ],
    ];

    expect(fn () => $this->receiptService->createWithAllocations($data))
        ->toThrow(InvalidArgumentException::class, 'Total allocated amount');
});

test('can post receipt and update invoice balances', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 0,
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

    $postedReceipt = $this->receiptService->post($receipt);

    expect($postedReceipt)
        ->status->toBe('posted')
        ->posted_at->not->toBeNull();

    $invoice->refresh();
    $this->assertEqualsWithDelta(5000.0, (float) $invoice->paid_total, 0.01);
    $this->assertEqualsWithDelta(5000.0, (float) $invoice->open_amount, 0.01);
    expect($invoice->status)->toBe('partially_paid');

    $this->assertDatabaseHas('ar_receipts', [
        'id' => $receipt->id,
        'status' => 'posted',
    ]);
});

test('posting receipt sets invoice to paid when fully paid', function () {
    $customer = Customer::factory()->create();
    $invoice = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 0,
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

    $this->receiptService->post($receipt);

    $invoice->refresh();
    $this->assertEqualsWithDelta(10000.0, (float) $invoice->paid_total, 0.01);
    $this->assertEqualsWithDelta(0.0, (float) $invoice->open_amount, 0.01);
    expect($invoice->status)->toBe('paid');
});

test('cannot post receipt that is not draft', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->posted()->create([
        'customer_id' => $customer->id,
    ]);

    expect(fn () => $this->receiptService->post($receipt))
        ->toThrow(InvalidArgumentException::class, 'Receipt cannot be posted');
});

test('cannot post receipt without allocations', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    expect(fn () => $this->receiptService->post($receipt))
        ->toThrow(InvalidArgumentException::class, 'Receipt cannot be posted');
});

test('can cancel receipt and rollback allocations', function () {
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

    $cancelledReceipt = $this->receiptService->cancel($receipt);

    expect($cancelledReceipt)
        ->status->toBe('cancelled')
        ->cancelled_at->not->toBeNull();

    $invoice->refresh();
    $this->assertEqualsWithDelta(0.0, (float) $invoice->paid_total, 0.01);
    $this->assertEqualsWithDelta(10000.0, (float) $invoice->open_amount, 0.01);
    expect($invoice->status)->toBe('issued');

    $this->assertDatabaseHas('ar_receipts', [
        'id' => $receipt->id,
        'status' => 'cancelled',
    ]);
});

test('cannot cancel receipt that is not posted', function () {
    $customer = Customer::factory()->create();
    $receipt = ArReceipt::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
    ]);

    expect(fn () => $this->receiptService->cancel($receipt))
        ->toThrow(InvalidArgumentException::class, 'Receipt cannot be cancelled');
});

test('generates unique receipt numbers', function () {
    $customer = Customer::factory()->create();

    $receipt1 = $this->receiptService->createWithAllocations([
        'customer_id' => $customer->id,
        'receipt_date' => now()->toDateString(),
        'total_amount' => 1000,
        'allocations' => [],
    ]);

    $receipt2 = $this->receiptService->createWithAllocations([
        'customer_id' => $customer->id,
        'receipt_date' => now()->toDateString(),
        'total_amount' => 2000,
        'allocations' => [],
    ]);

    expect($receipt1->receipt_no)
        ->not->toBe($receipt2->receipt_no)
        ->toStartWith('RCP-');
});

