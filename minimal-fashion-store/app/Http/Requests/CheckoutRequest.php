<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['name' => 'required|string|max:100', 'phone' => ['required', 'regex:/^[0-9+ ()-]{9,20}$/'], 'address' => 'required|string|max:255', 'city' => 'required|string|max:100', 'shipping_method' => 'sometimes|required|in:standard,express', 'payment_method' => 'required|in:cod,vnpay', 'voucher' => 'nullable|string|max:50', 'checkout_key' => 'required|uuid'];
    }
}
