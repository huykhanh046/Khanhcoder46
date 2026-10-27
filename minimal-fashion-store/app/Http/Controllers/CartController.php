<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $r, CartService $service)
    {
        $code = $r->query('voucher');
        $quote = $service->quote($service->current(), $code);

        return view('cart', compact('quote', 'code'));
    }

    public function store(Request $r, CartService $service)
    {
        $d = $r->validate(['product_variant_id' => 'required|integer|exists:product_variants,id', 'quantity' => 'required|integer|min:1|max:99']);
        $service->add($service->current(), $d['product_variant_id'], $d['quantity']);

        return redirect('/cart')->with('success', 'Đã thêm vào giỏ hàng.');
    }

    public function update(Request $r, CartItem $item, CartService $service)
    {
        abort_unless($item->cart_id === $service->current()->id, 403);
        $r->validate(['quantity' => 'required|integer|min:1|max:99']);
        $service->add($service->current(), $item->product_variant_id, $r->integer('quantity'), true);

        return back()->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function destroy(CartItem $item, CartService $service)
    {
        abort_unless($item->cart_id === $service->current()->id, 403);
        DB::transaction(function () use ($item) {
            Cart::whereKey($item->cart_id)->lockForUpdate()->firstOrFail();
            $item->delete();
        });

        return back()->with('success', 'Đã xóa sản phẩm.');
    }
}
