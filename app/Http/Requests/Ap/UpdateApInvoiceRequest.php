<?php

declare(strict_types=1);

namespace App\Http\Requests\Ap;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApInvoiceRequest extends FormRequest
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
        $invoiceId = $this->route('invoice')->id ?? null;

        return [
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'invoice_no' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('ap_invoices', 'invoice_no')->ignore($invoiceId),
            ],
            'invoice_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'subtotal_amount' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
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
            'supplier_id.exists' => 'Selected supplier does not exist',
            'invoice_no.unique' => 'Invoice number already exists',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'subtotal_amount.min' => 'Subtotal amount must be at least 0',
        ];
    }
}
