# SRS — MINIMAL FASHION STORE

## Mục tiêu và phạm vi
Đồ án cá nhân: thương mại điện tử thời trang đa shop, monolith dễ giải thích, giao diện tiếng Việt tách khỏi nghiệp vụ. Tác nhân Guest, Customer, Seller, Admin. Người có tài khoản Seller/Admin vẫn có thể mua hàng. Guest có catalog/giỏ hàng; checkout yêu cầu đăng nhập để truy vết đơn (guest checkout là tùy chọn không triển khai).

## Functional Requirements và truy vết
| ID | Yêu cầu | Triển khai |
|---|---|---|
| FR01 | Đăng ký/đăng nhập/đăng xuất/quên và reset mật khẩu | AuthController, Laravel Auth/Password broker |
| FR02 | Phân quyền và kiểm tra sở hữu | Gates manage/admin; ProductPolicy, ShopOrderPolicy, OrderPolicy |
| FR03 | Hồ sơ, đổi mật khẩu, CRUD địa chỉ | AccountController |
| FR04 | Catalog, filter giá/size/màu/category/shop/collection, sort và pagination | CatalogController |
| FR05 | Chi tiết ảnh, chọn biến thể đúng giá/kho, size guide | catalog/show + ProductVariant/Inventory |
| FR06 | Giỏ khách theo session, lưu MySQL, merge khi login | CartService, CartController |
| FR07 | Checkout COD, snapshot, split shop | CheckoutService |
| FR08 | Giữ kho, chống race, giải phóng/tiêu thụ một lần | InventoryService, OrderService, CHECK/unique |
| FR09 | Voucher percent/fixed, trần, min, hạn, giới hạn, scope | VoucherService, voucher_usages |
| FR10 | VNPay URL, IPN signed, amount, idempotency, expiry | VnpayService, orders:expire |
| FR11 | Lịch sử đơn, hủy có điều kiện, theo dõi trạng thái | OrderController, OrderService |
| FR12 | Wishlist, review đã giao, yêu cầu đổi/trả | AccountController, ManageController |
| FR13 | Đăng ký shop/chờ duyệt | shops, shop_applications, AdminController |
| FR14 | Product CRUD, soft delete, variants, upload ảnh, lịch sử tồn | ManageController, ProductRequest |
| FR15 | Kiểm duyệt sản phẩm/review/shop và phân quyền tài khoản | AdminController |
| FR16 | Quản lý trạng thái đơn con, vận đơn, ghi thu/hoàn có đối soát | OrderService, ManageController |
| FR17 | Dashboard ngày/tháng/shop, trạng thái đơn, top SKU, low stock | ReportService, Chart.js |
| FR18 | Cấu hình brand/tokens/menu/section/banner | site_settings, banners, admin/index |
| FR19 | Danh mục/voucher và giá giảm trực tiếp của biến thể | AdminController, product_variants |
| FR20 | Email xác nhận đơn và reset mật khẩu | OrderPlaced queue notification, ResetPasswordQueued |
| FR21 | Audit thao tác quan trọng | AuditService/audit_logs |

## Non-functional Requirements
- NFR01 Bảo mật: hash Laravel, CSRF POST/PATCH/DELETE, validation server, ORM parameter binding, Blade escape, rate limit auth/checkout/IPN, upload whitelist JPG/PNG/WebP 4 MB. SVG upload bị cấm.
- NFR02 Đúng dữ liệu: transactions + row locking InnoDB; amount integer VND; FK, unique, CHECK; snapshot đơn. Không dùng float cho business money.
- NFR03 Khả dụng local: Compose/MySQL + database queues; không bắt buộc Redis/SMTP/VNPay để demo COD.
- NFR04 Dễ sửa: service layer, Policies, Blade components, CSS variables, schema migrations. Không microservice.
- NFR05 UX: form errors/success, disabled khi submit, empty, confirmation, menu mobile, focus/labels/dialog bàn phím. Có kiểm tra browser ở desktop và 390 px; chưa audit WCAG đầy đủ.
- NFR06 Hiệu năng: eager loading chính, phân trang catalog/admin, index theo shop/status/ngày và SKU. Chưa có load test quy mô lớn/SLA; không tuyên bố throughput.
- NFR07 Vận hành: queue retry, scheduled expiry, logs, backup/restore hướng dẫn, env secrets. Chưa kiểm chứng SMTP/hosting thật.

## Use Cases quan trọng
### UC01 Đặt COD
Tác nhân Customer. Tiền điều kiện: giỏ có biến thể đang bán, shop approved, đủ tồn. Người dùng nhập/chọn địa chỉ, xác nhận COD. Controller validate → checkout lock cart → kiểm idempotency → đọc/khóa variants → voucher lock → tạo order/suborders/snapshot → inventory lock/reserve → payment pending → xóa cart items → commit → queue email. Kết quả: đơn persisted, kho reserved; chưa ghi doanh thu. Hết hàng/voucher sai rollback toàn bộ và hiển thị lỗi. Retry cùng checkout_key trả lại đơn cũ.

