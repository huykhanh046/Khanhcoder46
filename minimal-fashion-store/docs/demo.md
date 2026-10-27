# Hướng dẫn sử dụng và video demo 5–7 phút

Tài khoản xem README. Dữ liệu seed là demo. Thực hiện trên local, tiền COD ghi nhận là minh họa nghiệp vụ; không phải khoản chuyển tiền ngoài hệ thống.

## Hướng dẫn nhanh
Khách: xem catalog, filter, chọn size/màu, thêm giỏ, login, nhập địa chỉ và COD, xem `/orders`. Hủy khi tất cả đơn con pending. Sau giao hàng, đánh giá ở trang sản phẩm; đổi/trả ở chi tiết đơn trong 14 ngày. Yêu thích qua nút lưu/bỏ lưu. Địa chỉ và mật khẩu trong `/account`.

Seller: `/manage/products` tạo sản phẩm, thêm SKU/size/màu/giá/tồn, upload ảnh. Admin duyệt rồi mới bán. Điều chỉnh kho có lý do trong form sản phẩm; lịch sử ở `/manage/inventory`. Đơn chỉ thuộc shop của mình; chuyển trạng thái theo lựa chọn được phép. Đổi/trả ở `/manage/returns`; sau received có nút nhập lại kho một lần, cần xác nhận đã kiểm tra chất lượng.

Admin: `/admin` quản lý người dùng, duyệt shop, danh mục, banner, brand/tokens/menu/section và review. Các trang /admin/vouchers, /admin/collections, /admin/shipping và /admin/payments quản lý voucher, bộ sưu tập, phí giao và đối soát. `/manage/products` kiểm duyệt. Đơn COD sau delivered/completed ghi thu đúng tổng qua mã đối soát; refund không vượt tiền đã thu còn lại. Nhập mã mới cho một giao dịch mới; retry mã cũ cùng nội dung không ghi hai lần.

## Kịch bản
| Thời gian | Nội dung |
|---|---|
| 0:00–0:40 | Giới thiệu monolith Laravel/MySQL, tách presentation/service; cho xem ERD và variants/inventories |
| 0:40–1:40 | Catalog, lọc, chọn size/màu; biến thể hết hàng không được thêm; giỏ gồm hai shop |
| 1:40–2:40 | Login customer, voucher hợp lệ/sai, checkout COD; xem order tổng và hai order con, refresh vẫn có dữ liệu |
| 2:40–3:40 | Login Seller 1 chỉ thấy shop mình, đổi trạng thái một đơn con; dùng link shop khác nhận 403 |
| 3:40–4:30 | Admin giao/hoàn tất, ghi thu demo; dashboard trước và sau ghi thu; so sánh amount capture/refund |
| 4:30–5:20 | Chạy automated race: 2 process, stock=1, chỉ 1 success; giải thích transaction/lock và unique event key |
| 5:20–6:00 | VNPay local tests hash/amount/replay; nếu có merchant demo sandbox thật, nếu chưa nói rõ chưa xác minh gateway |
| 6:00–6:40 | Review/return, chỉnh thương hiệu/banner trong Admin, reload trang chủ; show docs/test log và giới hạn |

## Các câu hỏi bảo vệ
- Vì sao không dùng float? VND nguyên, intdiv basis points tránh sai số; snapshot không đổi khi sửa giá catalog.
- Vì sao check stock ở frontend chưa đủ? Hai request có thể cùng nhìn thấy 1; lock trong transaction tuần tự hóa write.
- Vì sao Return không xác nhận tiền? Browser do client kiểm soát; signed IPN và đối chiếu DB mới cập nhật.
- Vì sao chia order/shop_order? Seller xử lý/nhìn riêng; parent tổng hợp consistent, shipping/voucher phân bổ rõ.
- Hủy đơn đã trả tiền có hoàn ngay? Không. Ledger giữ capture; Admin đối soát và ghi refund thực tế.
- Mất email có mất đơn? Không. Notify sau commit qua queue; retry email độc lập.
- Nâng cấp sau đồ án? Kiểm chứng merchant, SMTP, restore backup và load test; API refund/đơn đổi tự động chỉ khi cần.
