<?php

declare(strict_types=1);

namespace App\Services\Ap;

use App\Models\ApInvoice;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class ApInvoiceService
{
    /**
     * Create a new AP invoice.
     *
     * @param  array<string, mixed>  $data
     */
    public function createInvoice(array $data): ApInvoice
    {
        return DB::transaction(function () use ($data) {
            // Auto-calculate due date if not provided
            if (!isset($data['due_date']) && isset($data['invoice_date'])) {
                $supplier = \App\Models\Supplier::find($data['supplier_id']);
                if ($supplier && $supplier->credit_days) {
                    $data['due_date'] = \Carbon\Carbon::parse($data['invoice_date'])
                        ->addDays($supplier->credit_days);
                }
            }

            // Calculate totals
            $subtotal = $data['subtotal_amount'] ?? 0;
            $discount = $data['discount_amount'] ?? 0;
            $tax = $data['tax_amount'] ?? 0;
            $grandTotal = $subtotal - $discount + $tax;

            $invoice = ApInvoice::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_id' => $data['purchase_id'] ?? null,
                'invoice_no' => $data['invoice_no'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'grand_total' => $grandTotal,
                'open_amount' => $grandTotal,
                'currency' => $data['currency'] ?? 'THB',
                'status' => 'draft',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            /** @var ApInvoice */
            return $invoice->fresh(['supplier', 'purchase']);
        });
    }

    /**
     * Update an existing AP invoice.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateInvoice(ApInvoice $invoice, array $data): ApInvoice
    {
        if (!$invoice->canBeUpdated()) {
            throw new \RuntimeException('Cannot update an issued or paid invoice');
        }

        return DB::transaction(function () use ($invoice, $data) {
            // Recalculate totals if amounts change
            if (isset($data['subtotal_amount']) || isset($data['discount_amount']) || isset($data['tax_amount'])) {
                $subtotal = $data['subtotal_amount'] ?? $invoice->subtotal_amount;
                $discount = $data['discount_amount'] ?? $invoice->discount_amount;
                $tax = $data['tax_amount'] ?? $invoice->tax_amount;
                $data['grand_total'] = $subtotal - $discount + $tax;
                $data['open_amount'] = $data['grand_total'];
            }

            $invoice->update($data);

            /** @var ApInvoice */
            return $invoice->fresh(['supplier', 'purchase']);
        });
    }

    /**
     * Issue an AP invoice.
     */
    public function issueInvoice(ApInvoice $invoice): ApInvoice
    {
        if (!$invoice->canBeIssued()) {
            throw new \RuntimeException('Invoice cannot be issued');
        }

        $invoice->issue();

        /** @var ApInvoice */
        return $invoice->fresh(['supplier', 'purchase']);
    }

    /**
     * Cancel an AP invoice.
     */
    public function cancelInvoice(ApInvoice $invoice): ApInvoice
    {
        if (!$invoice->canBeCancelled()) {
            throw new \RuntimeException('Invoice cannot be cancelled');
        }

        $invoice->cancel();

        /** @var ApInvoice */
        return $invoice->fresh(['supplier', 'purchase']);
    }

    /**
     * Create invoice from purchase order.
     */
    public function createFromPurchase(Purchase $purchase): ApInvoice
    {
        if (!$purchase->isApproved()) {
            throw new \RuntimeException('Purchase must be approved first');
        }

        // Generate invoice number
        $invoiceNo = $this->generateInvoiceNumber();

        return $this->createInvoice([
            'supplier_id' => $purchase->supplier_id,
            'purchase_id' => $purchase->id,
            'invoice_no' => $invoiceNo,
            'invoice_date' => now()->toDateString(),
            'subtotal_amount' => $purchase->subtotal,
            'discount_amount' => $purchase->discount,
            'tax_amount' => $purchase->tax,
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);
    }

    /**
     * Generate unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'API';
        $date = now()->format('Ymd');
        $lastInvoice = ApInvoice::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_no, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Update invoice paid status based on allocations.
     */
    public function updatePaidStatus(ApInvoice $invoice): ApInvoice
    {
        $paidTotal = $invoice->allocations()->sum('allocated_amount');

        $invoice->update([
            'paid_total' => $paidTotal,
        ]);

        $invoice->recalculateOpenAmount();
        $invoice->save();

        /** @var ApInvoice */
        return $invoice->fresh(['supplier', 'purchase']);
    }

    /**
     * Get supplier aging report.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAgingReport(int $supplierId): array
    {
        $invoices = ApInvoice::where('supplier_id', $supplierId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->get();

        $aging = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            'over_90' => 0,
        ];

        foreach ($invoices as $invoice) {
            if (!$invoice->due_date) {
                continue;
            }

            $daysOverdue = now()->diffInDays($invoice->due_date, false);

            if ($daysOverdue >= 0) {
                $aging['current'] += $invoice->open_amount;
            } elseif ($daysOverdue >= -30) {
                $aging['1_30'] += $invoice->open_amount;
            } elseif ($daysOverdue >= -60) {
                $aging['31_60'] += $invoice->open_amount;
            } elseif ($daysOverdue >= -90) {
                $aging['61_90'] += $invoice->open_amount;
            } else {
                $aging['over_90'] += $invoice->open_amount;
            }
        }

        return $aging;
    }
}
