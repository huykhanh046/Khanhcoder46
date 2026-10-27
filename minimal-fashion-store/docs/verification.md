# Kết quả kiểm chứng thực tế — 27/10/2026

PHP 8.4 / Laravel 12.70.1 / MySQL 8.4, Node 22, Chromium headless có JavaScript. Cài phụ thuộc theo lock files và TLS bình thường.

| Hạng mục | Kết quả quan sát |
|---|---|
| MySQL | 5 migrations; schema thực tế 36 bảng, xuất PK/FK/index/unique vào schema.json và ERD |
| PHPUnit | **41 passed, 213 assertions**; 4,64 giây trong runtime ban đầu, 5,00 giây trên bản Compose cài sạch |
| Concurrency | **5/5 tình huống đạt**, mỗi tình huống hai process, hai kết nối MySQL và barrier |
| Seed regression | 40 sản phẩm, 240 biến thể (ID bắt đầu 1000), 18 đơn; 2 shop có thu, 0 lệch sổ kho |
| Frontend | npm run build thành công; route:cache, view:cache thành công |
| Browser | **24 kiểm tra**, desktop 1440 px, mobile 390 px; 0 pageerrors; POST thiếu CSRF bị 419 |
| Mermaid | **10/10** sơ đồ parse thành công, gồm ERD schema mới |
| Docker sạch | Build Dockerfile, composer install, key, migrate, seed, storage link, npm ci/build; app/queue/scheduler hoạt động với MySQL mới |
| SMTP nội bộ | OrderPlaced và ResetPasswordQueued qua database queue đến Mailpit; dùng liên kết MIME để đổi mật khẩu rồi đăng nhập thành công |
| Jobs | orders:expire chạy; queue:failed không có job lỗi tại lần kiểm tra |
| Backup/restore | mysqldump consistent snapshot; restore vào database riêng, số sản phẩm/biến thể/đơn, tổng stock/reserved/tiền giao dịch khớp |

Năm race: (1) tranh chiếc cuối chỉ một đơn chiếm kho; (2) tranh voucher một lượt chỉ used=1; (3) IPN trùng chỉ một capture và attempts=2; (4) hủy cùng lúc IPN không release hai lần, khoản thu tùy thứ tự commit được giữ để đối soát; (5) nhập hàng trả hai lần chỉ tăng stock một lần. Các test kiểm tính đúng của những tình huống này, không chứng minh tải lớn.

Browser thực hiện login, chọn size/màu, giỏ, chọn giao nhanh, COD, refresh đơn, dashboard Chart.js; Admin chuyển giao/hoàn tất và ghi thu demo; tạo collection/upload ảnh; khách gửi return, Admin duyệt/nhận/nhập kho; mở các trang voucher/collection/shipping/payment; menu mobile. Thu tiền và hàng hóa đều là dữ liệu thử nghiệm.

Bản cài sạch ở thư mục riêng, không tái dùng vendor/node_modules/.env/database cũ. Trong cloud có override CA trust để tải phụ thuộc qua proxy, port 8001 để tránh xung đột; ứng dụng bàn giao dùng port 8000. Lỗi quyền đọc init.sql được phát hiện khi dựng sạch, đã sửa quyền 644 và dựng lại volume nghiệm thu mới. Không xóa volume dữ liệu dự án.

## Tái hiện

```bash
docker compose exec app php artisan test
docker compose exec -e DB_DATABASE=minimal_concurrency -e APP_ENV=testing -e BCRYPT_ROUNDS=4 -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array app php tests/Concurrency/run.php
docker compose exec -e DB_DATABASE=minimal_test -e APP_ENV=testing -e BCRYPT_ROUNDS=4 -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array app php tests/Seed/run.php
docker compose exec app php artisan view:cache
docker compose exec app php artisan route:cache
```

Race/seed xóa và dựng lại schema có guard hậu tố. Feature và seed dùng chung minimal_test nên chạy tuần tự. Không dùng DB chứa dữ liệu cần giữ. Browser script và lệnh ở test-plan.md; Mailpit ở installation.md.

## Chưa xác minh
Chưa có merchant/secret để chạy giao dịch với VNPay Sandbox thực. Chưa kiểm SMTP gửi ra Internet, production hosting, tải lớn, Safari/Firefox, WCAG toàn diện. Đã thử Compose trên cloud Linux, chưa xác minh trên Windows/macOS của người dùng. Hoàn tiền chỉ ghi sổ sau xác nhận thực tế, không tự gọi API chuyển tiền. Không tuyên bố sẵn sàng production chỉ dựa trên các test local.

Bằng chứng đã loại secret và token nằm trong [evidence](evidence). Các số liệu là kết quả lệnh đã chạy, không phải mục tiêu dự kiến.
