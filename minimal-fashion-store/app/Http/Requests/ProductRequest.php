<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['seller', 'admin']);
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:150', 'category_id' => 'required|exists:categories,id', 'description' => 'required|string|max:10000', 'collection_id' => 'nullable|integer|exists:collections,id', 'active' => 'nullable|boolean', 'shop_id' => [Rule::excludeIf($this->user()->role !== 'admin'), 'nullable', 'exists:shops,id']];
    }
}
