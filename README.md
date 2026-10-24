# KH Fashion — Website bán hàng thời trang fullstack

Ứng dụng Next.js App Router + TypeScript, Tailwind CSS, các component theo cấu trúc shadcn/ui, PostgreSQL và Prisma. Giao diện tiếng Việt, tiền VND; quản lý sản phẩm theo size/màu, đặt COD, phân quyền khách hàng/quản trị viên và tài liệu phân tích hệ thống.

## Chạy trên máy cá nhân

Yêu cầu **Node.js 22.12+ (đã kiểm tra với Node 24)**, npm, Docker và Docker Compose. Chạy các lệnh tại thư mục chứa `package.json`:

```bash
npm ci
npm run db:up
# Chờ PostgreSQL healthy:
docker compose ps
npm run setup
npm run dev
```

`.npmrc` tắt lifecycle scripts khi cài dependency để tránh tải Prisma native engine không sử dụng. Các gói native Next/Tailwind/esbuild dùng npm platform packages; bước generate được chạy tường minh bởi setup.

`npm run setup` chỉ tạo `.env` khi chưa có, sinh khóa phiên ngẫu nhiên, generate Prisma Client, áp dụng migration và seed. Có thể chạy lại: migration kiểm tra SHA-256; seed không ghi đè tài khoản, sản phẩm hay tồn kho đã có. Không dùng seed demo cho production.

Ứng dụng dùng cổng 3000, PostgreSQL được bind vào loopback cổng 5432. Khi dùng PostgreSQL có sẵn, bỏ `db:up`, tạo `.env` từ `.env.example`, sửa `DATABASE_URL`, `SESSION_SECRET` rồi chạy setup. **Thay `APP_URL` theo origin thực tế**, nếu không các request ghi dữ liệu sẽ bị chặn. Khi truy cập HTTPS, đặt `COOKIE_SECURE=true`.

### Tài khoản demo (chỉ phát triển)

| Vai trò    | Email              | Mật khẩu demo  |
| ---------- | ------------------ | -------------- |
| Quản trị   | admin@khfashion.vn | KhFashion@2026 |
| Khách hàng | khach@khfashion.vn | KhFashion@2026 |

Voucher `WELCOME10`: giảm 10%, đơn tối thiểu 300.000đ, tối đa 100.000đ, 100 lượt; hạn cuối năm 2030. Phí giao hàng 30.000đ, miễn phí từ 699.000đ tiền hàng trước giảm giá.

## Chức năng

- Trang chủ, tìm kiếm, lọc theo danh mục/giá/size/màu/tồn/khuyến mãi, sắp xếp, phân trang.
- Chi tiết sản phẩm có gallery, chọn biến thể và số lượng; giỏ hàng lưu tại trình duyệt.
- Đăng ký/đăng nhập/đăng xuất, hồ sơ và sổ địa chỉ, lịch sử và chi tiết đơn.
- Checkout tính giá ở server, voucher, COD; khách tự hủy đơn đang chờ và chưa thanh toán.
- Quản trị: doanh thu từ đơn đã giao/đã thanh toán; sản phẩm, danh mục, biến thể, nhập/điều chỉnh kho có lịch sử; xử lý đơn, voucher, xem khách hàng và lịch sử mua.
- Transaction PostgreSQL Serializable, retry xung đột, tồn kho có điều kiện, khóa idempotency duy nhất theo khách. Hủy đơn hoàn tồn/lượt voucher đúng một lần.
- VNPay sandbox có tạo URL ký HMAC-SHA512, xác minh IPN, số tiền/merchant và thông báo lặp. **Chưa được chứng nhận qua cổng VNPay thực**; COD hoạt động độc lập.

Ảnh sản phẩm là SVG minh họa được lưu trong repository, không phụ thuộc dịch vụ ảnh bên ngoài. Admin quản lý đường dẫn ảnh dưới `/images/`; thêm ảnh bằng cách đặt tệp vào `public/images`. Chưa có upload qua trình duyệt.

## Lệnh phát triển và kiểm thử

```bash
npm run lint
npm run typecheck
npm test                 # unit + integration trên PostgreSQL
npm run build
npm start                # chạy bản production sau build
npx playwright install chromium
npm run test:e2e         # desktop + mobile, tự chạy bản production
# Máy cloud có Chromium hệ thống:
CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e
```

Test ghi dữ liệu fixture vào DB đã migrate rồi dọn những dữ liệu đó. **Chỉ chạy trên DB phát triển/kiểm thử riêng**, không chạy trên DB có giao dịch thật. E2E tự tạo tài khoản quản trị và khách hàng tạm, rồi dọn fixture sau mỗi worker.

```bash
npm run db:generate
npm run db:migrate
npm run db:seed
npm run orders:expire    # hủy đơn VNPay chưa trả tiền đã quá 15 phút
npm run orders:watch     # worker kiểm tra mỗi phút; chạy riêng khi bật VNPay
```

Các lệnh `tsx` tự đọc `.env`. Môi trường này dùng Prisma Client JavaScript + `@prisma/adapter-pg`, không tải native query engine. Prisma 6 migrate deploy với adapter JS gặp lỗi kiểu catalog PostgreSQL `name`; vì vậy migration dùng SQL đã version hóa và runner `scripts/migrate.ts`: advisory lock, transaction, checksum, không sửa migration đã áp dụng. Không dùng `prisma db push` để thay thế migration. Xem [hướng dẫn vận hành](docs/operations.md).

## Cấu trúc

```text
src/app/           Trang server, layout và API route
src/components/    Giao diện tương tác, cart context, biểu mẫu
src/lib/           Xác thực, validation, nghiệp vụ đơn hàng/thanh toán
prisma/            Schema, migration SQL và seed
scripts/           Setup, migration, worker hết hạn, sinh ảnh minh họa
tests/             Unit, tích hợp PostgreSQL, E2E Playwright
docs/              Yêu cầu, Use Case, sơ đồ, kiểm thử và vận hành
```

Bắt đầu đọc [tài liệu đồ án](docs/README.md), [yêu cầu](docs/requirements.md), [thiết kế](docs/design.md) và [kết quả kiểm thử](docs/testing.md).

## Phạm vi và triển khai thực tế

Đây là ứng dụng đồ án chạy được, chưa phải dịch vụ thương mại đã triển khai công khai. Chưa tích hợp giao vận, email/khôi phục mật khẩu, trả hàng/hoàn tiền, flash sale, hay thanh toán live. Chính sách và tài khoản mẫu phục vụ trình diễn. Trước khi triển khai thực tế: dùng DB và secret riêng, HTTPS, thay tài khoản demo, thiết lập backup, giám sát, giới hạn request tại reverse proxy và xác thực sandbox với nhà cung cấp. Không đưa `.env` lên Git.
