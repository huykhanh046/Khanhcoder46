<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function methods(): array
    {
        $settings = SiteSetting::where('key', 'shipping')->first()?->value ?? [];

        return [
            'standard' => ['code' => 'standard', 'label' => 'Giao hàng tiêu chuẩn', 'fee' => (int) ($settings['standard_fee'] ?? config('store.shipping_per_shop')), 'estimate' => '3–5 ngày làm việc'],
            'express' => ['code' => 'express', 'label' => 'Giao hàng nhanh', 'fee' => (int) ($settings['express_fee'] ?? 50000), 'estimate' => '1–2 ngày làm việc'],
        ];
    }

    public function resolve(string $method): array
    {
        $methods = $this->methods();
        if (! isset($methods[$method])) {
            throw ValidationException::withMessages(['shipping_method' => 'Phương thức vận chuyển không hợp lệ.']);
        }

        return $methods[$method];
    }
}