### UC02 Thanh toán VNPay
Customer chọn online khi đã cấu hình. Backend tạo URL từ payment.reference/amount; gateway gọi signed IPN. Service lock order, verify hash/amount/state/expiry; success ghi capture theo shop và paid; fail hủy pending suborders và release. Browser Return chỉ hiển thị DB. Callback trùng không nhân đôi giao dịch; IPN sau hết hạn trả 02, ghi rejected/late để đối soát.

### UC03 Hai khách tranh chiếc cuối
Hai cart của hai user có cùng variant stock=1. Hai transaction độc lập khóa dòng variant/inventory. Transaction đầu reserve=1 và commit; transaction sau đọc reserved mới, validation failure, rollback. Bài test pcntl tạo hai process và MySQL connections có barrier, assert 1 order/1 reserved/1 failure.

### UC04 Seller xử lý đơn
Seller chỉ query shop của mình. Policy kiểm owner+role+shop approved trên detail/update. Trạng thái theo NEXT, không nhảy bước. VNPay cần capture đủ cho đơn con trước confirmed. Delivered consume stock/reserved. Hủy trước shipping release. Parent tổng hợp sau mỗi chuyển đổi.

### UC05 Hủy đơn
Customer chỉ hủy khi tất cả suborders pending. Lock parent; release mỗi dòng theo event key; inventory_status chặn release lần hai; giải phóng voucher khi toàn bộ đơn cancelled. Nếu có tiền đã thu, giữ ledger và chờ đối soát hoàn tiền, không tự tạo refund.

### UC06 Đánh giá và đổi/trả
Chủ đơn delivered/completed mới review sản phẩm; unique user/product, rating 1–5. Return trong 14 ngày giao; unique order_item; requested → approved/rejected → received → resolved. Nhập lại kho bằng ReturnService.restock sau kiểm chất lượng, mỗi yêu cầu một lần; refund cần Admin nhập mã chứng từ và xác nhận thực tế. Đổi hàng được ghi nhận quy trình thủ công trong response, chưa tự tạo đơn thay thế.

### UC07 Quản trị giao diện
Admin sửa tokens/brand/menu/section/banner trong DB. Layout đọc settings theo request, CSS variables đổi presentation. Order/cart/payment services không phụ thuộc CSS/layout.

## Quy tắc nghiệp vụ
- shipping mặc định tiêu chuẩn 30.000 ₫/shop, nhanh 50.000 ₫/shop; Admin chỉnh tại /admin/shipping. Checkout tính lại ở backend và snapshot phương thức/phí. Không tích hợp hãng vận chuyển.
- discount percent dùng basis points (1000=10%), intdiv; scope shop hoặc tất cả; phân bổ theo tỷ lệ và phần dư xác định. Lượt voucher giữ ngay checkout, release chỉ toàn bộ đơn cancelled.
- stock vật lý giảm khi delivered; reserved tăng checkout, giảm cancel/expiry/delivered. COD không tự hết hạn; admin xử lý đơn tồn.
- capture − refund là doanh thu đã thu theo transaction date, tách theo shop. Đơn chưa thu dù completed không được cộng. Refund chỉ ghi sau xác nhận thực tế.
- shop_orders có trạng thái độc lập; orders summary không thay quyền truy cập.

## Giới hạn được công khai
Không có guest checkout; không có API hoàn tiền VNPay tự động, tích hợp shipping, mô hình permissions tùy biến hay promotion scheduler riêng. Collections có bảng, CRUD và ảnh bìa riêng; sale price là giá biến thể, compare_price là giá tham chiếu. Notifications table dự phòng cho database channel; hiện gửi email qua queue. Không dùng các phần dự phòng này làm bằng chứng chức năng đã hoàn thành.

### UC08 Quản lý bộ sưu tập và voucher
Admin tạo/sửa/xóa nhóm sản phẩm, upload ảnh bìa và sắp xếp hiển thị. Seller gán sản phẩm vào nhóm có sẵn. Voucher đã dùng chỉ tắt, không xóa lịch sử; thay giá trị không sửa đơn đã đặt.

### UC09 Đối soát và nhập hàng trả
Admin xem /admin/payments, lọc trạng thái/phương thức/ngày, đọc lịch sử IPN. Với hàng trả đã nhận và kiểm tra, Seller của shop hoặc Admin xác nhận nhập lại qua /manage/returns. Hai thao tác đồng thời chỉ tạo một inventory movement. Thu/hoàn tiền vẫn cần chứng từ và xác nhận riêng.
