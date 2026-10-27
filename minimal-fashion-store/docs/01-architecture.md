# Kiến trúc và quyết định thiết kế

Trạng thái: kiến trúc đã triển khai; xem `progress.md` để biết mức triển khai và kiểm chứng.

Laravel 12 / PHP 8.4, MySQL 8.4 InnoDB, Blade, Tailwind 4, Alpine 3, Chart.js 4. Monolith MVC: HTTP → Form validation + Policies → Services → Eloquent → MySQL. Session/cache/queue dùng database, không bắt buộc Redis.

## Cấu trúc
- app/Http/Controllers: giao tiếp HTTP, validation, gọi service.
- app/Models và app/Policies: quan hệ và quyền sở hữu tài nguyên.
- app/Services: CartService, CheckoutService, InventoryService, OrderService, VnpayService, ReportService, VoucherService, ShippingService, ReviewService, ReturnService, AuditService.
- resources/views/components: layout/header/footer/product-card/product-grid/filter-sidebar/modal/form.
- config/store.php (bao gồm ảnh mặc định tập trung) và site_settings: tokens, thông tin cửa hàng, nội dung trang chủ.
- database/migrations, factories, seeders: schema/versioning/dữ liệu demo.
- tests/Feature: test HTTP, nghiệp vụ, quyền; tests/Concurrency: MySQL nhiều tiến trình.
- docs: phân tích, sơ đồ, cài đặt, kiểm thử, nghiệm thu theo giai đoạn.

## Dữ liệu
Vai trò cố định trong users.role (customer/seller/admin), kiểm tra bằng Gate và Policies; không cần hệ thống permissions động. Mỗi seller sở hữu tối đa một shop. products thuộc shop/category; product_variants có SKU unique, size/color unique theo product. inventories có stock và reserved, available = stock - reserved. Không lưu stock ở products.

orders là đơn tổng, shop_orders là đơn con; order_items giữ snapshot tên, SKU, size, màu, đơn giá. shipping_address là JSON snapshot. payments lưu yêu cầu thanh toán; payment_transactions là sổ thu/hoàn có khóa idempotency, phân bổ theo đơn con. returns dùng tên return_requests tránh từ khóa. Carts lưu MySQL và session chứa cart ID; merge khi login.

Tiền dùng unsigned BIGINT VND, không float. Giảm phần trăm dùng basis points (10000 = 100%), làm tròn xuống; phân bổ voucher theo tỷ lệ với phần dư phân bổ xác định. Phí vận chuyển theo phương thức và từng shop, quản trị tại /admin/shipping; ShippingService đọc site_settings và có giá mặc định từ config. shipping_method/shipping_label và số tiền được snapshot trong orders.

## Bất biến
1. 0 <= reserved <= stock. Lock inventory theo variant ID tăng dần; kiểm tra backend trong transaction.
2. Checkout reserve kho, VNPay hết hạn 15 phút. COD giữ đến giao/hủy. Giao hàng consume một lần; hủy release một lần. Mọi thay đổi ghi inventory_movements với event_key unique.
3. State machine: pending → confirmed → packing → shipping → delivered → completed; cancel chỉ pending/confirmed/packing. Customer chỉ hủy pending. Đơn online chưa trả tiền không được xác nhận.
4. Parent status: tất cả cancelled → cancelled; tất cả delivered/completed/cancelled → delivered/completed; còn lại processing hoặc pending.
5. IPN kiểm chữ ký, mã đơn, số tiền, currency, trạng thái; redirect không xác nhận thanh toán. Callback trùng không tạo giao dịch thứ hai. Callback đến sau hết hạn được ghi từ chối để đối soát.
6. Voucher khóa dòng, giới hạn lượt tính cả đơn đang giữ chỗ; hoàn lượt khi toàn bộ đơn hủy. Không tự giải phóng lượt khi chỉ hủy một shop để tránh tái sử dụng.
7. Doanh thu đã thu = tổng capture trừ refund trong ledger, theo ngày giao dịch. Đơn COD chưa thu không tính; hủy đơn đã trả tiền tạo nghĩa vụ hoàn, không giả lập hoàn tiền.
8. Seller luôn bị giới hạn shop cả trong truy vấn danh sách lẫn Policy thao tác từng tài nguyên.

## Thứ tự thực hiện
1. Phân tích, schema, routes dự kiến, các bất biến.
2. Bootstrap, migration, auth/role, layout, seeders.
3. Catalog/variants/filter/profile/cart.
4. Transaction checkout, voucher, split order, COD, IPN, expiry.
5. CRUD và moderation, seller scope, dashboard, site settings.
6. Review/return, automated tests, đối chiếu tài liệu, đóng gói.

## Nhóm routes
Public: GET /, /products, /products/{product}, /cart; POST /cart/items; PATCH/DELETE /cart/items/{item}; GET/POST /checkout (auth); GET /payments/return, /payments/ipn.
Auth: GET/POST /register, /login, /forgot-password, /reset-password; POST /logout; GET/PATCH /account; CRUD /addresses.
Customer: GET /orders, /orders/{order}; POST /orders/{order}/cancel; wishlist add/remove/list; POST /products/{product}/reviews; POST /order-items/{item}/returns.
Management: GET /manage; CRUD /manage/products; variants/images/inventory; GET /manage/orders, /manage/orders/{shopOrder}; POST transition/capture/refund; returns; shop application.
Admin: users/roles, shops/approve, product moderation, categories, vouchers, banners/settings, reviews, audit.

Danh mục routes chính xác ở `routes.md`, được sinh bằng artisan route:list.

## Các module hoàn thiện
- CollectionController: CRUD collections, ảnh bìa, active/position; products.collection_id là FK nullable. Xóa bộ sưu tập giữ sản phẩm. Migration chuyển tên collection cũ sang bảng mới trước khi bỏ cột chuỗi.
- VoucherAdminController/VoucherRequest: tìm kiếm, thêm/sửa/bật/tắt/xóa voucher chưa có lịch sử; khóa voucher khi sửa, không giảm usage_limit dưới used. Đơn đã đặt giữ snapshot giảm giá.
- FinanceController: danh sách payment, tổng thu/hoàn theo shop, hủy toàn đơn pending, lịch sử payment_callbacks; không tự chuyển tiền.
- ReturnService: state machine yêu cầu đổi/trả; restock chỉ received/resolved, parent lock + event_key unique + restocked_at chặn nhập hai lần. Refund là nghiệp vụ riêng.
- ReviewService: chỉ sản phẩm đã giao; khách sửa nội dung không thể gỡ trạng thái ẩn do Admin đặt.
- Auth: auth.session kiểm hash, đổi/reset mật khẩu thu hồi các session database cũ và xoay remember_token. ResetPasswordQueued gửi qua queue sau commit.

Không hủy từng đơn con của VNPay pending: số tiền gateway ứng với toàn đơn. Customer/Admin hủy cả đơn pending hoặc chờ hết hạn. Sau paid, các đơn con xử lý riêng và khoản thu cần hoàn được giữ trong ledger. COD paid so với tổng các đơn con còn hiệu lực, không sửa amount gốc. Đơn online đã hoàn tiền không tiếp tục xuất giao.
