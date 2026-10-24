# API của ứng dụng

Route handler tại `src/app/api/[...path]/route.ts`; request ghi dữ liệu dùng JSON, cookie phiên và Origin bằng APP_URL. Trả lỗi dưới dạng `{ "error": "Thông báo" }`. Mã chính: 400 validation/nghiệp vụ, 401 chưa đăng nhập, 403 thiếu quyền/Origin sai, 404 không tồn tại hoặc không thuộc chủ sở hữu, 409 xung đột, 429 giới hạn đăng nhập.

| Method       | Đường dẫn dưới `/api` | Quyền             | Nội dung                                                                                  |
| ------------ | --------------------- | ----------------- | ----------------------------------------------------------------------------------------- |
| POST         | auth/register         | Công khai         | name, email, password; luôn tạo CUSTOMER                                                  |
| POST         | auth/login            | Công khai         | email, password                                                                           |
| POST         | auth/logout           | Có/không có phiên | Hủy cookie                                                                                |
| PATCH        | account/profile       | Khách đăng nhập   | name                                                                                      |
| POST         | account/addresses     | Khách đăng nhập   | name, phone, detail                                                                       |
| PATCH        | account/addresses/:id | Chủ địa chỉ       | name, phone, detail                                                                       |
| DELETE       | account/addresses/:id | Chủ địa chỉ       | Xóa địa chỉ                                                                               |
| POST         | orders/quote          | Khách đăng nhập   | items, voucher; trả subtotal/discount/shipping/total                                      |
| POST         | orders                | Khách đăng nhập   | Xem ví dụ bên dưới                                                                        |
| POST         | orders/:id/cancel     | Chủ đơn           | Hủy pending/unpaid                                                                        |
| POST         | admin/categories      | ADMIN             | name, slug                                                                                |
| PATCH/DELETE | admin/categories/:id  | ADMIN             | Sửa / xóa danh mục trống                                                                  |
| POST         | admin/products        | ADMIN             | name, slug, description, material, price, compareAt, categoryId, images, active, featured |
| PATCH/DELETE | admin/products/:id    | ADMIN             | Sửa toàn bộ / ẩn sản phẩm                                                                 |
| POST         | admin/variants        | ADMIN             | productId, sku, size, color, active; stock ban đầu 0                                      |
| PATCH        | admin/variants/:id    | ADMIN             | sku, size, color, active                                                                  |
| POST         | admin/inventory/:id   | ADMIN             | delta nguyên khác 0, reason                                                               |
| POST         | admin/vouchers        | ADMIN             | code, percent, minOrder, maxDiscount, usageLimit, startsAt, expiresAt, active             |
| PATCH        | admin/vouchers/:id    | ADMIN             | Các trường voucher, không giảm limit dưới used                                            |
| PATCH        | admin/orders/:id      | ADMIN             | status theo transition hợp lệ                                                             |
| GET          | payments/vnpay/ipn    | Chữ ký VNPay      | Query có chữ ký HMAC-SHA512; trả RspCode/Message                                          |

Dữ liệu đọc phục vụ trang web được lấy trong Server Components, chưa cung cấp public REST GET cho catalog/admin. API ghi chỉ phục vụ cùng origin; chưa có token API cho ứng dụng bên thứ ba.

## Ví dụ checkout

```json
{
  "items": [{ "variantId": "ID lấy từ DB", "quantity": 2 }],
  "recipient": "Nguyễn Minh Anh",
  "phone": "0901234567",
  "address": "123 Nguyễn Huệ, phường Sài Gòn, TP. Hồ Chí Minh",
  "note": "Giao giờ hành chính",
  "voucher": "WELCOME10",
  "paymentMethod": "COD",
  "expectedTotal": 478200,
  "idempotencyKey": "c8f3260b-7d19-4b5a-8a94-417bd4d79950"
}
```

Giá trị mẫu ứng với hai áo Essential 249.000đ. variantId và tổng tiền phải lấy từ dữ liệu/báo giá hiện tại. expectedTotal dùng phát hiện giá thay đổi, không dùng làm nguồn tính tiền. Server trả `{id,paymentUrl}`; COD có paymentUrl=null. Gửi lại cùng userId/UUID trả đơn cũ ngay cả khi giỏ thay đổi: một key đại diện một lần mua, không tái sử dụng cho lần mua khác.

Sản phẩm/biến thể được ẩn để bảo toàn lịch sử. Không có API xóa đơn hoặc xóa khách hàng. API không trả passwordHash cho trình duyệt.
