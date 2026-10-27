# Báo cáo triển khai theo giai đoạn

Bản bàn giao là ứng dụng chạy local với dữ liệu MySQL thực, không phải UI mock. Các kết quả bên dưới dựa trên lệnh và trình duyệt đã chạy; xem log trong docs/evidence. Chưa triển khai production và chưa xác minh merchant VNPay/SMTP thật.

## Giai đoạn 1 — Phân tích và kiến trúc
- Đã làm: MVC + Service Layer, quy tắc kho/tiền/voucher/đơn con, SRS FR/NFR và use cases; ERD, kiến trúc, class, activity, sequence và state machine.
- File chính: docs/01-architecture.md, 02-srs.md, 03-diagrams.md, erd.mmd, schema-dictionary.md, routes.md.
- Kiểm tra: xuất schema từ 36 bảng MySQL thực; routes từ artisan; 10 sơ đồ vượt qua Mermaid parser.
- Giới hạn: sơ đồ use case dùng flowchart Mermaid, không phải ký pháp UML ellipse nguyên bản.
- Bước kế tiếp đã thực hiện: dựng Laravel/schema/auth.

## Giai đoạn 2 — Khởi tạo nền tảng
- Đã làm: Laravel 12, PHP 8.4, MySQL 8.4, auth/password broker, Gates/Policies, migrations, factories, seeders, Blade components và design tokens.
- File chính: composer.json/lock, compose.yaml, Dockerfile, database/migrations, database/factories, DatabaseSeeder, app/Policies, AppServiceProvider, resources/views/components.
- Lệnh: composer install; php artisan key:generate; php artisan migrate; php artisan db:seed; php artisan storage:link; npm ci; npm run build.
- Kiểm tra: migration/seed chạy thành công; seeder regression với ID khởi đầu 1000 tạo 40 products/240 variants/18 orders, cả 2 shop có capture, 0 lệch ledger kho. Auth/reset/role được kiểm thử.
- Giới hạn: ảnh SVG minh họa; tài khoản demo chỉ local; không có RBAC động (3 vai trò cố định).
- Bước kế tiếp đã thực hiện: catalog/tài khoản/cart.

## Giai đoạn 3 — Sản phẩm và khách hàng
- Đã làm: catalog/filter/sort/pagination, detail với size/color/stock/price chính xác, hồ sơ/password/địa chỉ CRUD, wishlist, giỏ MySQL cho guest và merge khi login.
- File chính: CatalogController, AccountController, CartController, CartService; views/catalog, cart.blade.php, account.blade.php.
- Kiểm tra: HTTP feature tests; Chromium chọn biến thể, add cart, login, checkout; refresh dữ liệu vẫn tồn tại.
- Giới hạn: checkout cần login; guest checkout là tùy chọn chưa triển khai. Hai phương thức tiêu chuẩn/nhanh, phí/shop chỉnh ở Admin.
- Bước kế tiếp đã thực hiện: checkout/kho/payment.

## Giai đoạn 4 — Đặt hàng và thanh toán
- Đã làm: server pricing, integer VND, idempotent checkout, order/shop_orders/snapshot, voucher scope/limits, row locks/reserve/expire/release/consume, COD, VNPay signed URL/IPN/return-read-only.
- File chính: CheckoutRequest; CheckoutService, InventoryService, VoucherService, OrderService, VnpayService; OrderPlaced; routes/console.php.
- Kiểm tra: PHPUnit tính giá/voucher/split/payment signature/amount/replay/failure/expiry/cancel/refund; test race 2 process MySQL độc lập cho 1 chiếc, chỉ 1 order và reserved=1.
- Lệnh: php artisan test; DB_DATABASE=minimal_concurrency ... php tests/Concurrency/run.php; php artisan orders:expire; php artisan queue:work --stop-when-empty.
- Giới hạn: VNPay chỉ test logic bằng payload ký local; chưa có merchant secret để thử gateway thật. Refund là ghi nhận thủ công sau xác nhận thực tế, không gọi API chuyển tiền. Email đã kiểm SMTP nội bộ Mailpit và queue, chưa gửi tới Internet.
- Bước kế tiếp đã thực hiện: Seller/Admin/dashboard.

