<?php

namespace App\Services;

use App\Models\Voucher;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    public function calculate(string $code, array $groups, bool $lock = true): array
    {
        $q = Voucher::where('code', strtoupper(trim($code)));
        if ($lock) {
            $q->lockForUpdate();
        } $v = $q->first();
        if (! $v || ! $v->active || now()->lt($v->starts_at) || now()->gte($v->ends_at) || $v->used >= $v->usage_limit) {
            throw ValidationException::withMessages(['voucher' => 'Voucher không hợp lệ, hết hạn hoặc hết lượt.']);
        }
        $eligible = $v->shop_id ? ($groups[$v->shop_id] ?? 0) : array_sum($groups);
        if (! $eligible || $eligible < $v->min_order) {
            throw ValidationException::withMessages(['voucher' => 'Đơn chưa đạt giá trị tối thiểu hoặc không thuộc shop áp dụng.']);
        }
        $discount = $v->type === 'percent' ? intdiv($eligible * $v->value, 10000) : $v->value;
        $discount = min($eligible, $discount, $v->max_discount ?? PHP_INT_MAX);
        $allocations = array_fill_keys(array_keys($groups), 0);
        $remaining = $discount;
        $remainingBase = $eligible;
        foreach ($groups as $id => $amount) {
            if ($v->shop_id && $id != $v->shop_id) {
                continue;
            }
            $part = $remainingBase === $amount ? $remaining : intdiv($remaining * $amount, $remainingBase);
            $allocations[$id] = $part;
            $remaining -= $part;
            $remainingBase -= $amount;
        }

        return ['voucher' => $v, 'discount' => $discount, 'allocations' => $allocations];
    }
}
