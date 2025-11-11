<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

class InstallationOrderStoreRequest extends FormRequest
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
            'sales_order_id' => ['required', 'integer', 'exists:sales_orders,id'],
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'installation_address_id' => ['required_without:installation_address_override', 'nullable', 'integer', 'exists:customer_addresses,id'],
            'installation_address_override' => ['required_without:installation_address_id', 'nullable', 'string', 'max:1000'],
            'installation_contact_name' => ['nullable', 'string', 'max:255'],
            'installation_contact_phone' => ['nullable', 'string', 'regex:/^[0-9\-\+\(\) ]+$/', 'max:20'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
            'sales_order_id.required' => 'Sales order is required.',
            'sales_order_id.exists' => 'The selected sales order does not exist.',
            'customer_id.required' => 'Customer is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'installation_address_id.required_without' => 'Either installation address or address override is required.',
            'installation_address_override.required_without' => 'Either installation address or address override is required.',
            'installation_contact_phone.regex' => 'The phone number format is invalid.',
        ];
    }
}
