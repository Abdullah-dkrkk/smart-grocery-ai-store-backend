<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1', 'max:100'],
            'products.*.id' => ['required', 'integer', 'exists:products,id'],
            'products.*.updates' => ['required', 'array'],
            'products.*.updates.name' => ['sometimes', 'string', 'max:255'],
            'products.*.updates.price' => ['sometimes', 'numeric', 'min:0'],
            'products.*.updates.compare_at_price' => ['sometimes', 'numeric', 'nullable', 'min:0'],
            'products.*.updates.stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'products.*.updates.is_active' => ['sometimes', 'boolean'],
            'products.*.updates.is_featured' => ['sometimes', 'boolean'],
            'products.*.updates.category_id' => ['sometimes', 'integer', 'exists:categories,id'],
        ];
    }
}
