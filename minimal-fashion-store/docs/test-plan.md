# Test Plan

## Phạm vi và chiến lược
PHPUnit feature trên MySQL 8.4, không SQLite. RefreshDatabase dùng `minimal_test`; race dùng database riêng `_concurrency` và pcntl. Smoke test bằng Chromium có JS thật, session/CSRF thật, desktop 1440 và mobile 390 px. Build Vite và compile Blade kiểm tính hợp lệ tài nguyên/template.

## Test cases
| Nhóm | Tình huống | Kết quả mong đợi |
|---|---|---|
| Auth | Register chèn role=admin | Luôn customer, password hash |
| Auth | Login/logout; reset token sai/đúng | Đúng session, token sai bị chặn, hash thay đổi |
| Catalog | size/màu/giá không khớp | Empty state; không trộn giá/kho hai variants |
| Cart | Khách theo session, merge login, sửa cart của người khác | MySQL tồn tại, merge hợp lệ, 403 sở hữu |
| Cart | Quantity vượt stock, giá bị sửa sau thêm | Backend reject kho; checkout dùng giá mới |
| Product | Seller tạo/sửa/xóa, inject shop_id, variant trùng | Own shop; pending; soft delete; unique bị chặn |
| Upload | SVG hoặc shop khác | Validation error / 403 |
| Checkout | Chèn total=1 | Backend total đúng giá+ship−discount |
| Checkout | COD nhiều shop, retry checkout key | 1 parent, đúng child, snapshots, không duplicate |
| Voucher | Percent/fixed, scope, trần, hết lượt/hết hạn/sai code | Integer allocation, usage lock, reject đúng |
| Payment | Signature sai, amount sai, browser Return | Không paid/không capture |
| Payment | Success, failure, duplicate callback | Capture một lần hoặc release một lần |
| Payment | Hết hạn, callback muộn, chưa cấu hình | Expired/rejected hoặc COD fallback, không success giả |
| Orders | Nhảy trạng thái, hủy quá muộn | Validation error |
| Stock | Reserve/consume/release/adjust dưới reserved | Bất biến 0 <= reserved <= stock |
| Money | Thu COD, hoàn, replay, vượt số dư | Ledger đúng, không duplicate/over-refund |
| Split | Refund shop A rồi xử lý B đã trả tiền | B tiếp tục độc lập; parent processing |
| Review | Chưa giao / đã giao | 403 / lưu review |
| Return | Sai chủ, quá 14 ngày | 403 / 422 |
| Report | Đơn chưa thu / capture / refund | 0 / +capture / −refund |
| Admin | Pages và settings lưu MySQL | HTTP 200, dữ liệu persisted |
| Seed | ID bắt đầu 1000, hai shop có thu, kiểm ledger | 40 products/240 variants/18 orders; 0 lệch kho |
| Race | Hai process đặt chiếc cuối | 1 success, 1 unavailable, 1 order, reserved=1 |
| Race | Hai giỏ tranh lượt voucher cuối | used=1, chỉ một đơn thành công |
| Race | Hai IPN cùng payload | Một capture, audit attempts=2 |
| Race | Hủy đơn và success IPN đồng thời | Kho release một lần, tiền theo thứ tự commit, không success giả |
| Race | Hai yêu cầu nhập lại hàng trả | Stock chỉ tăng một lần, một movement |
| Auth | Đổi/reset mật khẩu | Thu hồi session cũ, xoay remember_token, reset email queued |
| UX | Hàng ngừng bán/hết kho sau thêm giỏ | Cảnh báo, chặn tiếp tục checkout |
| Shipping | Hai shop chọn nhanh, đổi cấu hình sau đặt | Server tính theo shop, snapshot cũ không thay đổi |
| Management | CRUD collection/voucher; ẩn review | Lưu thật, không xóa lịch sử voucher, khách không tự hiện review |
| Browser | COD end-to-end, refresh, admin workflow, CSRF | Hoạt động thật, POST thiếu token 419 |
| Responsive | 390 px, menu, JS errors | Không tràn ngang, toggle đúng, không pageerror |

## Test manual còn cần khi vận hành
SMTP gửi email thật; VNPay merchant sandbox (success/failure/disconnect/replay/late); ảnh thật và nội dung người dùng; bàn phím/screen reader toàn bộ form, tablet, Safari/Firefox; load test, backup/restore trên hạ tầng đích. Không xem automated mock IPN là chứng cứ giao dịch gateway đã chạy.

Kết quả thực tế và log cuối được ghi trong `verification.md`; số test ở đó là số đã chạy, không phải mục tiêu dự kiến.

## Chạy browser smoke có thể tái hiện
Chỉ chạy trên bản seed demo riêng: script tạo đơn, ghi thu demo, tạo bộ sưu tập và xử lý return. Dùng tài khoản/mật khẩu demo mặc định; không chạy vào website kinh doanh.

```bash
python -m venv .venv
. .venv/bin/activate
pip install -r tests/Browser/requirements.txt
python -m playwright install chromium
QA_CONFIRM_DEMO=yes STORE_BASE_URL=http://localhost:8000 python tests/Browser/smoke.py
```

Ảnh/report mặc định /tmp/minimal-browser-qa; đặt QA_OUTPUT để đổi thư mục, CHROMIUM_PATH để dùng Chromium đã cài. Đợi ít nhất một phút nếu bị throttle login sau nhiều lần chạy liên tiếp. Các selector và dữ liệu cố định dựa trên seed mặc định.
