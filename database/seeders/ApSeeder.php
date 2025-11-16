<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApInvoice;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ApSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing suppliers or create new ones
        $suppliers = Supplier::inRandomOrder()->limit(5)->get();

        if ($suppliers->count() < 5) {
            $suppliers = Supplier::factory(5)->create([
                'payment_terms' => 'Net 30',
                'credit_days' => 30,
                'credit_limit' => 100000,
            ]);
        } else {
            // Update existing suppliers with payment terms
            $suppliers->each(function ($supplier) {
                $supplier->update([
                    'payment_terms' => 'Net 30',
                    'credit_days' => 30,
                    'credit_limit' => 100000,
                ]);
            });
        }

        // Create AP Invoices for each supplier
        foreach ($suppliers as $supplier) {
            // Draft invoices (2 per supplier)
            ApInvoice::factory(2)->create([
                'supplier_id' => $supplier->id,
                'status' => 'draft',
            ]);

            // Issued invoices (3 per supplier)
            $issuedInvoices = ApInvoice::factory(3)->issued()->create([
                'supplier_id' => $supplier->id,
            ]);

            // Create a payment for this supplier
            $payment = ApPayment::factory()->create([
                'supplier_id' => $supplier->id,
                'total_amount' => 15000,
            ]);

            // Allocate payment to the first issued invoice
            $invoice = $issuedInvoices->first();
            $allocatedAmount = min(15000, $invoice->open_amount);

            ApPaymentAllocation::factory()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'allocated_amount' => $allocatedAmount,
            ]);

            // Update invoice paid status
            $invoice->update([
                'paid_total' => $allocatedAmount,
                'open_amount' => $invoice->grand_total - $allocatedAmount,
                'status' => $allocatedAmount >= $invoice->grand_total ? 'paid' : 'partially_paid',
            ]);

            // Post the payment
            $payment->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);

            // Overdue invoices (2 per supplier)
            ApInvoice::factory(2)->overdue()->create([
                'supplier_id' => $supplier->id,
            ]);

            // Partially paid invoices (1 per supplier)
            ApInvoice::factory(1)->partiallyPaid()->create([
                'supplier_id' => $supplier->id,
            ]);

            // Paid invoices (2 per supplier)
            ApInvoice::factory(2)->paid()->create([
                'supplier_id' => $supplier->id,
            ]);

            // Create a posted payment with multiple allocations
            $multiPayment = ApPayment::factory()->create([
                'supplier_id' => $supplier->id,
                'total_amount' => 30000,
            ]);

            // Allocate to multiple invoices
            $unpaidInvoices = $issuedInvoices->skip(1)->take(2);
            $remainingAmount = 30000;

            foreach ($unpaidInvoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $allocAmount = min($remainingAmount, $invoice->open_amount);

                ApPaymentAllocation::factory()->create([
                    'payment_id' => $multiPayment->id,
                    'invoice_id' => $invoice->id,
                    'allocated_amount' => $allocAmount,
                ]);

                $invoice->update([
                    'paid_total' => $invoice->paid_total + $allocAmount,
                    'open_amount' => $invoice->open_amount - $allocAmount,
                    'status' => $invoice->open_amount - $allocAmount <= 0 ? 'paid' : 'partially_paid',
                ]);

                $remainingAmount -= $allocAmount;
            }

            $multiPayment->update([
                'status' => 'posted',
                'posted_at' => now(),
            ]);
        }

        $this->command->info('✅ AP Invoices and Payments seeded successfully!');
        $this->command->info('   - Total Suppliers: ' . $suppliers->count());
        $this->command->info('   - Total AP Invoices: ' . ApInvoice::count());
        $this->command->info('   - Total AP Payments: ' . ApPayment::count());
        $this->command->info('   - Total Allocations: ' . ApPaymentAllocation::count());
    }
}
