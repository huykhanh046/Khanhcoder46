<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ShippingService;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index(Request $r, CartService $cart)
    {
        $quote = $cart->quote($cart->current(), $r->query('voucher'));
        if (! $quote['items']->count() || count($quote['issues'])) {
            return redirect('/cart')->with('notice', 'Vui lòng kiểm tra lại giỏ hàng trước khi đặt.');
        }

        return view('checkout', ['shippingMethods' => app(ShippingService::class)->methods(), 'quote' => $quote, 'key' => (string) Str::uuid(), 'addresses' => $r->user()->addresses, 'vnpay' => app(VnpayService::class)->configured()]);
    }

    public function store(CheckoutRequest $r, CartService $cart, CheckoutService $checkout, VnpayService $vnpay)
    {
        $order = $checkout->place($cart->current(), $r->user()->id, $r->safe()->only(['name', 'phone', 'address', 'city']), $r->payment_method, $r->voucher, $r->checkout_key, $r->input('shipping_method', 'standard'));
        if ($order->payment_method === 'vnpay' && $order->payment->status === 'pending') {
            return redirect()->away($vnpay->url($order, $r->ip()));
        }

        return redirect()->route('orders.show', $order)->with('success', 'Đặt hàng thành công.');
    }
}
