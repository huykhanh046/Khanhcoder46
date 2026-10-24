# Phân tích yêu cầu

## Bối cảnh và phạm vi

Cửa hàng thời trang cần công bố sản phẩm, bán theo size/màu, theo dõi tồn kho và xử lý đơn. Thao tác thủ công dễ gây bán vượt tồn, áp dụng sai khuyến mãi và tạo đơn lặp. KH Fashion tập trung một cửa hàng, tiền VND, một kho logic và giao hàng trong Việt Nam.

Ba actor: Khách vãng lai; Khách hàng đã đăng nhập; Quản trị viên. VNPay là actor bên ngoài tùy chọn, chỉ sandbox. Không dùng microservices vì quy mô đồ án và yêu cầu transaction nhất quán.

## Yêu cầu chức năng

| Mã   | Yêu cầu                                                 | Hiện trạng                                                     |
| ---- | ------------------------------------------------------- | -------------------------------------------------------------- |
| FR01 | Tra cứu, lọc, sắp xếp, phân trang sản phẩm đang bán     | Đã triển khai                                                  |
| FR02 | Gallery, chọn size/màu, hiển thị tồn biến thể           | Đã triển khai                                                  |
| FR03 | Giỏ hàng thêm/sửa/xóa, lưu qua tải lại                  | Đã triển khai bằng localStorage                                |
| FR04 | Đăng ký, đăng nhập, đăng xuất, phân quyền               | Đã triển khai                                                  |
| FR05 | Hồ sơ, lưu/sửa/xóa tối đa 10 địa chỉ                    | Đã triển khai                                                  |
| FR06 | Báo giá server, áp voucher, checkout COD                | Đã triển khai                                                  |
| FR07 | Chống overselling và tạo đơn trùng                      | Đã triển khai và kiểm thử transaction                          |
| FR08 | Danh sách/chi tiết đơn riêng, hủy pending chưa trả tiền | Đã triển khai                                                  |
| FR09 | CRUD danh mục, tạo/sửa/ẩn sản phẩm và biến thể          | Đã triển khai; xóa mềm sản phẩm                                |
| FR10 | Nhập/điều chỉnh tồn biến thể, lịch sử biến động         | Đã triển khai                                                  |
| FR11 | Quản trị chuyển trạng thái, lưu lịch sử, ghi nhận COD   | Đã triển khai                                                  |
| FR12 | Tạo/sửa/bật tắt voucher, giới hạn đồng thời             | Đã triển khai                                                  |
| FR13 | Dashboard, khách hàng và lịch sử mua                    | Đã triển khai                                                  |
| FR14 | VNPay ký URL, xác minh IPN, hết hạn giữ hàng            | Đã triển khai mã và kiểm thử mô phỏng; chưa xác thực cổng thật |
| FR15 | Quản lý ảnh sản phẩm                                    | Đường dẫn tệp nội bộ, chưa upload qua UI                       |

## Yêu cầu phi chức năng

- NFR01: kiểm tra dữ liệu bằng Zod ở server, ràng buộc SQL cho stock/total/voucher.
- NFR02: mật khẩu bcrypt cost 12; cookie phiên mã hóa HttpOnly, SameSite=Lax; Secure khi HTTPS.
- NFR03: mọi API quản trị kiểm tra role; đơn và địa chỉ kiểm tra chủ sở hữu. Mutation kiểm tra Origin khớp APP_URL.
- NFR04: transaction Serializable, retry tối đa 5 lần khi xung đột; idempotency theo user + UUID. Số lượng mỗi biến thể tối đa 20 trong một đơn.
- NFR05: responsive; nhãn form, keyboard focus, skip link, thông báo lỗi, loading/empty state và reduced motion.
- NFR06: lockfile, migration có checksum, seed chạy lại không ghi đè dữ liệu, không lưu secret trong Git.
- NFR07: không có cam kết tải/số request mỗi giây khi chưa benchmark. Kiểm thử đồng thời hiện xác thực các tình huống nghiệp vụ quy mô nhỏ.

## Quy tắc và giả định

- Giá lưu dạng số nguyên VND. Thông tin sản phẩm/thuộc tính/đơn giá được chụp vào OrderItem khi đặt.
- Giá và tồn kho client chỉ để hiển thị; server đọc lại DB. Nếu tổng tiền UI đã xác nhận khác server, checkout báo xung đột để khách xem lại.
- Một giỏ hàng áp dụng tối đa một voucher. Miễn phí giao hàng xét tiền hàng trước giảm giá.
- Giữ tồn ngay khi tạo đơn; COD giữ tới giao hoặc hủy, VNPay unpaid hết hạn sau 15 phút qua worker.
- Hoàn tồn và lượt voucher trong cùng transaction khi hủy; đơn đã trả tiền cần đối soát/hoàn tiền riêng, không tự hủy.
- Doanh thu dashboard là tổng tiền đơn DELIVERED + PAID, có gồm vận chuyển, chưa trừ chi phí.
- Khách phải đăng nhập để checkout. Giỏ hàng không đồng bộ qua nhiều thiết bị.
- Tài khoản demo và ảnh minh họa không đại diện hoạt động kinh doanh thật.

## Ngoài phạm vi phiên bản này

Đa kho, phân quyền nhân viên chi tiết, trả hàng/hoàn tiền, khôi phục mật khẩu qua email, đơn khách vãng lai, vận chuyển qua API, flash sale, hệ thống gợi ý và cổng thanh toán live. Các gợi ý trong tài liệu ban đầu không được xem là các chức năng đã triển khai.
