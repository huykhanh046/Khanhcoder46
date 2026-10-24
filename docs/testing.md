# Kiểm thử và ma trận truy vết

Môi trường xác thực: Node.js 24, Next.js 16, PostgreSQL 17 chạy Docker, Prisma Client JavaScript + pg adapter. Unit và integration chạy bằng Vitest; E2E dùng Chromium hệ thống với Playwright, desktop 1280px và thiết bị mobile giả lập iPhone 13 (Chromium, không phải Safari trên iPhone thật).

## Ma trận yêu cầu → bằng chứng

| Yêu cầu  | Kiểm tra                                                                                 |
| -------- | ---------------------------------------------------------------------------------------- |
| FR01–02  | E2E tìm Essential, mở gallery, đổi size/màu; kiểm tra các màn hình và ảnh                |
| FR03     | E2E thêm giỏ, tải lại, tăng số lượng và chuyển checkout                                  |
| FR04     | E2E tài khoản fixture, API 401/403, Origin khác nguồn bị chặn                            |
| FR05     | E2E sửa hồ sơ và tạo/sửa/xóa địa chỉ của chính mình                                      |
| FR06     | E2E COD + WELCOME10, xác nhận tổng 478.200đ từ server                                    |
| FR07     | Integration hai khách mua hàng cuối; hai request cùng UUID chỉ tạo một đơn               |
| FR08     | Integration ngăn hủy đơn người khác, hủy đồng thời hoàn kho một lần                      |
| FR09–10  | E2E admin tạo sản phẩm/biến thể, điều chỉnh kho, chặn tồn âm và ẩn sản phẩm              |
| FR11     | E2E admin xác nhận → shipping → delivered, kiểm tra COD PAID                             |
| FR12     | Unit tính min/cap, integration lượt voucher cuối và trả lượt, E2E tạo voucher            |
| FR13     | E2E mở dashboard/khách hàng và các màn hình quản trị                                     |
| FR14     | Integration callback ký tổng hợp: chữ ký sai, amount sai, thành công/lặp; worker hết hạn |
| FR15     | E2E kiểm ảnh nội bộ tải thành công; chưa có upload qua browser                           |
| NFR01–04 | Unit rules + transaction integration, kiểm tra API 400/401/403/404                       |
| NFR05    | E2E desktop/mobile, kiểm tra tràn ngang và pageerror                                     |
| NFR06    | Build/typecheck/lint, migration lặp lại và seed bảo toàn dữ liệu                         |

## Các nhóm kiểm thử

### Unit và integration

- Tiền hàng, ngưỡng miễn ship trước giảm, cap/rounding voucher, minOrder, giá trị không hợp lệ.
- Luật chuyển trạng thái, dữ liệu checkout không hợp lệ, canonical signing.
- Hai khách tranh sản phẩm tồn 1: một thành công, một thất bại, stock 0.
- Gửi checkout trùng: một order, một lần trừ stock.
- Ownership, hủy đồng thời: hoàn tồn một lần.
- Tranh lượt voucher cuối: một claim; hủy hoàn lượt.
- Snapshot giá và COD qua các trạng thái; không nhảy bước/hủy sau giao.
- Callback tổng hợp và hết hạn VNPay. Đây là test mã xử lý, không thay cho merchant sandbox.

### E2E

Mỗi project desktop/mobile chạy năm kịch bản: mua COD đến giao thành công; kiểm tra bảo mật API; smoke tất cả màn hình/responsive/ảnh; CRUD sản phẩm/biến thể/kho/voucher của admin; quản lý hồ sơ/địa chỉ khách hàng. Fixture dùng tài khoản tạm và dữ liệu riêng, được dọn sau test. Không dùng số liệu fixture như giao dịch kinh doanh.

## Kết quả lần bàn giao

Đã xác thực ngày 24/10/2026 trên môi trường cloud hiện tại:

| Hạng mục                                                   | Kết quả                                                         |
| ---------------------------------------------------------- | --------------------------------------------------------------- |
| `npm ci` từ lockfile, lifecycle scripts bị tắt theo .npmrc | Thành công, 479 package                                         |
| `npm run setup` trên DB đã có                              | Thành công; migration/seed chạy lại không ghi đè dữ liệu        |
| Tạo DB PostgreSQL trống, migrate, seed và migrate lần hai  | Thành công, 20 sản phẩm / 160 biến thể                          |
| `npm test` trên DB trống đã migrate/seed                   | **14/14 passed**, 0 failed, 0 skipped                           |
| `npm run build`                                            | Thành công, các route ứng dụng được build                       |
| `npm run typecheck`                                        | Thành công, có generate route types trước tsc                   |
| `npm run lint`                                             | Thành công                                                      |
| `npm run format:check`                                     | Thành công                                                      |
| `CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e`         | **10/10 passed**, 0 failed, 0 skipped; 5 desktop + 5 mobile     |
| Development server `npm run dev -- --port 3001`            | HTTP 200, trang chủ trả nội dung KH Fashion và dữ liệu sản phẩm |
| Kiểm tra ảnh / tràn ngang / lỗi JavaScript trong E2E       | Thành công trên các màn hình đã liệt kê                         |
| GitHub Actions CI                                          | Đã lưu workflow; chưa chạy trên GitHub                          |

DB trống dùng kiểm tra đã được xóa sau khi hoàn tất. Fixture E2E được dọn; không giữ đơn hàng kiểm thử làm số liệu demo. Ảnh giao diện tại [desktop](screenshots/home-desktop.png) và [mobile](screenshots/home-mobile.png).

Đã lưu install_script và start_skill trong draft cấu hình cloud và đọc lại xác nhận có dữ liệu. Việc này chưa Publish môi trường và chưa xác thực phục hồi snapshot trên một task mới.

## Chưa xác thực

- Merchant VNPay thật/sandbox bên ngoài, webhook qua Internet và đối soát tiền thực.
- Safari/Firefox, thiết bị vật lý, trình đọc màn hình chuyên dụng.
- Benchmark tải lớn, audit bảo mật độc lập hoặc khả năng phục hồi production.
- Deploy public và phục hồi snapshot trong task cloud mới.

Không có cơ chế bỏ assertion hay thay kiểm thử nghiệp vụ bằng kiểm tra PID/port. Build thành công không chứng minh mọi tính năng đã được kiểm thử; bảng trên nêu phạm vi bằng chứng cụ thể.
