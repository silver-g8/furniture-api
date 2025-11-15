<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerUpdateRequest extends FormRequest
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
        $customerId = $this->route('customer');

        return [
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('customers', 'code')->ignore($customerId)],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'payment_type' => ['sometimes', 'string', 'in:cash,credit'],
            'customer_group' => ['sometimes', 'string', 'in:personal,government,organization'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'required_if:payment_type,credit'],
            'credit_term_days' => ['nullable', 'integer', 'min:1', 'required_if:payment_type,credit'],
            'outstanding_balance' => ['nullable', 'numeric', 'min:0'],
            'credit_note' => ['nullable', 'string'],
        ];
    }
}
