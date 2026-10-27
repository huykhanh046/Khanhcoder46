<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\VoucherUsage;
use App\Notifications\OrderPlaced;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function place(Cart $cart, int $userId, array $address, string $method, ?string $code, string $key, string $shippingMethod = 'standard'): Order
    {
        return DB::transaction(function () use ($cart, $userId, $address, $method, $code, $key, $shippingMethod) {
            $cart = Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            abort_unless($cart->user_id === $userId, 403);
            if ($existing = Order::where('checkout_key', $key)->first()) {
                abort_unless($existing->user_id === $userId, 403);

                return $existing;
            }
            if (! in_array($method, ['cod', 'vnpay'])) {
                throw ValidationException::withMessages(['payment_method' => 'Phương thức không hợp lệ.']);
            }
            if ($method === 'vnpay' && ! app(VnpayService::class)->configured()) {
                throw ValidationException::withMessages(['payment_method' => 'VNPay Sandbox chưa được cấu hình. Vui lòng chọn COD.']);
            }
            $items = $cart->items()->orderBy('product_variant_id')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
            }
            $groups = [];
            $variants = [];
            foreach ($items as $item) {
                $v = ProductVariant::with('product')->where('active', true)->whereHas('product', fn ($q) => $q->published())->lockForUpdate()->find($item->product_variant_id);
                if (! $v) {
                    throw ValidationException::withMessages(['cart' => 'Một sản phẩm đã ngừng bán.']);
                }
                $variants[$v->id] = $v;
                $groups[$v->product->shop_id] = ($groups[$v->product->shop_id] ?? 0) + $v->price * $item->quantity;
            }
            ksort($groups);
            $voucher = $code ? app(VoucherService::class)->calculate($code, $groups) : null;
            $subtotal = array_sum($groups);
            $discount = $voucher['discount'] ?? 0;
            $shippingOption = app(ShippingService::class)->resolve($shippingMethod);
            $shipping = count($groups) * $shippingOption['fee'];
            $order = Order::create(['number' => (string) Str::uuid(), 'user_id' => $userId, 'checkout_key' => $key, 'status' => 'pending', 'payment_method' => $method, 'shipping_address' => $address, 'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping, 'shipping_method' => $shippingOption['code'], 'shipping_label' => $shippingOption['label'], 'total' => $subtotal - $discount + $shipping, 'expires_at' => $method === 'vnpay' ? now()->addMinutes(config('store.reservation_minutes')) : null]);
            $subs = [];
            foreach ($groups as $shopId => $amount) {
                $off = $voucher['allocations'][$shopId] ?? 0;
                $fee = $shippingOption['fee'];
                $subs[$shopId] = $order->shopOrders()->create(['shop_id' => $shopId, 'subtotal' => $amount, 'discount' => $off, 'shipping' => $fee, 'total' => $amount - $off + $fee]);
            }
            foreach ($items as $item) {
                $v = $variants[$item->product_variant_id];
                $sub = $subs[$v->product->shop_id];
                $line = $sub->items()->create(['product_variant_id' => $v->id, 'product_name' => $v->product->name, 'sku' => $v->sku, 'size' => $v->size, 'color' => $v->color, 'unit_price' => $v->price, 'quantity' => $item->quantity, 'total' => $v->price * $item->quantity]);
                app(InventoryService::class)->change($v->id, 0, $item->quantity, "reserve:{$line->id}", 'Giữ chỗ đơn hàng');
            }
            Payment::create(['order_id' => $order->id, 'reference' => (string) Str::uuid(), 'method' => $method, 'amount' => $order->total]);
            if ($voucher) {
                $voucher['voucher']->increment('used');
                VoucherUsage::create(['voucher_id' => $voucher['voucher']->id, 'order_id' => $order->id, 'user_id' => $userId]);
            }
            $cart->items()->delete();
            DB::afterCommit(function () use ($order) {
                try {
                    $order->user->notify(new OrderPlaced($order));
                } catch (\Throwable $e) {
                    Log::warning('Order notification failed', ['order_id' => $order->id, 'exception' => get_class($e)]);
                }
            });

            return $order;
        }, 3);
    }
}
