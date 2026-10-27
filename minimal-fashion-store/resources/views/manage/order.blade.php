<x-layout title="Xử lý đơn hàng" :manage="true">
    <h1 class="page-title">Đơn #{{ $sub->id }} · {{ $sub->shop->name }}</h1>
    <p class="muted">Đơn tổng {{ $sub->order->number }} · {{ config('labels.' . $sub->status) }}</p>
    <div class="two-col">
        <section class="panel">
            <h2>Sản phẩm</h2>
            <table>
                <tr>
                    <th>Sản phẩm / SKU</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
                @foreach ($sub->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}<br>{{ $item->sku }} / {{ $item->size }} / {{ $item->color }}
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->total) }} ₫</td>
                    </tr>
                @endforeach
            </table>
            <div class="row total"><span>Tổng đơn con</span><span>{{ number_format($sub->total) }} ₫</span></div>
            <p class="small">Giảm giá {{ number_format($sub->discount) }} ₫ · Phí vận chuyển
                {{ number_format($sub->shipping) }} ₫</p>
            <h2 class="mt-8">Người nhận</h2>
            <p class="small">{{ $sub->order->shipping_address['name'] }} ·
                {{ $sub->order->shipping_address['phone'] }}<br>{{ $sub->order->shipping_address['address'] }},
                {{ $sub->order->shipping_address['city'] }}</p>
        </section>
        <aside class="stack">
            @if (count($next))
                <x-form :action="'/manage/orders/' . $sub->id . '/transition'" class="panel" confirm="Xác nhận chuyển trạng thái đơn hàng?">
                    <h2>Xử lý đơn</h2>
                    <div class="field"><label for="status">Chuyển đến</label><select name="status" id="status">
                            @foreach ($next as $status)
                                <option value="{{ $status }}">{{ config('labels.' . $status) }}</option>
                            @endforeach
                        </select>
                    </div><x-input name="tracking_number" label="Mã vận đơn" :value="$sub->tracking_number" /><button type="submit"
                        class="btn">Cập nhật trạng thái</button>
                </x-form>
            @endif @can('admin')
            <x-form :action="'/manage/orders/' . $sub->id . '/money'" class="panel"
                confirm="Chỉ ghi nhận khi đã kiểm tra giao dịch thu/hoàn thực tế. Bạn xác nhận?">
                <h2>Ghi nhận giao dịch thực tế</h2>
                <div class="field"><label for="type">Loại giao dịch</label><select name="type" id="type">
                        <option value="capture">Thu tiền COD</option>
                        <option value="refund">Hoàn tiền thủ công</option>
                    </select></div><x-input name="amount" label="Số tiền (₫)" type="number" :value="$sub->total"
                    required /><x-input name="reference" label="Mã chứng từ / đối soát duy nhất" required /><label
                    class="small"><input type="checkbox" name="confirmed" value="1" required> Đã kiểm tra tiền
                    thực nhận / thực hoàn</label><button type="submit" class="btn mt-4">Ghi nhận</button>
                <p class="form-note">Thao tác này ghi sổ đối soát; không gọi API hoàn tiền.</p>
            </x-form>
        @endcan
    </aside>
</div>
<section class="panel mt-8">
    <h2>Lịch sử thu / hoàn của đơn con</h2>
    @forelse($sub->transactions as $t)
        <div class="row small"><span>{{ config('labels.' . $t->type) }} ·
            {{ $t->provider_reference }}</span><span>{{ number_format($t->amount) }} ₫</span></div>@empty<p
            class="muted">Chưa có giao dịch.</p>
    @endforelse
</section>
</x-layout>
