<?php

declare(strict_types=1);

namespace App\Http\Requests\Ap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApPaymentRequest extends FormRequest
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
        $paymentId = $this->route('payment')->id ?? null;

        return [
            'payment_date' => ['sometimes', 'date'],
            'total_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.invoice_id' => ['required', 'exists:ap_invoices,id'],
            'allocations.*.allocated_amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'total_amount.min' => 'Total amount must be greater than 0',
            'allocations.*.invoice_id.required' => 'Invoice is required for allocation',
            'allocations.*.invoice_id.exists' => 'Selected invoice does not exist',
            'allocations.*.allocated_amount.required' => 'Allocated amount is required',
            'allocations.*.allocated_amount.min' => 'Allocated amount must be greater than 0',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('allocations') && $this->has('total_amount')) {
                $totalAllocated = collect($this->allocations)->sum('allocated_amount');
                $totalAmount = $this->total_amount;

                if ($totalAllocated > $totalAmount) {
                    $validator->errors()->add('allocations', 'Total allocated amount cannot exceed payment amount');
                }
            }
        });
    }
}
