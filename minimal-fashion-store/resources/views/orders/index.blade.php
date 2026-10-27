<x-layout title="Đơn hàng">
    <div class="container">
        <h1 class="page-title">Đơn hàng của bạn.</h1>
        <div class="table-wrap mt-8">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Ngày đặt</th>
                        <th>Trạng thái</th>
                        <th>Tổng tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ substr($order->number, 0, 8) }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ config('labels.' . $order->status) }}</td>
                            <td>{{ number_format($order->total) }} ₫</td>
                            <td><a class="text-link" href="{{ route('orders.show', $order) }}">Xem chi tiết</a></td>
                    </tr>@empty<tr>
                            <td colspan="5">Chưa có đơn hàng. <a href="/products">Khám phá sản phẩm →</a></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div><x-pagination :items="$orders" />
    </div>
</x-layout>
