<?php

declare(strict_types=1);

namespace App\Services\Ar;

use App\Models\ArInvoice;
use App\Models\SalesOrder;
use App\Services\Ar\CustomerBalanceService;
use Illuminate\Support\Facades\DB;

class ArInvoiceService
{
    public function __construct(
        private readonly CustomerBalanceService $balanceService
    ) {
    }

    /**
     * Create invoice from SalesOrder.
     */
    public function createFromSalesOrder(SalesOrder $salesOrder, array $additionalData = []): ArInvoice
    {
        return DB::transaction(function () use ($salesOrder, $additionalData) {
            $invoice = new ArInvoice();
            $invoice->customer_id = $salesOrder->customer_id;
            $invoice->sales_order_id = $salesOrder->id;
            $invoice->invoice_no = $this->generateInvoiceNo();
            $invoice->invoice_date = $additionalData['invoice_date'] ?? now()->toDateString();
            $invoice->due_date = $additionalData['due_date'] ?? null;
            $invoice->currency = $additionalData['currency'] ?? 'THB';

            // Calculate amounts from sales order
            $invoice->subtotal_amount = $salesOrder->total_amount ?? 0;
            $invoice->discount_amount = $additionalData['discount_amount'] ?? 0;
            $invoice->tax_amount = $additionalData['tax_amount'] ?? 0;
            $invoice->calculateTotal();

            $invoice->paid_total = 0;
            $invoice->open_amount = $invoice->grand_total;
            $invoice->status = 'draft';
            $invoice->reference_type = 'sales_order';
            $invoice->reference_id = $salesOrder->id;
            $invoice->note = $additionalData['note'] ?? null;

            $invoice->save();

            $invoice->auditCreated(['source' => 'sales_order', 'sales_order_id' => $salesOrder->id]);

            return $invoice;
        });
    }

    /**
     * Create invoice from payload (manual invoice).
     */
    public function createFromPayload(array $data): ArInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = new ArInvoice();
            $invoice->customer_id = $data['customer_id'];
            $invoice->sales_order_id = $data['sales_order_id'] ?? null;
            $invoice->invoice_no = $this->generateInvoiceNo();
            $invoice->invoice_date = $data['invoice_date'];
            $invoice->due_date = $data['due_date'] ?? null;
            $invoice->currency = $data['currency'] ?? 'THB';

            $invoice->subtotal_amount = $data['subtotal_amount'] ?? 0;
            $invoice->discount_amount = $data['discount_amount'] ?? 0;
            $invoice->tax_amount = $data['tax_amount'] ?? 0;
            $invoice->calculateTotal();

            $invoice->paid_total = 0;
            $invoice->open_amount = $invoice->grand_total;
            $invoice->status = 'draft';
            $invoice->reference_type = $data['reference_type'] ?? null;
            $invoice->reference_id = $data['reference_id'] ?? null;
            $invoice->note = $data['note'] ?? null;

            $invoice->save();

            $invoice->auditCreated(['source' => 'manual']);

            return $invoice;
        });
    }

    /**
     * Issue an invoice (draft → issued).
     */
    public function issue(ArInvoice $invoice): ArInvoice
    {
        if (! $invoice->canBeIssued()) {
            throw new \InvalidArgumentException('Invoice cannot be issued. Status must be draft and total_amount must be greater than 0.');
        }

        return DB::transaction(function () use ($invoice) {
            $before = $invoice->snapshot(['status', 'issued_at']);

            $invoice->status = 'issued';
            $invoice->issued_at = now();
            $invoice->save();

            $invoice->auditUpdated($before, $invoice->snapshot(['status', 'issued_at']), [
                'action' => 'issued',
            ]);

            return $invoice;
        });
    }

    /**
     * Cancel an invoice.
     */
    public function cancel(ArInvoice $invoice): ArInvoice
    {
        if (! $invoice->canBeCancelled()) {
            throw new \InvalidArgumentException('Invoice cannot be cancelled. Must be draft/issued/partially_paid with no payments.');
        }

        return DB::transaction(function () use ($invoice) {
            $before = $invoice->snapshot(['status', 'cancelled_at']);

            $invoice->status = 'cancelled';
            $invoice->cancelled_at = now();
            $invoice->save();

            // Recalculate customer balance
            $this->balanceService->recalculateForCustomer($invoice->customer_id);

            $invoice->auditCancelled($before, $invoice->snapshot(['status', 'cancelled_at']));

            return $invoice;
        });
    }

    /**
     * Recalculate balance for an invoice.
     */
    public function recalculateBalance(ArInvoice $invoice): void
    {
        // Only count allocations from posted receipts
        $paidTotal = $invoice->allocations()
            ->whereHas('receipt', function ($query) {
                $query->where('status', 'posted');
            })
            ->sum('allocated_amount');

        $invoice->paid_total = $paidTotal;
        $invoice->recalculateOpenAmount();

        // Update status based on payment
        if ($invoice->open_amount <= 0 && $invoice->status !== 'cancelled') {
            $invoice->status = 'paid';
        } elseif ($paidTotal > 0 && $invoice->status === 'issued') {
            $invoice->status = 'partially_paid';
        } elseif ($paidTotal == 0 && $invoice->status === 'partially_paid') {
            $invoice->status = 'issued';
        }

        $invoice->save();
    }

    /**
     * Generate unique invoice number.
     */
    private function generateInvoiceNo(): string
    {
        $prefix = 'INV-';
        $date = now()->format('Ymd');
        $lastInvoice = ArInvoice::query()
            ->where('invoice_no', 'like', $prefix . $date . '%')
            ->orderByDesc('invoice_no')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . '-' . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }
}

