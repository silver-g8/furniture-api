<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementIndexRequest extends FormRequest
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
            'warehouse_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'product_id' => ['sometimes', 'integer', 'exists:products,id'],
            'stock_id' => ['sometimes', 'integer', 'exists:stocks,id'],
            'type' => ['sometimes', 'string', Rule::in(['in', 'out'])],
            'reference_type' => ['sometimes', 'string', 'max:255'],
            'reference_id' => ['sometimes', 'integer'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date', 'after_or_equal:from_date'],
            'search' => ['sometimes', 'string', 'max:255'],
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
        return [
            'warehouse_id.exists' => 'Selected warehouse does not exist',
            'product_id.exists' => 'Selected product does not exist',
            'stock_id.exists' => 'Selected stock does not exist',
            'type.in' => 'Type must be either "in" or "out"',
            'to_date.after_or_equal' => 'To date must be after or equal to from date',
            'per_page.max' => 'Per page cannot exceed 100',
        ];
    }
}

