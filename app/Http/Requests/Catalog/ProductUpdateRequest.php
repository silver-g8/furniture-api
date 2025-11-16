<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'nullable', 'exists:brands,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => ['sometimes', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($productId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'price_tagged' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_discounted_tag' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_discounted_net' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_vat' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_vat_credit' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'inactive', 'archived'])],
            'image_url' => ['sometimes', 'nullable', 'string'],
            'on_hand' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
