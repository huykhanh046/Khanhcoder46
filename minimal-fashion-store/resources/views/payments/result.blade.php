<x-layout title="Kết quả thanh toán">
    <div class="auth-box">
        <h1 class="page-title">Trạng thái thanh toán.</h1>
        @if ($payment)
            <p>{{ config('labels.' . $payment->status) }}</p>
            <p class="muted my-5">Kết quả được xác nhận từ thông báo máy chủ VNPay. Nếu đang chờ, hãy tải lại chi tiết
                đơn hàng sau ít phút.</p><a class="btn" href="{{ route('orders.show', $payment->order_id) }}">Xem đơn
            hàng</a>@else<p>Không tìm thấy giao dịch thuộc tài khoản của bạn.</p><a class="text-link"
                href="/orders">Xem đơn hàng của tôi</a>
        @endif
    </div>
</x-layout>
