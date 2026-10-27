<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->code)) {
            $this->merge(['code' => strtoupper(trim($this->code))]);
        }
    }

    public function rules(): array
    {
        return ['code' => ['required', 'alpha_dash', 'max:50', Rule::unique('vouchers')->ignore($this->route('voucher')?->id)], 'shop_id' => 'nullable|integer|exists:shops,id', 'type' => 'required|in:fixed,percent', 'value' => 'required|integer|min:1|max:'.($this->type === 'percent' ? 10000 : 1000000000), 'max_discount' => 'nullable|integer|min:1|max:1000000000', 'min_order' => 'required|integer|min:0|max:1000000000', 'usage_limit' => 'required|integer|min:1|max:1000000', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at'];
    }
}
