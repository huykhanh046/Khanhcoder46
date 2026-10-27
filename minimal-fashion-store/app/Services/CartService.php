<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function current(): Cart
    {
        if (auth()->check()) {
            return Cart::firstOrCreate(['user_id' => auth()->id()], ['token' => (string) Str::uuid()]);
        }
        $cart = session('cart_id') ? Cart::whereNull('user_id')->find(session('cart_id')) : null;
        if (! $cart) {
            $cart = Cart::create(['token' => (string) Str::uuid()]);
            session(['cart_id' => $cart->id]);
        }

        return $cart;
    }

    public function merge(): void
    {
        $guestId = session()->pull('cart_id');
        if (! $guestId) {
            return;
        }
        DB::transaction(function () use ($guestId) {
            $target = $this->current();
            $guest = Cart::whereNull('user_id')->lockForUpdate()->find($guestId);
            if (! $guest) {
                return;
            }
            foreach ($guest->items as $item) {
                try {
                    $this->add($target, $item->product_variant_id, $item->quantity);
                } catch (ValidationException|ModelNotFoundException $e) {
                    session()->flash('notice', 'Một số sản phẩm không được gộp vì không còn đủ hàng hoặc đã ngừng bán.');
                }
            }
            $guest->delete();
        }, 3);
    }

    public function add(Cart $cart, int $variantId, int $quantity, bool $replace = false): void
    {
        DB::transaction(function () use ($cart, $variantId, $quantity, $replace) {
            Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $variant = ProductVariant::with('inventory')->where('active', true)->whereHas('product', fn ($q) => $q->published())->findOrFail($variantId);
            $item = $cart->items()->where('product_variant_id', $variantId)->first();
            $count = $quantity + ($replace ? 0 : ($item?->quantity ?? 0));
            if ($count < 1 || $count > 99 || $count > $variant->inventory->stock - $variant->inventory->reserved) {
                throw ValidationException::withMessages(['quantity' => 'Số lượng vượt tồn kho khả dụng (tối đa 99).']);
            }
            $cart->items()->updateOrCreate(['product_variant_id' => $variantId], ['quantity' => $count]);
        }, 3);
    }

    public function quote(Cart $cart, ?string $code = null, string $shippingMethod = 'standard'): array
    {
        $items = $cart->items()->with(['variant.product.shop', 'variant.inventory'])->get();
        $groups = [];
        $issues = [];
        foreach ($items as $item) {
            $product = $item->variant->product;
            if (! $product || ! $product->active || $product->status !== 'approved' || $product->shop->status !== 'approved' || ! $item->variant->active || ! $item->variant->inventory || $item->quantity > $item->variant->inventory->stock - $item->variant->inventory->reserved) {
                $issues[] = ($product?->name ?? $item->variant->sku).': đã ngừng bán hoặc không đủ số lượng. Vui lòng sửa/xóa khỏi giỏ.';
            }
            if (! $item->variant->product) {
                continue;
            }
            $id = $item->variant->product->shop_id;
            $groups[$id] = ($groups[$id] ?? 0) + $item->quantity * $item->variant->price;
        }
        $subtotal = array_sum($groups);
        $discount = 0;
        if ($code && $subtotal) {
            $discount = app(VoucherService::class)->calculate($code, $groups, false)['discount'];
        }
        $shippingOption = app(ShippingService::class)->resolve($shippingMethod);
        $shipping = count($groups) * $shippingOption['fee'];

        return compact('items', 'groups', 'subtotal', 'discount', 'shipping', 'issues') + ['total' => $subtotal - $discount + $shipping];
    }
}
