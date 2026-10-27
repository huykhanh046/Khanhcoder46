<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <h3>{{ $site['brand']['name'] ?? config('store.name') }}</h3>
                <p>{{ $site['brand']['slogan'] ?? 'Ít hơn, nhưng tốt hơn. Những thiết kế đồng hành cùng bạn mỗi ngày.' }}
                </p>
            </div>
            <div>
                <h3>KHÁM PHÁ</h3><a href="/products">Tất cả sản phẩm</a><a href="/orders">Theo dõi đơn hàng</a><a
                    href="/account">Tài khoản & đăng ký shop</a>
                @can('manage')
                    <a href="/manage">Quản lý cửa hàng</a>
                @endcan
            </div>
            <div>
                <h3>LIÊN HỆ & CHÍNH SÁCH</h3>
                <p>{{ $site['brand']['email'] ?? '' }}<br>{{ $site['brand']['phone'] ?? '' }}<br>{{ $site['brand']['address'] ?? '' }}
                </p>
                <details>
                    <summary>Giao hàng & đổi trả</summary>
                    <p>Phí giao hàng
                        {{ number_format(app(\App\Services\ShippingService::class)->resolve('standard')['fee']) }} ₫ /
                        shop. Đề nghị đổi/trả
                        trong {{ config('store.return_days') }} ngày sau giao hàng; shop xét duyệt theo tình trạng thực
                        tế. Hoàn tiền được xác nhận riêng.</p>
                </details>
                <details>
                    <summary>Bảo mật thông tin</summary>
                    <p>Địa chỉ và thông tin liên hệ được sử dụng để xử lý đơn hàng. Liên hệ cửa hàng để yêu cầu hỗ trợ
                        dữ liệu cá nhân.</p>
                </details>
            </div>
        </div>
        <div class="footer-bottom"><span>© {{ date('Y') }}
                {{ $site['brand']['name'] ?? config('store.name') }}</span><span>Thiết kế tinh giản. Mua sắm dễ
                dàng.</span></div>
    </div>
</footer>
