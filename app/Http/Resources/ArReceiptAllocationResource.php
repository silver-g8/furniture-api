<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ArReceiptAllocation */
class ArReceiptAllocationResource extends JsonResource
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
            'receipt_id' => $this->receipt_id,
            'invoice_id' => $this->invoice_id,
            'allocated_amount' => $this->allocated_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'receipt' => $this->whenLoaded('receipt', fn () => new ArReceiptResource($this->receipt)),
            'invoice' => $this->whenLoaded('invoice', fn () => new ArInvoiceResource($this->invoice)),
        ];
    }
}

