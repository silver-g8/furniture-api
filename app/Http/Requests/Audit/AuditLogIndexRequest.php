<?php

declare(strict_types=1);

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

/**
 * AuditLogIndexRequest
 *
 * Validates query parameters for listing audit logs.
 */
class AuditLogIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validTypes = array_keys(config('audit.type_aliases', []));

        return [
            'auditable_type' => ['sometimes', 'string', 'max:255'],
            'auditable_id' => ['sometimes', 'integer', 'min:1'],
            'type' => ['sometimes', 'string', 'in:'.implode(',', $validTypes)],
            'action' => ['sometimes', 'string', 'in:created,updated,approved,cancelled,deleted'],
            'user_id' => ['sometimes', 'integer', 'min:1'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $validTypes = implode(', ', array_keys(config('audit.type_aliases', [])));

        return [
            'type.in' => "The type must be one of: {$validTypes}.",
            'action.in' => 'The action must be one of: created, updated, approved, cancelled, deleted.',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'per_page.max' => 'You cannot request more than 100 items per page.',
        ];
    }

    /**
     * Get validated filters for the query.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->only([
            'auditable_type',
            'auditable_id',
            'type',
            'action',
            'user_id',
            'date_from',
            'date_to',
        ]);
    }

    /**
     * Get pagination parameters.
     *
     * @return array{page: int, per_page: int}
     */
    public function pagination(): array
    {
        return [
            'page' => (int) $this->input('page', 1),
            'per_page' => (int) $this->input('per_page', 15),
        ];
    }
}
