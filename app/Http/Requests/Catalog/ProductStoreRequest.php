<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductStoreRequest extends FormRequest
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
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_tagged' => ['nullable', 'numeric', 'min:0'],
            'price_discounted_tag' => ['nullable', 'numeric', 'min:0'],
            'price_discounted_net' => ['nullable', 'numeric', 'min:0'],
            'price_vat' => ['nullable', 'numeric', 'min:0'],
            'price_vat_credit' => ['nullable', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'image_url' => ['nullable', 'string'],
            'on_hand' => ['required', 'integer', 'min:0'],
        ];
    }
}
