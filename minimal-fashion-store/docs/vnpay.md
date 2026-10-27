# VNPay Sandbox

Nguồn tham chiếu giao thức: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html và https://sandbox.vnpayment.vn/apis/docs/huong-dan-tich-hop/ . Chưa xác nhận giao dịch với merchant thật trong môi trường này; cần đối chiếu tài liệu mới nhất khi nhận tài khoản.

## Cấu hình
```dotenv
APP_URL=https://your-https-domain.example
VNPAY_TMN_CODE=<merchant sandbox>
VNPAY_HASH_SECRET=<secret sandbox>
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
```
Không commit secret. Chạy `php artisan config:clear` và khởi động lại worker. Đăng ký IPN URL `https://your-https-domain.example/payments/ipn` trong cấu hình merchant. Return URL được backend tạo từ route `/payments/return`. IPN phải truy cập được từ máy chủ VNPay; localhost không nhận callback Internet.

## Giao thức triển khai
`VnpayService`: Version 2.1.0, Command pay, VND; số tiền gửi = VND nguyên × 100; TxnRef là payments.reference UUID unique. CreateDate/ExpireDate theo Asia/Ho_Chi_Minh. Sort tham số vnp_* theo tên, URL encode RFC1738, HMAC SHA512, loại SecureHash/SecureHashType khỏi chuỗi ký.

IPN GET kiểm chữ ký bằng hash_equals, merchant, currency nếu gateway gửi, tham chiếu đơn, số tiền khớp và trạng thái. Lock order trong transaction, tạo capture phân bổ đơn con với event_key unique. Callback trùng trả 02, không ghi thu lại. Hash sai trả 97, sai amount 04, không có đơn 01. Success chỉ khi cả ResponseCode và TransactionStatus là 00. Thất bại trả kho, hủy đơn và ghi failed event.

Return chỉ đọc trạng thái database và chỉ cho chủ đơn xem. Nó không ghi nhận thanh toán. Callback hết hạn bị từ chối; không tự khôi phục kho đã giải phóng. Giao dịch gateway có tiền nhưng local từ chối cần Admin đối soát để xử lý thực tế, không coi là đã hoàn.

## Checklist sandbox cần thực hiện với merchant
- Success: đơn paid, đúng amount, ledger capture duy nhất, reserved chưa trừ stock đến khi giao.
- Hủy/thất bại: payment failed, reserved giảm đúng một lần.
- Replay IPN: không tăng ledger/không thay đổi kho.
- Amount/hash sai: không có capture.
- Tắt trình duyệt trước Return: IPN vẫn xác nhận độc lập.
- Hết 15 phút: scheduler release; IPN muộn không chiếm lại hàng.
- Multi-shop: tổng capture phân bổ = total đơn tổng.

Automated tests ký payload bằng secret test chỉ xác minh logic local, không thay thế kiểm chứng tương thích gateway thực. Chưa tích hợp API refund/query của VNPay; hoàn tiền ghi sổ thủ công sau thao tác đối soát thực tế, theo phạm vi đồ án.

## Nhật ký và xử lý tranh chấp
Bảng payment_callbacks lưu fingerprint payload, mã tham chiếu, kết quả chữ ký, verdict và attempts; không lưu secret/raw payload. Admin xem tại /admin/payments. Thiếu required fields hoặc transaction number thành công không hợp lệ bị từ chối; exception xử lý trả RspCode 99 để gateway thử lại.

VNPay pending không được hủy một shop riêng, vì amount URL đã cố định. Hủy toàn đơn và IPN khóa cùng parent: nếu IPN thắng thì capture được giữ để đối soát hoàn; nếu hủy thắng thì IPN bị từ chối, không ghi thu. Không tự đánh dấu refunded cho cả hai trường hợp.
