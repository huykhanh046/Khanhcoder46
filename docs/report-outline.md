# Đề cương báo cáo và bảo vệ

## Báo cáo giữa kỳ

1. Giới thiệu: lý do chọn đề tài, mục tiêu phân tích hệ thống bán thời trang, phạm vi một cửa hàng.
2. Khảo sát: mô tả cách bán thủ công, rủi ro size/màu, tồn kho và đơn trùng. Chưa có khảo sát người dùng thật; chỉ trình bày giả định, không bịa số liệu.
3. Yêu cầu: actor, danh sách chức năng, yêu cầu phi chức năng, quy tắc nghiệp vụ theo requirements.md.
4. Use Case: chuyển bản đồ actor sang ký pháp UML theo yêu cầu giảng viên, dùng đặc tả use-cases.md.
5. Dữ liệu: ERD, mô hình quan hệ từ schema Prisma, unique/FK/CHECK và snapshot chi tiết đơn.
6. Tiến độ: đánh dấu chức năng thực tế đã có bằng commit/ảnh và kết quả chạy.

## Báo cáo cuối kỳ

Bổ sung kiến trúc, sequence cho checkout/cạnh tranh tồn/IPN, activity xử lý đơn, mô hình module; trình bày UI desktop/mobile; kiểm thử và ma trận truy vết; đánh giá hạn chế và hướng phát triển. Chỉ mô tả VNPay là tích hợp sandbox chưa xác thực bên ngoài, không viết như thanh toán live đã vận hành.

Thông tin sinh viên, giảng viên, trường, biểu mẫu và tiêu chí chấm chưa được cung cấp; bổ sung theo mẫu chính thức. Bộ Markdown này là nguồn nội dung báo cáo, chưa phải file Word/PDF theo mẫu trường.

## Kịch bản demo 8–10 phút

1. Trang chủ, lọc danh mục, mở chi tiết và đổi size/màu.
2. Giỏ hàng: tăng số lượng và tải lại để thấy lưu trạng thái.
3. Đăng nhập khách demo, checkout chọn địa chỉ và áp WELCOME10; giải thích server tính tiền.
4. Tạo COD, mở lịch sử đơn; chỉ ra snapshot đơn giá và thông tin giao hàng.
5. Đăng nhập admin, xác nhận → giao → hoàn tất; dashboard phản ánh doanh thu.
6. Mở tồn kho theo SKU và lịch sử thay đổi; tạo biến thể mới tồn 0 rồi nhập kho.
7. Chạy test tranh mua sản phẩm cuối và idempotency, giải thích transaction.
8. Kết luận phần đã làm và giới hạn thanh toán/hoàn tiền/giao vận.

Dùng dữ liệu giả. Đặt hàng demo làm thay đổi tồn và lượt voucher; không chạy seed để reset vì seed giữ nguyên dữ liệu hiện có. Muốn làm lại từ đầu hãy dùng DB demo mới hoặc khôi phục backup demo đã lưu.

## Gợi ý 10 slide

1. Đề tài và mục tiêu.
2. Bài toán và phạm vi.
3. Actor, Use Case chính.
4. Kiến trúc và công nghệ.
5. ERD / Product–Variant–OrderItem.
6. Checkout, idempotency và tranh chấp tồn.
7. State machine đơn / COD / thanh toán sandbox.
8. Giao diện và demo.
9. Kiểm thử thực chạy, ma trận yêu cầu.
10. Kết quả, giới hạn, hướng phát triển.

## Các câu hỏi cần chuẩn bị

- Vì sao SKU ở Variant thay vì Product?
- Tại sao đơn lưu lại giá và tên thay vì lấy từ Product hiện tại?
- Hai khách mua sản phẩm cuối được giải quyết như thế nào?
- Bấm nút đặt hai lần có tạo hai đơn không?
- Hủy đơn lặp có hoàn kho hai lần không?
- Vì sao không tin trang return URL để đánh dấu đã thanh toán?
- Doanh thu dashboard tính theo trạng thái nào?
- Phần nào đã kiểm thử tự động, phần nào chưa được xác thực bên ngoài?
