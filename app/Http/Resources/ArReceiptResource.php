<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ArReceipt */
class ArReceiptResource extends JsonResource
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
            'receipt_no' => $this->receipt_no,
            'receipt_date' => $this->receipt_date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'payment_method' => $this->payment_method,
            'reference_no' => $this->reference_no ?? $this->reference,
            'note' => $this->note,
            'status' => $this->status,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'total_allocated' => $this->total_allocated,
            'unallocated_amount' => $this->unallocated_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'allocations' => $this->whenLoaded('allocations', fn () => ArReceiptAllocationResource::collection($this->allocations)),
        ];
    }
}

