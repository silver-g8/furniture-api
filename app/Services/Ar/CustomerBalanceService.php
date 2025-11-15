<?php

declare(strict_types=1);

namespace App\Services\Ar;

use App\Models\ArInvoice;
use App\Models\Customer;

class CustomerBalanceService
{
    /**
     * Recalculate outstanding balance for a specific customer.
     *
     * This method:
     * 1. Calculates paid_total and open_amount for each invoice from allocations
     * 2. Updates invoice status (open/partial/paid)
     * 3. Sums all open_amount and updates customers.outstanding_balance
     */
    public function recalculateForCustomer(int $customerId): void
    {
        // คำนวณ open_amount ต่อ invoice ก่อน (กันกรณีข้อมูลหลุด)
        $invoices = ArInvoice::query()
            ->where('customer_id', $customerId)
            ->whereNot('status', 'cancelled')
            ->get();

        foreach ($invoices as $invoice) {
            // Only count allocations from posted receipts
            $paid = $invoice->allocations()
                ->whereHas('receipt', function ($query) {
                    $query->where('status', 'posted');
                })
                ->sum('allocated_amount');

            $invoice->paid_total = $paid;
            $invoice->open_amount = max(0, $invoice->grand_total - $paid);

            // Update status based on payment (only if invoice is not cancelled)
            if ($invoice->status !== 'cancelled' && $invoice->status !== 'draft') {
                if ($invoice->open_amount <= 0) {
                    $invoice->status = 'paid';
                } elseif ($paid > 0) {
                    $invoice->status = 'partially_paid';
                } else {
                    $invoice->status = 'issued';
                }
            }

            $invoice->save();
        }

        // รวมยอด open_amount ทุกใบของลูกค้า
        $outstanding = ArInvoice::query()
            ->where('customer_id', $customerId)
            ->whereNot('status', 'cancelled')
            ->sum('open_amount');

        // อัปเดต cache ในตาราง customers
        Customer::whereKey($customerId)->update([
            'outstanding_balance' => $outstanding,
        ]);
    }

    /**
     * Recalculate outstanding balance for all customers.
     *
     * Loops through all customers that have invoices and recalculates their balance.
     */
    public function recalculateForAllCustomers(): void
    {
        $customerIds = ArInvoice::query()
            ->select('customer_id')
            ->distinct()
            ->pluck('customer_id');

        foreach ($customerIds as $customerId) {
            $this->recalculateForCustomer((int) $customerId);
        }
    }
}

