<?php

declare(strict_types=1);

namespace App\Services\Ap;

use App\Models\ApInvoice;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use Illuminate\Support\Facades\DB;

class ApPaymentService
{
    public function __construct(
        protected ApInvoiceService $invoiceService
    ) {}

    /**
     * Create a new AP payment with allocations.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPayment(array $data): ApPayment
    {
        return DB::transaction(function () use ($data) {
            $payment = ApPayment::create([
                'supplier_id' => $data['supplier_id'],
                'payment_no' => $data['payment_no'] ?? $this->generatePaymentNumber(),
                'payment_date' => $data['payment_date'],
                'total_amount' => $data['total_amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'reference_no' => $data['reference_no'] ?? null,
                'status' => 'draft',
                'note' => $data['note'] ?? null,
            ]);

            // Create allocations if provided
            if (isset($data['allocations']) && is_array($data['allocations'])) {
                foreach ($data['allocations'] as $allocation) {
                    $this->allocateToInvoice(
                        $payment,
                        $allocation['invoice_id'],
                        $allocation['allocated_amount']
                    );
                }
            }

            /** @var ApPayment */
            return $payment->fresh(['supplier', 'allocations.invoice']);
        });
    }

    /**
     * Update an existing AP payment.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePayment(ApPayment $payment, array $data): ApPayment
    {
        if (!$payment->canBeUpdated()) {
            throw new \RuntimeException('Cannot update a posted payment');
        }

        return DB::transaction(function () use ($payment, $data) {
            $payment->update([
                'payment_date' => $data['payment_date'] ?? $payment->payment_date,
                'total_amount' => $data['total_amount'] ?? $payment->total_amount,
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'reference' => $data['reference'] ?? $payment->reference,
                'reference_no' => $data['reference_no'] ?? $payment->reference_no,
                'note' => $data['note'] ?? $payment->note,
            ]);

            // Update allocations if provided
            if (isset($data['allocations']) && is_array($data['allocations'])) {
                // Remove existing allocations
                $this->removeAllAllocations($payment);

                // Create new allocations
                foreach ($data['allocations'] as $allocation) {
                    $this->allocateToInvoice(
                        $payment,
                        $allocation['invoice_id'],
                        $allocation['allocated_amount']
                    );
                }
            }

            /** @var ApPayment */
            return $payment->fresh(['supplier', 'allocations.invoice']);
        });
    }

    /**
     * Post a payment (finalize it).
     */
    public function postPayment(ApPayment $payment): ApPayment
    {
        if (!$payment->canBePosted()) {
            throw new \RuntimeException('Payment cannot be posted. Ensure it has allocations.');
        }

        return DB::transaction(function () use ($payment) {
            $payment->post();

            // Update all related invoices
            foreach ($payment->allocations as $allocation) {
                $this->invoiceService->updatePaidStatus($allocation->invoice);
            }

            /** @var ApPayment */
            return $payment->fresh(['supplier', 'allocations.invoice']);
        });
    }

    /**
     * Cancel a payment.
     */
    public function cancelPayment(ApPayment $payment): ApPayment
    {
        if (!$payment->canBeCancelled()) {
            throw new \RuntimeException('Payment cannot be cancelled');
        }

        return DB::transaction(function () use ($payment) {
            // Store allocations before cancelling
            $allocations = $payment->allocations;

            $payment->cancel();

            // Update all related invoices
            foreach ($allocations as $allocation) {
                $this->invoiceService->updatePaidStatus($allocation->invoice);
            }

            /** @var ApPayment */
            return $payment->fresh(['supplier', 'allocations.invoice']);
        });
    }

    /**
     * Allocate payment to an invoice.
     */
    public function allocateToInvoice(ApPayment $payment, int $invoiceId, float $amount): ApPaymentAllocation
    {
        if (!$payment->canBeUpdated()) {
            throw new \RuntimeException('Cannot allocate to a posted payment');
        }

        $invoice = ApInvoice::findOrFail($invoiceId);

        // Validate supplier matches
        if ($payment->supplier_id !== $invoice->supplier_id) {
            throw new \RuntimeException('Payment and invoice must belong to the same supplier');
        }

        // Validate amount doesn't exceed invoice open amount
        if ($amount > $invoice->open_amount) {
            throw new \RuntimeException('Allocation amount exceeds invoice open amount');
        }

        // Validate total allocations don't exceed payment amount
        $currentAllocated = $payment->total_allocated;
        if (($currentAllocated + $amount) > $payment->total_amount) {
            throw new \RuntimeException('Total allocations exceed payment amount');
        }

        return ApPaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoiceId,
            'allocated_amount' => $amount,
        ]);
    }

    /**
     * Remove an allocation.
     */
    public function removeAllocation(ApPaymentAllocation $allocation): void
    {
        $payment = $allocation->payment;

        if (!$payment->canBeUpdated()) {
            throw new \RuntimeException('Cannot modify allocations of a posted payment');
        }

        $allocation->delete();
    }

    /**
     * Remove all allocations from a payment.
     */
    public function removeAllAllocations(ApPayment $payment): void
    {
        if (!$payment->canBeUpdated()) {
            throw new \RuntimeException('Cannot modify allocations of a posted payment');
        }

        $payment->allocations()->delete();
    }

    /**
     * Generate unique payment number.
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = 'APP';
        $date = now()->format('Ymd');
        $lastPayment = ApPayment::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment ? ((int) substr($lastPayment->payment_no, -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Auto-allocate payment to invoices.
     * Allocates payment to oldest invoices first.
     *
     * @return array<int, ApPaymentAllocation>
     */
    public function autoAllocate(ApPayment $payment): array
    {
        if (!$payment->canBeUpdated()) {
            throw new \RuntimeException('Cannot allocate to a posted payment');
        }

        return DB::transaction(function () use ($payment) {
            // Get unpaid invoices for the supplier
            $invoices = ApInvoice::where('supplier_id', $payment->supplier_id)
                ->whereIn('status', ['issued', 'partially_paid'])
                ->where('open_amount', '>', 0)
                ->orderBy('due_date', 'asc')
                ->orderBy('invoice_date', 'asc')
                ->get();

            $remainingAmount = $payment->total_amount - $payment->total_allocated;
            $allocations = [];

            foreach ($invoices as $invoice) {
                if ($remainingAmount <= 0) {
                    break;
                }

                $allocateAmount = min($remainingAmount, $invoice->open_amount);

                $allocation = $this->allocateToInvoice($payment, $invoice->id, (float) $allocateAmount);
                $allocations[] = $allocation;

                $remainingAmount -= $allocateAmount;
            }

            return $allocations;
        });
    }

    /**
     * Get supplier payment summary.
     *
     * @return array<string, mixed>
     */
    public function getSupplierPaymentSummary(int $supplierId): array
    {
        $totalPaid = ApPayment::where('supplier_id', $supplierId)
            ->where('status', 'posted')
            ->sum('total_amount');

        $totalOutstanding = ApInvoice::where('supplier_id', $supplierId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->sum('open_amount');

        $overdueAmount = ApInvoice::where('supplier_id', $supplierId)
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('due_date', '<', \now())
            ->sum('open_amount');

        return [
            'total_paid' => $totalPaid,
            'total_outstanding' => $totalOutstanding,
            'overdue_amount' => $overdueAmount,
        ];
    }
}
