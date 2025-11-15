<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ArInvoice */
class ArInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'sales_order_id' => $this->sales_order_id,
            'invoice_no' => $this->invoice_no,
            'invoice_date' => $this->invoice_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'grand_total' => $this->grand_total,
            'paid_total' => $this->paid_total,
            'open_amount' => $this->open_amount,
            'status' => $this->status,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'note' => $this->note,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'is_overdue' => $this->is_overdue,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'sales_order' => $this->whenLoaded('salesOrder'),
            'allocations' => $this->whenLoaded('allocations', fn () => ArReceiptAllocationResource::collection($this->allocations)),
        ];
    }
}

