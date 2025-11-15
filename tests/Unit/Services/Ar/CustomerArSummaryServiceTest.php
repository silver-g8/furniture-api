<?php

declare(strict_types=1);

use App\Models\ArInvoice;
use App\Models\Customer;
use App\Services\Ar\CustomerArSummaryService;

beforeEach(function () {
    $this->summaryService = new CustomerArSummaryService();
});

test('can calculate AR summary for customer', function () {
    $customer = Customer::factory()->create();

    // Create invoices with various statuses
    $invoice1 = ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'paid_total' => 0,
        'open_amount' => 10000,
    ]);

    $invoice2 = ArInvoice::factory()->partiallyPaid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 20000,
        'paid_total' => 10000,
        'open_amount' => 10000,
    ]);

    $invoice3 = ArInvoice::factory()->paid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 15000,
        'paid_total' => 15000,
        'open_amount' => 0,
    ]);

    // Draft and cancelled invoices should not be counted
    ArInvoice::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'draft',
        'grand_total' => 5000,
    ]);

    ArInvoice::factory()->cancelled()->create([
        'customer_id' => $customer->id,
        'grand_total' => 3000,
    ]);

    $summary = $this->summaryService->getSummary($customer->id);

    expect($summary)
        ->customer_id->toBe($customer->id)
        ->total_invoiced->toBe(45000.0) // 10000 + 20000 + 15000
        ->total_paid->toBe(25000.0) // 0 + 10000 + 15000
        ->total_outstanding->toBe(20000.0); // 10000 + 10000 (only issued and partially_paid)
});

test('returns zero summary for customer with no invoices', function () {
    $customer = Customer::factory()->create();

    $summary = $this->summaryService->getSummary($customer->id);

    expect($summary)
        ->customer_id->toBe($customer->id)
        ->total_invoiced->toBe(0.0)
        ->total_paid->toBe(0.0)
        ->total_outstanding->toBe(0.0);
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

    $summary = $this->summaryService->getSummary($customer->id);

    $this->assertEqualsWithDelta(10000.0, (float) $summary['total_invoiced'], 0.01);
    $this->assertEqualsWithDelta(10000.0, (float) $summary['total_outstanding'], 0.01);
});

test('only counts issued and partially_paid for outstanding', function () {
    $customer = Customer::factory()->create();

    ArInvoice::factory()->issued()->create([
        'customer_id' => $customer->id,
        'grand_total' => 10000,
        'open_amount' => 10000,
    ]);

    ArInvoice::factory()->partiallyPaid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 20000,
        'open_amount' => 5000,
    ]);

    ArInvoice::factory()->paid()->create([
        'customer_id' => $customer->id,
        'grand_total' => 15000,
        'open_amount' => 0,
    ]);

    $summary = $this->summaryService->getSummary($customer->id);

    expect($summary)
        ->total_outstanding->toBe(15000.0); // 10000 + 5000 (paid invoice not counted)
});

