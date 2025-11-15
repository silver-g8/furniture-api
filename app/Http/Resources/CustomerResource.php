<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Customer */
class CustomerResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,

            // กลุ่มลูกค้า
            'customer_group' => $this->customer_group,
            'customer_group_label' => $this->customer_group_label,

            // ประเภทการชำระ & เครดิต
            'payment_type' => $this->payment_type,
            'is_credit' => $this->is_credit,
            'credit_limit' => $this->credit_limit,
            'credit_term_days' => $this->credit_term_days,
            'credit_note' => $this->credit_note,

            // ยอดค้างชำระ
            'outstanding_balance' => $this->outstanding_balance,
            'is_over_credit_limit' => $this->is_over_credit_limit,

            // timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