## Giai đoạn 5 — Seller và Admin
- Đã làm: CRUD/soft delete products, variants/SKU/giá/ảnh/tồn, moderation, scope Seller, state machine đơn con, tracking, manual capture/refund idempotent, dashboard thu−hoàn theo ngày/tháng/shop, top SKU và low stock; users/shop/category/voucher/banner/settings/review/audit.
- File chính: ManageController, AdminController, ProductRequest, ReportService, AuditService, views/manage, views/admin, config/store.php, resources/css/app.css.
- Kiểm tra: Seller shop khác nhận 403, khách bị chặn manage; các trang quản trị HTTP 200; Chart.js render thật; Admin xử lý COD đến giao/hoàn tất và ghi thu demo qua UI.
- Giới hạn: collections có CRUD, ảnh bìa và FK riêng; sale price quản lý trực tiếp trên variant, chưa có promotion scheduler riêng. Báo cáo có filter kỳ nhưng chưa export Excel/PDF (không bắt buộc).
- Bước kế tiếp đã thực hiện: review/return, QA và tài liệu.

## Giai đoạn 6 — Hoàn thiện
- Đã làm: review sau giao, return window/ownership/state, thông báo lỗi/success/empty/loading/confirm, mobile menu, docs cài đặt/deploy/demo, chuẩn hóa PHP, scripts kiểm thử và schema export.
- File chính: tests/Feature/CommerceTest.php, tests/Concurrency/run.php, tests/Seed/run.php, docs/test-plan.md, docs/verification.md, docs/installation.md, docs/deployment.md, docs/demo.md.
- Kiểm tra cuối: 41 feature tests / 213 assertions đạt; 5 tình huống race đạt; seeder regression đạt; Vite build, PHP syntax, route cache, Blade cache, Compose config đạt; browser desktop/mobile có 24 checks, 0 pageerrors; 10 diagrams parse được.
- Giới hạn: chưa load test, chưa audit WCAG toàn diện/Safari/Firefox; chưa triển khai hosting production; ảnh và thông tin cửa hàng còn là mẫu. Quy trình đổi hàng xử lý thủ công, chưa tự tạo đơn thay thế.
- Bước tiếp theo khi vận hành thật: thay dữ liệu/ảnh, cấu hình merchant sandbox và SMTP, chạy checklist tích hợp trong docs/vnpay.md, staging HTTPS/worker/cron, kiểm tra backup/restore và accessibility.

## Những gì không được tuyên bố đã hoàn thành
Không có giao dịch VNPay thật được xác nhận trong phiên; email chỉ gửi SMTP nội bộ Mailpit, chưa tới hộp thư Internet; không tự hoàn tiền; không có URL production đã deploy. Toàn bộ seed/capture trong demo chỉ là dữ liệu thử nghiệm. Không gọi ứng dụng là đã đủ điều kiện vận hành production chỉ từ các test local.

## Hoàn thiện bổ sung
- Voucher CRUD đầy đủ và ràng buộc lịch sử; bộ sưu tập chuẩn hóa với migration chuyển dữ liệu cũ; cấu hình hai loại vận chuyển và snapshot; màn đối soát payment/IPN.
- Chặn hủy lẻ VNPay đang pending, chặn xuất giao đơn đã hoàn tiền; COD paid so với các shop còn hiệu lực; khóa và idempotency giữ sổ thu/hoàn nhất quán.
- ReturnService nhập lại kho một lần; ReviewService giữ moderation khi khách sửa; giỏ hết hàng có cảnh báo; thu hồi phiên khi đổi/reset mật khẩu; reset email queued.
- File chính mới: CollectionController, VoucherAdminController, FinanceController, VoucherRequest, ShippingService, ReturnService, ReviewService, ResetPasswordQueued, Collection, PaymentCallback; migration 2026_10_27_000001; CompletionTest và tests/Browser/smoke.py.
- Kiểm chứng bổ sung: cài Docker sạch, SMTP sink và reset từ email, backup/restore DB riêng. Kết quả và lệnh tái hiện đầy đủ ở verification.md, installation.md, test-plan.md.
- Việc còn cần từ chủ dự án trước vận hành: merchant VNPay Sandbox, SMTP Internet, nội dung/ảnh kinh doanh thật và máy chủ HTTPS. Không còn lỗi nghiêm trọng đã biết trong các luồng local đã kiểm thử; đây không phải bảo đảm mọi môi trường đều không lỗi.
