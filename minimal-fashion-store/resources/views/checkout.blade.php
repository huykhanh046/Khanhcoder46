<x-layout title="Đặt hàng">
    <div class="container">
        <p class="breadcrumbs">Giỏ hàng → Thông tin giao hàng → Xác nhận</p>
        <h1 class="page-title">Hoàn tất đơn hàng.</h1>
        @php($saved = $addresses->first())
        @php($addressData = ['name' => old('name', $saved?->name ?? auth()->user()->name), 'phone' => old('phone', $saved?->phone ?? ''), 'address' => old('address', $saved?->address ?? ''), 'city' => old('city', $saved?->city ?? '')])
        <x-form action="/checkout" class="two-col" x-data="{ address: {{ Js::from($addressData) }}, shipping: {{ Js::from(old('shipping_method', 'standard')) }}, methods: {{ Js::from($shippingMethods) }}, shopCount: {{ count($quote['groups']) }}, subtotal: {{ $quote['subtotal'] - $quote['discount'] }}, money(value) { return new Intl.NumberFormat('vi-VN').format(value) + ' ₫' } }">
            <input type="hidden" name="checkout_key" value="{{ old('checkout_key', $key) }}">
            <section>
                <h2 class="text-xl mb-5">Thông tin nhận hàng</h2>
                @if ($addresses->count())
                    <div class="field"><label for="saved-address">Địa chỉ đã lưu</label><select id="saved-address"
                            @change="if($event.target.value)address=JSON.parse($event.target.value)">
                            <option value="">Chọn để điền địa chỉ</option>
                            @foreach ($addresses as $address)
                                <option
                                    value="{{ json_encode($address->only(['name', 'phone', 'address', 'city']), JSON_UNESCAPED_UNICODE) }}">
                                    {{ $address->address }}, {{ $address->city }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @foreach (['name' => 'Họ tên người nhận', 'phone' => 'Số điện thoại', 'address' => 'Số nhà, đường, phường/xã', 'city' => 'Tỉnh / thành phố'] as $name => $label)
                    <div class="field"><label for="{{ $name }}">{{ $label }}</label><input
                            id="{{ $name }}" name="{{ $name }}" x-model="address.{{ $name }}"
                            required maxlength="255" @if ($name === 'phone') inputmode="tel" @endif></div>
                @endforeach
                <h2 class="text-xl mt-8 mb-4">Phương thức vận chuyển</h2>
                @foreach ($shippingMethods as $method)
                    <label class="panel block mb-3 small"><input type="radio" name="shipping_method"
                            value="{{ $method['code'] }}" x-model="shipping"> {{ $method['label'] }} ·
                        {{ number_format($method['fee']) }} ₫/shop<br><span class="muted">Dự kiến
                            {{ $method['estimate'] }}</span></label>
                @endforeach
                <h2 class="text-xl mt-8 mb-4">Phương thức thanh toán</h2><label class="panel block mb-3 small"><input
                        type="radio" name="payment_method" value="cod" @checked(old('payment_method', 'cod') === 'cod')> Thanh toán khi
                    nhận hàng (COD)</label><label class="panel block small"><input type="radio" name="payment_method"
                        value="vnpay" @checked(old('payment_method') === 'vnpay' && $vnpay) @disabled(!$vnpay)> VNPay Sandbox
                    @if (!$vnpay)
                        <span class="muted">— Chưa cấu hình</span>
                    @endif
                </label>
                <p class="form-note">Đơn VNPay giữ chỗ {{ config('store.reservation_minutes') }} phút. Phí vận chuyển
                    tính riêng theo từng shop.</p>
            </section>
            <aside class="panel self-start">
                <h2>Đơn hàng của bạn</h2>
                @foreach ($quote['items'] as $item)
                    <div class="row small"><span>{{ $item->variant->product?->name ?? 'Sản phẩm ngừng bán' }}<br><span
                                class="muted">{{ $item->variant->size }} / {{ $item->variant->color }} ×
                                {{ $item->quantity }}</span></span><span>{{ number_format($item->quantity * $item->variant->price) }}
                            ₫</span></div>
                @endforeach
                <input type="hidden" name="voucher" value="{{ old('voucher', request('voucher')) }}">
                <div class="row small"><span>Giảm giá</span><span>−{{ number_format($quote['discount']) }} ₫</span>
                </div>
                <div class="row small"><span>Vận chuyển</span><span
                        x-text="money((methods[shipping]?.fee||methods.standard.fee)*shopCount)"></span></div>
                <div class="row total" aria-live="polite"><span>Tổng cộng</span><span
                        x-text="money(subtotal+(methods[shipping]?.fee||methods.standard.fee)*shopCount)"></span></div>
                <button type="submit" class="btn w-full mt-6">Xác nhận đặt hàng ↗</button>
            </aside>
        </x-form>
    </div>
</x-layout>
