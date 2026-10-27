# Cài đặt và vận hành local

## Không dùng Docker
PHP 8.4 với pdo_mysql, mbstring, intl, xml/dom, curl, zip, bcmath; Composer 2; Node 22; MySQL 8.4. pcntl cần cho test race (Linux/macOS hoặc Docker).

1. Tạo database `minimal`, `minimal_test`, `minimal_concurrency` với utf8mb4; tạo user local có quyền trên cả ba database.
2. `cp .env.example .env`, đặt DB_HOST=127.0.0.1, thông tin DB; đặt APP_URL.
3. `composer install`, `npm ci`, `php artisan key:generate`.
4. `php artisan migrate`, `php artisan db:seed`, `php artisan storage:link`, `npm run build`.
5. Chạy `php artisan serve`, `php artisan queue:work --tries=3` và `php artisan schedule:work` ở ba terminal.
6. `php artisan test`; bài race chạy với `DB_DATABASE=minimal_concurrency APP_ENV=testing BCRYPT_ROUNDS=4 QUEUE_CONNECTION=sync MAIL_MAILER=array php tests/Concurrency/run.php`.

Database queue/cache/session là mặc định. Redis không bắt buộc. SMTP mặc định `MAIL_MAILER=log`: email vào `storage/logs/laravel.log`, không gửi ra ngoài. Đổi MAIL_MAILER=smtp và các biến MAIL_* khi có SMTP thật; khởi động lại worker sau thay cấu hình. Không chia sẻ log reset password vì chứa link dùng một lần.

## Tác vụ nền
`orders:expire` chạy mỗi phút qua scheduler. Dừng scheduler làm đơn VNPay chờ giữ kho lâu hơn; khi IPN đến service vẫn tự kiểm hết hạn. `queue:failed` và `queue:retry <id>` để xem/thử lại email lỗi. Email lỗi không rollback đơn đã tạo.

## Khi thay giao diện
- `resources/views/components`: cấu trúc giao diện.
- `resources/css/app.css`: styles và CSS variables.
- `/admin`: brand, tokens, thứ tự section, menu, banner, thông tin liên hệ.
- `/admin/shipping`: phí tiêu chuẩn/nhanh mỗi shop. `config/store.php`: giá mặc định, thời hạn giữ kho, số ngày đổi/trả.
- `npm run dev` khi phát triển; `npm run build` trước giao bản.

Không đổi khóa ngoại/giá snapshot của đơn cũ khi sửa sản phẩm. Thay đổi schema phải tạo migration mới; tránh sửa migration đã chạy trên production.

## Email thử bằng Mailpit (không gửi ra Internet)
Compose có profile mail: `docker compose --profile mail up -d mailpit`. Đặt trong .env:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_SCHEME=smtp
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

Chạy `docker compose exec app php artisan optimize:clear` và `docker compose restart queue`. Xem hộp thư local tại http://localhost:8025. Đặt COD hoặc dùng Quên mật khẩu rồi chờ worker. Không đưa link reset/token trong ảnh demo hoặc log công khai. Mailpit là SMTP sink để test, không chứng minh email đã đến hộp thư Internet.

File docker/mysql/init.sql cần quyền đọc cho container MySQL (trên Linux: `chmod 644 docker/mysql/init.sql`). Nếu tải source qua Git quyền này đã phù hợp. Không xóa volume cũ để sửa quyền trên database cần giữ.
