<x-layout title="Chi tiết đơn hàng">
    <div class="container">
        <p class="breadcrumbs"><a href="/orders">Đơn hàng</a> / {{ substr($order->number, 0, 8) }}</p>
        <h1 class="page-title">Cảm ơn bạn đã lựa chọn.</h1>
        <p class="muted">Mã đơn: {{ $order->number }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
        <div class="two-col">
            <div class="stack">
                @foreach ($order->shopOrders as $sub)
                    <section class="panel">
                        <div class="row">
                            <h2>{{ $sub->shop->name }}</h2><span
                                class="status">{{ config('labels.' . $sub->status) }}</span>
                        </div>
                        @if ($sub->tracking_number)
                            <p class="small">Mã vận đơn: {{ $sub->tracking_number }}</p>
                            @endif @foreach ($sub->items as $item)
                                <div class="row">
                                    <div>
                                        <p>{{ $item->product_name }}</p>
                                        <p class="muted">{{ $item->size }} / {{ $item->color }} ·
                                            {{ $item->sku }} × {{ $item->quantity }}</p>
                                    </div><span class="small">{{ number_format($item->total) }} ₫</span>
                                </div>
                                @if ($item->returnRequest)
                                    <p class="notice">Yêu cầu đổi/trả:
                                        {{ config('labels.' . $item->returnRequest->status) }} ·
                                        {{ $item->returnRequest->response }}</p>
                                @elseif(in_array($sub->status, ['delivered', 'completed']))
                                    <details>
                                        <summary>Gửi yêu cầu đổi/trả</summary><x-form :action="'/order-items/' . $item->id . '/returns'" class="mt-4">
                                            <div class="field"><label for="return-type-{{ $item->id }}">Loại yêu
                                                    cầu</label><select id="return-type-{{ $item->id }}"
                                                    name="type">
                                                    <option value="return">Trả hàng</option>
                                                    <option value="exchange">Đổi hàng</option>
                                                </select></div><x-input name="reason" :id="'reason-' . $item->id" label="Lý do"
                                                required /><button type="submit" class="btn small">Gửi yêu cầu</button>
                                        </x-form>
                                    </details>
                                @endif
                            @endforeach
                            <div class="row total">
                                <span>Tổng shop</span><span>{{ number_format($sub->total) }} ₫</span>
                            </div>
                            <p class="form-note">Giảm {{ number_format($sub->discount) }} ₫ · Vận chuyển
                                {{ number_format($sub->shipping) }} ₫</p>
                    </section>
                @endforeach
            </div>
            <aside class="panel self-start">
                <h2>Thông tin đơn hàng</h2>
                <p class="small mb-3">{{ $order->shipping_label }}</p>
                <p class="small">{{ $order->shipping_address['name'] }} ·
                    {{ $order->shipping_address['phone'] }}<br>{{ $order->shipping_address['address'] }},
                    {{ $order->shipping_address['city'] }}</p>
                <div class="divider"></div>
                <p class="small">{{ config('labels.' . $order->payment_method) }}<br>Trạng thái thanh toán:
                    <strong>{{ config('labels.' . $order->payment->status) }}</strong>
                </p>
                <div class="row total"><span>Tổng cộng</span><span>{{ number_format($order->total) }} ₫</span></div>
                @if (
                    $order->payment_method === 'vnpay' &&
                        $order->payment->status === 'pending' &&
                        $order->status !== 'cancelled' &&
                        $order->expires_at?->isFuture())
                    <x-form :action="'/orders/' . $order->id . '/pay'"><button type="submit" class="btn w-full mt-5">Tiếp tục thanh toán
                            VNPay</button></x-form>
                    @endif @if ($order->shopOrders->every(fn($s) => $s->status === 'pending'))
                        <x-form :action="'/orders/' . $order->id . '/cancel'" confirm="Bạn muốn hủy toàn bộ đơn hàng?"><button type="submit"
                                class="btn secondary w-full mt-4">Hủy đơn hàng</button></x-form>
                    @endif
                    <div class="divider">
                    </div>
                    <h2>Lịch sử giao dịch</h2>
                    @forelse($order->payment->transactions as $transaction)
                        <p class="small">{{ $transaction->created_at->format('d/m H:i') }} ·
                            {{ config('labels.' . $transaction->type, $transaction->type) }} ·
                        {{ number_format($transaction->amount) }} ₫</p>@empty<p class="muted">Chưa có giao dịch
                            thu tiền.</p>
                    @endforelse
            </aside>
        </div>
    </div>
</x-layout>
