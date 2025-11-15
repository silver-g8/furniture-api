<?php

declare(strict_types=1);

namespace App\Services\Ar;

use App\Models\ArInvoice;
use App\Models\Customer;

class CustomerArSummaryService
{
    /**
     * Get AR summary for a customer.
     *
     * @return array{total_invoiced: float, total_paid: float, total_outstanding: float}
     */
    public function getSummary(int $customerId): array
    {
        $invoices = ArInvoice::query()
            ->where('customer_id', $customerId)
            ->whereNot('status', 'cancelled')
            ->get();

        $totalInvoiced = 0;
        $totalPaid = 0;
        $totalOutstanding = 0;

        foreach ($invoices as $invoice) {
            // Only count issued, partially_paid, and paid invoices
            if (in_array($invoice->status, ['issued', 'partially_paid', 'paid'])) {
                $totalInvoiced += (float) $invoice->grand_total;
                $totalPaid += (float) $invoice->paid_total;
            }

            // Outstanding only for issued and partially_paid
            if (in_array($invoice->status, ['issued', 'partially_paid'])) {
                $totalOutstanding += (float) $invoice->open_amount;
            }
        }

        return [
            'customer_id' => $customerId,
            'total_invoiced' => round($totalInvoiced, 2),
            'total_paid' => round($totalPaid, 2),
            'total_outstanding' => round($totalOutstanding, 2),
        ];
    }

    /**
     * Get AR summary for all customers (for reporting).
     *
     * @return array<int, array{total_invoiced: float, total_paid: float, total_outstanding: float}>
     */
    public function getSummaryForAllCustomers(): array
    {
        $customers = Customer::query()->pluck('id');

        $summaries = [];
        foreach ($customers as $customerId) {
            $summaries[$customerId] = $this->getSummary($customerId);
        }

        return $summaries;
    }
}

