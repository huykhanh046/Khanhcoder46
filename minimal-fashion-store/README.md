# MINIMAL FASHION STORE

Ứng dụng Laravel 12 / PHP 8.4 / MySQL 8.4, Blade + Tailwind 4 + Alpine 3. Dữ liệu lưu database; không dùng localStorage để thay backend. Giao diện tiếng Việt. Hai shop mẫu, 40 sản phẩm, 240 biến thể.

## Chạy bằng Docker (local)

Hướng dẫn từng bước cho Windows/macOS/Linux và cấu trúc thư mục: [Chạy bằng localhost](docs/localhost.md).

Cần Docker Engine + Compose v2. Chạy từ thư mục chứa `compose.yaml`:

```bash
cp .env.example .env
chmod 644 docker/mysql/init.sql # Linux/macOS: MySQL cần đọc file khởi tạo
# Đổi DB_PASSWORD và MYSQL_ROOT_PASSWORD trong .env trước khi chạy.
docker compose build app
docker compose up -d db
docker compose run --rm app composer install --no-interaction
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan db:seed
docker compose run --rm app php artisan storage:link
docker compose run --rm node
docker compose up -d app queue scheduler
```

Mở http://localhost:8000. Linux: nếu UID/GID khác 1000, đặt `LOCAL_UID`/`LOCAL_GID` tương ứng trong `.env` trước khi chạy. Seed chỉ dành cho database trống; không dùng migrate:fresh trên dữ liệu thật. Compose là cấu hình phát triển, dùng PHP development server; triển khai production theo `docs/deployment.md`.

Ảnh SVG trong `public/images` là minh họa tự tạo, thay ảnh bằng form quản trị. Seed không phải dữ liệu kinh doanh thật. `.env` không được commit. File `composer.lock` khóa phiên bản và tải archive commit từ GitHub codeload chính thức; giữ nguyên khi `composer install`.

## Tài khoản demo

Mật khẩu lấy từ `DEMO_PASSWORD` khi seed, mặc định `DemoFashion123!` cho local:

| Vai trò | Email |
|---|---|
| Admin | admin@example.test |
| Seller | seller1@example.test, seller2@example.test |
| Customer | customer1@example.test, customer2@example.test, customer3@example.test |

Admin/Seller vào `/manage`, Admin cấu hình tại `/admin`. Không seed tài khoản demo trên production.

## Kiểm thử

```bash
docker compose exec app php artisan test
docker compose exec -e DB_DATABASE=minimal_concurrency -e APP_ENV=testing -e BCRYPT_ROUNDS=4 -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array app php tests/Concurrency/run.php
```

Suite feature dùng `minimal_test`. Bài race dùng `minimal_concurrency`, xóa và dựng lại schema chỉ trên database có hậu tố `_concurrency`. Không trỏ kiểm thử vào database chứa dữ liệu cần giữ. Test race tạo hai process với hai kết nối MySQL và barrier đồng thời; không dùng SQLite để chứng minh locking.

Kiểm tra seeder với ID bắt đầu từ 1000 (xóa và dựng lại database test riêng):

```bash
docker compose exec -e DB_DATABASE=minimal_test -e APP_ENV=testing -e BCRYPT_ROUNDS=4 -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array app php tests/Seed/run.php
```

## Tài liệu

- [Kiến trúc và business rules](docs/01-architecture.md)
- [SRS, yêu cầu và Use Cases](docs/02-srs.md)
- [ERD và sơ đồ](docs/03-diagrams.md)
- [Routes thực tế](docs/routes.md)
- [Test plan và kết quả](docs/test-plan.md)
- [Cài đặt không Docker](docs/installation.md)
- [VNPay Sandbox](docs/vnpay.md)
- [Triển khai](docs/deployment.md)
- [Hướng dẫn và kịch bản demo](docs/demo.md)
- [Tiến độ, giới hạn và nghiệm thu](docs/progress.md)

VNPay chỉ hoạt động khi có mã merchant và secret sandbox. Redirect trình duyệt không xác nhận tiền; IPN được kiểm hash và amount. Khi chưa cấu hình, COD vẫn hoạt động. Hoàn tiền hiện ghi nhận thủ công với mã chứng từ, không tự chuyển tiền.

## Các module hoàn thiện
Quản trị bộ sưu tập `/admin/collections`, voucher `/admin/vouchers`, vận chuyển `/admin/shipping`, đối soát `/admin/payments`. Hàng trả có xác nhận nhập kho một lần. Đổi/reset mật khẩu thu hồi phiên cũ. Email xác nhận đơn và reset chạy qua queue; có Mailpit local theo docs/installation.md.

Bộ test gồm PHPUnit, 5 tình huống cạnh tranh hai process MySQL và regression seeder; kết quả chạy thực tế mới nhất ở docs/verification.md.
