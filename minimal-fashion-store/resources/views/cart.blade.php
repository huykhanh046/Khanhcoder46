<x-layout title="Giỏ hàng">
    <div class="container">
        <h1 class="page-title">Giỏ hàng của bạn.</h1>
        @if (!$quote['items']->count())
            <div class="empty mt-8">
                <p class="mb-5">Giỏ hàng đang trống. Tìm món đồ yêu thích đầu tiên của bạn.</p><a href="/products"
                    class="btn">Khám phá sản phẩm ↗</a>
        </div>@else<div class="two-col">
                <div>
                    @foreach ($quote['items'] as $item)
                        <div class="row border-b border-gray-200"><img class="cart-thumb"
                                src="{{ $item->variant->product?->images->first()?->path ?? config('store.images.product') }}"
                                alt="{{ $item->variant->product?->name ?? 'Sản phẩm ngừng bán' }}">
                            <div class="flex-1">
                                <h2>{{ $item->variant->product?->name ?? 'Sản phẩm ngừng bán' }}</h2>
                                <p class="muted">{{ $item->variant->size }} / {{ $item->variant->color }} ·
                                    {{ number_format($item->variant->price) }} ₫</p>
                                <div class="inline mt-3"><x-form :action="'/cart/items/' . $item->id" method="PATCH" class="inline"><label
                                            class="sr-only" for="qty-{{ $item->id }}">Số lượng</label><input
                                            id="qty-{{ $item->id }}" type="number" name="quantity"
                                            value="{{ $item->quantity }}" min="1" max="99" class="control"
                                            style="width:70px"><button type="submit" class="btn small secondary">Cập
                                            nhật</button></x-form><x-form :action="'/cart/items/' . $item->id" method="DELETE"
                                        confirm="Xóa sản phẩm khỏi giỏ hàng?"><button type="submit"
                                            class="text-link">Xóa</button></x-form></div>
                            </div><span class="small">{{ number_format($item->quantity * $item->variant->price) }}
                                ₫</span>
                        </div>
                    @endforeach
                </div>
                <aside class="panel">
                    <h2>Thông tin thanh toán</h2>
                    @foreach ($quote['issues'] as $issue)
                        <p class="notice errors">{{ $issue }}</p>
                    @endforeach
                    <form action="/cart"><x-input name="voucher" label="Mã ưu đãi" :value="$code" /><button
                            type="submit" class="btn small secondary">Áp dụng</button></form>
                    <div class="row small"><span>Tạm tính</span><span>{{ number_format($quote['subtotal']) }} ₫</span>
                    </div>
                    <div class="row small"><span>Giảm giá</span><span>−{{ number_format($quote['discount']) }} ₫</span>
                    </div>
                    <div class="row small"><span>Vận chuyển ({{ count($quote['groups']) }}
                            shop)</span><span>{{ number_format($quote['shipping']) }} ₫</span></div>
                    <div class="row total"><span>Tổng cộng</span><span>{{ number_format($quote['total']) }} ₫</span>
                    </div>
                    @unless (count($quote['issues']))
                        <a href="/checkout?{{ http_build_query(['voucher' => $code]) }}" class="btn w-full mt-5">Tiến
                            hành đặt hàng ↗</a>
                    @endunless
                    <p class="form-note">Giá và tồn kho được kiểm tra lại khi xác nhận đơn. Đăng nhập để theo dõi đơn
                        hàng.</p>
                </aside>
            </div>
        @endif
    </div>
</x-layout>
