<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Enums\InstallationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallationOrderUpdateRequest extends FormRequest
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
            'status' => ['sometimes', 'required', Rule::in(InstallationStatus::values())],
            'installation_address_id' => ['sometimes', 'nullable', 'integer', 'exists:customer_addresses,id'],
            'installation_address_override' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'installation_contact_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'installation_contact_phone' => ['sometimes', 'nullable', 'string', 'regex:/^[0-9\-\+\(\) ]+$/', 'max:20'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
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
            'status.in' => 'The selected status is invalid.',
            'installation_contact_phone.regex' => 'The phone number format is invalid.',
        ];
    }
}
