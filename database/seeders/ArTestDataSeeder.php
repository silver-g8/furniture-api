<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ArInvoice;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Customer;
use App\Models\SalesOrder;
use Illuminate\Database\Seeder;

class ArTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test customers
        $customer1 = Customer::factory()->credit()->create([
            'name' => 'Test Customer - Credit',
            'code' => 'CUST-AR-001',
            'credit_limit' => 100000,
            'credit_term_days' => 30,
        ]);

        $customer2 = Customer::factory()->credit()->create([
            'name' => 'Test Customer - Overdue',
            'code' => 'CUST-AR-002',
            'credit_limit' => 50000,
            'credit_term_days' => 45,
        ]);

        $customer3 = Customer::factory()->create([
            'name' => 'Test Customer - Paid',
            'code' => 'CUST-AR-003',
            'payment_type' => 'cash',
        ]);

        // Create sales orders for customer1
        $salesOrder1 = SalesOrder::factory()->create([
            'customer_id' => $customer1->id,
            'total_amount' => 50000,
        ]);

        $salesOrder2 = SalesOrder::factory()->create([
            'customer_id' => $customer1->id,
            'total_amount' => 30000,
        ]);

        // Create invoices for customer1 - various statuses
        $invoice1 = ArInvoice::factory()
            ->fromSalesOrder($salesOrder1)
            ->issued()
            ->create([
                'customer_id' => $customer1->id,
                'subtotal_amount' => 50000,
                'discount_amount' => 0,
                'tax_amount' => 3500,
                'grand_total' => 53500,
                'open_amount' => 53500,
            ]);

        $invoice2 = ArInvoice::factory()
            ->fromSalesOrder($salesOrder2)
            ->partiallyPaid()
            ->create([
                'customer_id' => $customer1->id,
                'subtotal_amount' => 30000,
                'discount_amount' => 1000,
                'tax_amount' => 2030,
                'grand_total' => 31030,
                'paid_total' => 15000,
                'open_amount' => 16030,
            ]);

        $invoice3 = ArInvoice::factory()
            ->issued()
            ->create([
                'customer_id' => $customer1->id,
                'subtotal_amount' => 20000,
                'discount_amount' => 0,
                'tax_amount' => 1400,
                'grand_total' => 21400,
                'open_amount' => 21400,
            ]);

        $invoice4 = ArInvoice::factory()
            ->paid()
            ->create([
                'customer_id' => $customer1->id,
                'subtotal_amount' => 15000,
                'discount_amount' => 500,
                'tax_amount' => 1015,
                'grand_total' => 15515,
                'paid_total' => 15515,
                'open_amount' => 0,
            ]);

        // Create overdue invoice for customer2
        $invoice5 = ArInvoice::factory()
            ->overdue()
            ->create([
                'customer_id' => $customer2->id,
                'subtotal_amount' => 40000,
                'discount_amount' => 0,
                'tax_amount' => 2800,
                'grand_total' => 42800,
                'open_amount' => 42800,
            ]);

        // Create receipts and allocations for customer1
        $receipt1 = ArReceipt::factory()->create([
            'customer_id' => $customer1->id,
            'total_amount' => 20000,
            'payment_method' => 'transfer',
            'reference_no' => 'TRF-001',
        ]);

        // Allocate receipt1 to invoice2 (partial payment)
        ArReceiptAllocation::factory()
            ->forReceipt($receipt1)
            ->forInvoice($invoice2)
            ->create([
                'allocated_amount' => 15000,
            ]);

        // Post receipt1
        $receipt1->status = 'posted';
        $receipt1->posted_at = now();
        $receipt1->save();

        // Update invoice2 paid_total
        $invoice2->paid_total = 15000;
        $invoice2->open_amount = 16030;
        $invoice2->status = 'partially_paid';
        $invoice2->save();

        // Create another receipt for customer1 (draft)
        $receipt2 = ArReceipt::factory()->create([
            'customer_id' => $customer1->id,
            'total_amount' => 25000,
            'payment_method' => 'cash',
        ]);

        // Allocate receipt2 to multiple invoices
        ArReceiptAllocation::factory()
            ->forReceipt($receipt2)
            ->forInvoice($invoice1)
            ->create([
                'allocated_amount' => 20000,
            ]);

        ArReceiptAllocation::factory()
            ->forReceipt($receipt2)
            ->forInvoice($invoice2)
            ->create([
                'allocated_amount' => 5000,
            ]);

        // Create paid invoice scenario for customer3
        $invoice6 = ArInvoice::factory()
            ->paid()
            ->create([
                'customer_id' => $customer3->id,
                'subtotal_amount' => 10000,
                'discount_amount' => 0,
                'tax_amount' => 700,
                'grand_total' => 10700,
                'paid_total' => 10700,
                'open_amount' => 0,
            ]);

        $receipt3 = ArReceipt::factory()->posted()->create([
            'customer_id' => $customer3->id,
            'total_amount' => 10700,
            'payment_method' => 'transfer',
            'reference_no' => 'TRF-002',
        ]);

        ArReceiptAllocation::factory()
            ->forReceipt($receipt3)
            ->forInvoice($invoice6)
            ->create([
                'allocated_amount' => 10700,
            ]);

        // Create draft invoice
        $invoice7 = ArInvoice::factory()->create([
            'customer_id' => $customer1->id,
            'subtotal_amount' => 12000,
            'discount_amount' => 0,
            'tax_amount' => 840,
            'grand_total' => 12840,
            'open_amount' => 12840,
            'status' => 'draft',
        ]);

        $this->command->info('AR test data seeded successfully!');
        $this->command->info("Created customers: {$customer1->name}, {$customer2->name}, {$customer3->name}");
        $this->command->info('Created invoices with various statuses: draft, issued, partially_paid, paid, overdue');
        $this->command->info('Created receipts: draft and posted');
        $this->command->info('Created allocations linking receipts to invoices');
    }
}

