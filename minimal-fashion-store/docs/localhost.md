# Bước 2 — Chạy dự án bằng localhost

Mục tiêu: mở http://localhost:8000 trên chính máy đang chạy Docker. Không cần hosting, tên miền hoặc tài khoản cloud. Localhost không phải URL công khai và localhost của workspace không phải localhost của máy người dùng.

## Chuẩn bị

1. Cài Docker Desktop trên Windows/macOS hoặc Docker Engine + Compose v2 trên Linux.
2. Mở Docker, đợi engine sẵn sàng.
3. Giải nén mã nguồn, mở terminal trong thư mục `minimal-fashion-store` có `compose.yaml`.
4. Kiểm tra `docker --version` và `docker compose version`.

## Cài lần đầu từ bản mã nguồn mới

Tạo `.env` từ `.env.example` nếu chưa có. Giữ nguyên `.env` và APP_KEY nếu dự án đã cài.

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

macOS/Linux/WSL:

```bash
cp .env.example .env
chmod 644 docker/mysql/init.sql
```

Mở `.env`: đặt `APP_URL=http://localhost:8000`; giữ `DB_HOST=db`; đặt `DB_PASSWORD` và `MYSQL_ROOT_PASSWORD` cho database local mới. Giữ tên database/user mặc định để khớp file khởi tạo database kiểm thử. Linux có UID/GID khác 1000: đặt LOCAL_UID/LOCAL_GID tương ứng. SMTP mặc định ghi log; COD không cần thông tin VNPay.

Chạy lần lượt; chỉ tiếp tục khi lệnh trước thành công:

```bash
docker compose build app
docker compose up -d db
docker compose run --rm app composer install --no-interaction
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate
docker compose run --rm app php artisan db:seed
docker compose run --rm app php artisan storage:link
docker compose run --rm node
docker compose up -d app queue scheduler
docker compose ps
```

Seeder dành cho database mới, tạo tài khoản và dữ liệu demo. Không chạy lại key:generate hoặc seed như một thao tác khởi động thường ngày. Không dùng migrate:fresh cho dữ liệu cần giữ.

Mở http://localhost:8000. Admin: `admin@example.test`; Customer: `customer1@example.test`; mật khẩu mặc định `DemoFashion123!` hoặc DEMO_PASSWORD bạn đặt trước khi seed. Admin/Seller quản lý tại `/manage`; Admin cấu hình tại `/admin`.

## Khởi động và dừng những lần sau

```bash
docker compose up -d app queue scheduler
docker compose stop
```

`stop` giữ dữ liệu. Không dùng `down -v` khi cần giữ database. Nếu không mở được trang, kiểm tra `docker compose ps` và `docker compose logs --tail=80 app db`. Cổng 8000 phải chưa bị ứng dụng khác sử dụng.

## Cấu trúc chính

```text
app/
  Http/Controllers/   Nhận request, gọi service, trả response
  Http/Requests/      Validation và quyền của form request
  Models/            Bảng và quan hệ Eloquent
  Policies/          Quyền sở hữu dữ liệu theo user/shop
  Services/          Nghiệp vụ kho, đơn, voucher, thanh toán
  Notifications/     Email xác nhận đơn và đặt lại mật khẩu
database/
  migrations/        Phiên bản schema và ràng buộc dữ liệu
  factories/         Dữ liệu phục vụ kiểm thử
  seeders/           Dữ liệu và tài khoản demo
resources/
  views/             Trang Blade và components
  css/               Styles và design tokens
  js/                Alpine.js và biểu đồ
routes/              Routes web và tác vụ console
config/              Cấu hình ứng dụng, cửa hàng và thanh toán
public/              Web root và tài nguyên được phục vụ
storage/             Upload, cache và log
tests/               Feature, concurrency, seed và browser tests
docs/                Tài liệu đồ án và hướng dẫn
compose.yaml         Các dịch vụ chạy local
Dockerfile           PHP runtime
```

Backend và frontend đã có trong mã nguồn bàn giao; các bước tiếp theo của hướng dẫn sẽ giải thích và kiểm tra từng phần trên cùng dự án này. Xác nhận với người dùng trước khi chuyển bước.

VNPay IPN từ Internet không thể gọi trực tiếp localhost. Dùng COD để demo mua hàng đầy đủ ở local; kiểm thử logic VNPay nằm trong test suite, không phải giao dịch gateway thực.
