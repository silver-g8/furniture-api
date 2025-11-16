<?php

declare(strict_types=1);

namespace App\Http\Requests\Ap;

use Illuminate\Foundation\Http\FormRequest;

class StoreApInvoiceRequest extends FormRequest
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
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'invoice_no' => ['required', 'string', 'max:100', 'unique:ap_invoices,invoice_no'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'subtotal_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
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
            'supplier_id.required' => 'Supplier is required',
            'supplier_id.exists' => 'Selected supplier does not exist',
            'invoice_no.required' => 'Invoice number is required',
            'invoice_no.unique' => 'Invoice number already exists',
            'invoice_date.required' => 'Invoice date is required',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'subtotal_amount.required' => 'Subtotal amount is required',
            'subtotal_amount.min' => 'Subtotal amount must be at least 0',
        ];
    }
}
