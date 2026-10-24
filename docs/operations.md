# Vận hành và cấu hình

## Từ checkout mới

Dùng checkout đang có trong task cloud; mỗi task đã cô lập, không tạo Git worktree trừ khi được yêu cầu. Đọc README và chạy trong `/workspace/Khanhcoder46` (hoặc thư mục clone trên máy cá nhân).

1. `npm ci` theo lockfile.
2. `npm run db:up`, chờ `docker compose exec -T db pg_isready -U khfashion -d khfashion` thành công.
3. `npm run setup`: tạo `.env` nếu thiếu, generate, migrate, seed demo.
4. `npm run build`, sau đó `npm start`; phát triển dùng `npm run dev`.
5. Dùng HTTP nội bộ kiểm tra trang chủ và `/san-pham`; kiểm tra luồng COD bằng Playwright.

Không giả định process Next.js/PostgreSQL/worker đang chạy sau khi chuyển task. Docker volume `khfashion_pg` thuộc Docker host hiện tại; môi trường cloud có thể không giữ nó khi tạo lại máy. Cần kiểm tra và phục hồi DB từ backup nếu cần giữ dữ liệu, không chỉ dựa vào snapshot ứng dụng. Các lệnh seed không khôi phục đơn hàng bị mất.

## Biến môi trường

| Tên               | Bắt buộc | Mục đích                                                             |
| ----------------- | -------- | -------------------------------------------------------------------- |
| DATABASE_URL      | Có       | PostgreSQL, credential lưu trong .env riêng                          |
| SESSION_SECRET    | Có       | Ít nhất 32 ký tự ngẫu nhiên, setup sinh nếu chưa có                  |
| APP_URL           | Có       | Origin public/local chính xác để kiểm tra mutation và tạo return URL |
| COOKIE_SECURE     | Có       | true cho HTTPS, false cho demo HTTP nội bộ                           |
| VNPAY_TMN_CODE    | Tùy chọn | Merchant sandbox                                                     |
| VNPAY_HASH_SECRET | Tùy chọn | Khóa ký, dùng cục bộ nên cần giá trị thật trong môi trường bảo mật   |
| VNPAY_URL         | Tùy chọn | Mặc định endpoint sandbox                                            |
| CHROMIUM_PATH     | Chỉ test | Chromium hệ thống nếu không dùng browser Playwright tải              |

Không đưa secret vào Git hoặc chat. VNPay cần HMAC trong process, không thể dùng placeholder chỉ được proxy thay thế. Chưa yêu cầu credential nếu chỉ dùng COD. Cloud đã có Git authentication; không cần token riêng để chạy ứng dụng.

Các gói được tải qua registry.npmjs.org; Docker dùng registry Docker Hub và CDN của registry. Không cần thêm domain ảnh. Kiến trúc Prisma JS tránh tải binaries.prisma.sh. VNPay sandbox cần browser và kết nối IPN từ nhà cung cấp về ứng dụng có HTTPS công khai; chưa có endpoint public trong task này.

## Migration có thể lặp lại

`prisma/migrations/*/migration.sql` được tạo từ mô hình Prisma, sau đó thêm ràng buộc nghiệp vụ trong migration riêng. `npm run db:migrate` chạy runner Node pg:

- BEGIN transaction và advisory lock cố định, tránh hai process apply cùng lúc.
- Bảng `_kh_migrations` giữ tên và SHA-256 SQL.
- SQL đã áp dụng mà bị sửa sẽ làm lệnh thất bại; cần migration mới, không đổi lịch sử.
- Tất cả migration mới được commit cùng nhau; lỗi rollback.

Prisma migrate deploy của phiên bản đang dùng bị lỗi deserialize kiểu catalog `name` khi dùng adapter JS. Không chỉnh thư viện hoặc tắt kiểm tra integrity để vượt lỗi. Runner pg thực thi SQL chuẩn và không dùng bảng `_prisma_migrations`; không trộn hai runner trên cùng DB.

Khi sửa schema: tạo migration mới bằng diff từ bản schema trước sang bản mới (không cần native engine), rà soát SQL và lưu vào thư mục timestamp mới; giữ CHECK/index đặc thù đã có. Chạy trên DB kiểm thử rồi `npm run db:generate`. Tránh destructive schema changes khi có dữ liệu cần giữ.

## Worker và thanh toán

COD không cần worker. Khi bật VNPay, chạy `npm run orders:watch` như process riêng được quản lý bằng systemd/container/process manager, hoặc schedule `npm run orders:expire` mỗi phút. Nhiều worker không gây hoàn tồn hai lần vì kiểm tra trạng thái trong transaction.

Đăng ký IPN với nhà cung cấp: `/api/payments/vnpay/ipn`; return URL `/thanh-toan/ket-qua`. IPN là GET có chữ ký, không dùng cookie/Origin. Các API mutation khác cần cookie và Origin hợp lệ. Callback thử bằng test chỉ là mô phỏng nội bộ. Với merchant thật, kiểm thử cả thành công, thất bại, lặp, chậm và đối soát trước khi bật cho người dùng.

## Backup

Tạo bản sao bằng `docker compose exec -T db pg_dump -U khfashion khfashion > backup.sql` trong vị trí riêng an toàn. File có thông tin khách hàng và hash mật khẩu; không commit. Khôi phục bằng psql vào DB riêng, xác minh trước khi chuyển kết nối. Không chạy `docker compose down -v` nếu cần dữ liệu hiện tại. Seed không phải backup.

## Khi triển khai ngoài môi trường demo

Dùng secret và DB riêng, xóa tài khoản demo hoặc đổi credential bằng quy trình quản trị DB. Không chạy seed demo ở production; script chặn khi NODE_ENV=production trừ khi chủ động đặt ALLOW_DEMO_SEED=true. Triển khai HTTPS, COOKIE_SECURE=true và APP_URL chuẩn; chỉ tin x-forwarded-for từ reverse proxy tin cậy. Throttle tại edge, backup, quan sát lỗi, chính sách dữ liệu cá nhân và quy trình phục hồi cần được cấu hình theo hạ tầng thực.

Chưa cung cấp deployment public hoặc domain. Không coi build thành công hay lưu draft môi trường là đã triển khai hoặc Publish.
