<x-layout title="Cấu hình vận chuyển" :manage="true">
    <h1 class="page-title">Phương thức vận chuyển.</h1>
    <p class="muted">Tính phí một lần cho mỗi shop trong giỏ. Phí trên đơn đã đặt được lưu snapshot và không thay đổi.
    </p><x-form action="/admin/shipping" method="PUT" class="panel mt-6"><x-input name="standard_fee"
            label="Giao hàng tiêu chuẩn — phí/shop (₫)" type="number" min="1" max="1000000" :value="$settings['standard_fee'] ?? config('store.shipping_per_shop')"
            required /><x-input name="express_fee" label="Giao hàng nhanh — phí/shop (₫)" type="number" min="1"
            max="1000000" :value="$settings['express_fee'] ?? 50000" required /><button type="submit" class="btn">Lưu phí vận
            chuyển</button>
        <p class="form-note">Thời gian giao dự kiến; chưa tích hợp API hãng vận chuyển.</p>
    </x-form>
</x-layout>
