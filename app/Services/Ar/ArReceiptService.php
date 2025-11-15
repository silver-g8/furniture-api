<?php

declare(strict_types=1);

namespace App\Services\Ar;

use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Services\Ar\CustomerBalanceService;
use Illuminate\Support\Facades\DB;

class ArReceiptService
{
    public function __construct(
        private readonly CustomerBalanceService $balanceService,
        private readonly ArInvoiceService $invoiceService
    ) {
    }

    /**
     * Create receipt with allocations.
     */
    public function createWithAllocations(array $data): ArReceipt
    {
        return DB::transaction(function () use ($data) {
            $receipt = new ArReceipt();
            $receipt->customer_id = $data['customer_id'];
            $receipt->receipt_no = $this->generateReceiptNo();
            $receipt->receipt_date = $data['receipt_date'];
            $receipt->total_amount = $data['total_amount'];
            $receipt->payment_method = $data['payment_method'] ?? null;
            $receipt->reference_no = $data['reference_no'] ?? null;
            $receipt->reference = $data['reference_no'] ?? null; // Backward compatibility
            $receipt->note = $data['note'] ?? null;
            $receipt->status = 'draft';
            $receipt->save();

            // Create allocations
            $allocations = $data['allocations'] ?? [];
            $totalAllocated = 0;

            foreach ($allocations as $allocationData) {
                $allocation = new ArReceiptAllocation();
                $allocation->receipt_id = $receipt->id;
                $allocation->invoice_id = $allocationData['invoice_id'];
                $allocation->allocated_amount = $allocationData['allocated_amount'];
                $allocation->save();

                $totalAllocated += $allocation->allocated_amount;
            }

            // Validate that total allocated does not exceed receipt total
            if ($totalAllocated > $receipt->total_amount) {
                throw new \InvalidArgumentException(
                    "Total allocated amount ({$totalAllocated}) exceeds receipt total amount ({$receipt->total_amount})"
                );
            }

            $receipt->auditCreated(['allocations_count' => count($allocations)]);

            return $receipt->fresh(['allocations']);
        });
    }

    /**
     * Post a receipt (apply allocations to invoices).
     */
    public function post(ArReceipt $receipt): ArReceipt
    {
        if (! $receipt->canBePosted()) {
            throw new \InvalidArgumentException('Receipt cannot be posted. Must be draft with allocations.');
        }

        return DB::transaction(function () use ($receipt) {
            $before = $receipt->snapshot(['status', 'posted_at']);

            // Update invoice balances
            foreach ($receipt->allocations as $allocation) {
                $invoice = $allocation->invoice;

                // Only update if invoice is not cancelled
                if ($invoice->status !== 'cancelled') {
                    $invoice->paid_total += $allocation->allocated_amount;
                    $this->invoiceService->recalculateBalance($invoice);
                }
            }

            // Update receipt status
            $receipt->status = 'posted';
            $receipt->posted_at = now();
            $receipt->save();

            // Recalculate customer balance
            $this->balanceService->recalculateForCustomer($receipt->customer_id);

            $receipt->auditUpdated($before, $receipt->snapshot(['status', 'posted_at']), [
                'action' => 'posted',
            ]);

            return $receipt->fresh(['allocations']);
        });
    }

    /**
     * Cancel a receipt (rollback allocations).
     */
    public function cancel(ArReceipt $receipt): ArReceipt
    {
        if (! $receipt->canBeCancelled()) {
            throw new \InvalidArgumentException('Receipt cannot be cancelled. Must be posted.');
        }

        return DB::transaction(function () use ($receipt) {
            $before = $receipt->snapshot(['status', 'cancelled_at']);

            // Rollback invoice balances - recalculate from posted receipts only
            $invoicesToRecalculate = [];
            foreach ($receipt->allocations as $allocation) {
                $invoice = $allocation->invoice;

                // Only rollback if invoice is not cancelled
                if ($invoice->status !== 'cancelled') {
                    $invoicesToRecalculate[$invoice->id] = $invoice;
                }
            }

            // Recalculate balances for affected invoices
            foreach ($invoicesToRecalculate as $invoice) {
                $this->invoiceService->recalculateBalance($invoice);
            }

            // Update receipt status
            $receipt->status = 'cancelled';
            $receipt->cancelled_at = now();
            $receipt->save();

            // Recalculate customer balance
            $this->balanceService->recalculateForCustomer($receipt->customer_id);

            $receipt->auditCancelled($before, $receipt->snapshot(['status', 'cancelled_at']));

            return $receipt->fresh(['allocations']);
        });
    }

    /**
     * Generate unique receipt number.
     */
    private function generateReceiptNo(): string
    {
        $prefix = 'RCP-';
        $date = now()->format('Ymd');
        $lastReceipt = ArReceipt::query()
            ->where('receipt_no', 'like', $prefix . $date . '%')
            ->orderByDesc('receipt_no')
            ->first();

        if ($lastReceipt) {
            $lastNumber = (int) substr($lastReceipt->receipt_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}

