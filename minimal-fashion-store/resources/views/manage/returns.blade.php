<x-layout title="Đổi trả" :manage="true">
    <h1 class="page-title">Yêu cầu đổi / trả.</h1>
    <div class="stack mt-8">
        @forelse($returns as $ret)
            <section class="panel">
                <h2>#{{ $ret->id }} · {{ $ret->item->product_name }}</h2>
                <p class="small">{{ $ret->user->name }} · {{ $ret->item->shopOrder->shop->name }} ·
                    {{ $ret->type === 'return' ? 'Trả hàng' : 'Đổi hàng' }} · {{ config('labels.' . $ret->status) }}</p>
                <p class="my-4">{{ $ret->reason }}</p>
                <p class="muted">{{ $ret->response }}</p>
                @if (!in_array($ret->status, ['resolved', 'rejected']))
                    <x-form :action="'/manage/returns/' . $ret->id" method="PATCH">
                        <div class="field"><label for="status-{{ $ret->id }}">Trạng thái tiếp theo</label><select
                                name="status" id="status-{{ $ret->id }}">
                                @foreach (['requested' => ['approved', 'rejected'], 'approved' => ['received'], 'received' => ['resolved']][$ret->status] as $status)
                                    <option value="{{ $status }}">{{ config('labels.' . $status) }}</option>
                                @endforeach
                            </select>
                        </div><x-input name="response" :id="'response-' . $ret->id" label="Phản hồi / thông tin xử lý"
                            required /><button type="submit" class="btn">Cập nhật</button>
                    </x-form>
                @endif
                @if ($ret->restocked_at)
                    <p class="notice">Đã nhập lại kho lúc {{ $ret->restocked_at->format('d/m/Y H:i') }}.</p>
                @elseif(in_array($ret->status, ['received', 'resolved']))
                    <x-form :action="'/manage/returns/' . $ret->id . '/restock'" class="mt-5"
                        confirm="Xác nhận đã kiểm tra chất lượng và nhập lại toàn bộ số lượng hàng trả?"><label
                            class="small"><input type="checkbox" name="confirmed" value="1" required> Hàng đã kiểm
                            tra và đủ điều kiện bán lại</label><button type="submit" class="btn small secondary">Nhập
                            lại kho {{ $ret->item->quantity }} sản phẩm</button></x-form>
                @endif
                <p class="form-note">Nhập lại kho chỉ ghi một lần cho yêu cầu này. Tiền hoàn phải đối soát và ghi nhận
                    riêng tại đơn con. Với đổi hàng, ghi thông tin giao thay thế trong phản hồi.</p>
        </section>@empty<div class="empty">Chưa có yêu cầu đổi/trả.</div>
        @endforelse
    </div><x-pagination :items="$returns" />
</x-layout>
