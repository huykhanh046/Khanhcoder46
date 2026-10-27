# Triển khai

## Local Docker
Dùng README. Named volume mysql_data giữ dữ liệu sau restart. `docker compose down` giữ volume; `down -v` xóa dữ liệu nên không dùng với dữ liệu cần giữ. Port MySQL không publish. Init SQL tạo hai DB test cho user mặc định `minimal`; nếu đổi DB_USERNAME, cập nhật init.sql trước lần khởi tạo đầu tiên.

## VPS / hosting PHP
PHP-FPM 8.4 + Nginx/Apache, MySQL 8.4, Composer 2, Node 22 để build. Upload repository, document root phải trỏ `public/`; không public root source hoặc `.env`. Tạo user database riêng và storage có quyền ghi cho PHP; không dùng chmod 777.

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan key:generate # chỉ lần đầu, không đổi khóa của site đang chạy
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Đặt APP_ENV=production, APP_DEBUG=false, APP_URL=https://domain, SESSION_SECURE_COOKIE=true và secret từ môi trường deployment. Không chạy demo seeder. Tạo admin riêng bằng lệnh `php artisan store:create-admin` (prompt mật khẩu ẩn). Bật HTTPS, cấu hình reverse proxy đáng tin cậy theo hạ tầng. Nginx `try_files $uri $uri/ /index.php?$query_string;`, PHP location truyền SCRIPT_FILENAME, chặn dotfiles trừ .well-known. Chỉ phục vụ file upload tĩnh, không thực thi PHP trong storage.

Supervisor/systemd chạy `php artisan queue:work --tries=3 --timeout=60`; cron mỗi phút `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`. Sau deploy `php artisan queue:restart`. DB/cache driver mặc định database; nếu mở rộng Redis cần cài client/extension và kiểm tra cấu hình trước.

Backup MySQL + storage/app/public, mã nguồn và APP_KEY. Thử restore vào staging. Lưu log không public, không ghi secrets/payload thẻ. Theo dõi failed_jobs, IPN rejected/late, đơn hết hạn và khoản thu chưa hoàn. Trước release chạy suite trên DB test tách biệt và smoke test COD, phân quyền, ảnh upload. Hosting không cho cron/worker lâu dài cần cơ chế cron phù hợp hoặc chọn VPS.

Chưa triển khai public production trong phiên này. Docker dùng PHP development server dành cho local; không coi là cấu hình production đã nghiệm thu.

## Backup/restore đã thử ở local
Đã dùng mysqldump --single-transaction --no-tablespaces --set-gtid-purged=OFF cho DB demo, restore vào DB riêng và so các tổng quan trọng. Evidence: docs/evidence/backup.json. Ví dụ trên máy cá nhân (mysql_config_editor lưu profile, không đặt mật khẩu vào command history):

```bash
mysqldump --login-path=store --single-transaction --no-tablespaces --set-gtid-purged=OFF minimal > backup.sql
mysql --login-path=store -e 'CREATE DATABASE minimal_restore CHARACTER SET utf8mb4'
mysql --login-path=store minimal_restore < backup.sql
```

Cần backup cả storage/app/public và APP_KEY bằng nơi lưu an toàn; không đưa dump/token/secret vào Git. Kiểm chứng local không thay thế thử restore trên hạ tầng đích.
