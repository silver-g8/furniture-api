<?php

declare(strict_types=1);

namespace App\Http\Requests\Ar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArReceiptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'receipt_date' => ['required', 'date'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', Rule::in(['cash', 'transfer', 'credit_card', 'cheque', 'other'])],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer', 'exists:ar_invoices,id'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allocations = $this->input('allocations', []);
            $totalAmount = (float) $this->input('total_amount', 0);
            $totalAllocated = array_sum(array_column($allocations, 'allocated_amount'));

            if ($totalAllocated > $totalAmount) {
                $validator->errors()->add(
                    'allocations',
                    "Total allocated amount ({$totalAllocated}) cannot exceed receipt total amount ({$totalAmount})"
                );
            }
        });
    }
}

