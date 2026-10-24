# Hồ sơ đồ án KH Fashion

Bộ tài liệu mô tả ứng dụng thực tế trong repository, tách phần đã xây dựng và phần phát triển tiếp.

1. [Phạm vi và yêu cầu](requirements.md): actor, chức năng, phi chức năng và tiêu chí nghiệm thu.
2. [Đặc tả Use Case](use-cases.md): kịch bản chính, ngoại lệ, điều kiện trước/sau.
3. [Thiết kế hệ thống](design.md): ERD, kiến trúc, sequence, activity, mô hình module.
4. [Vận hành](operations.md): cài đặt, migration, cấu hình, VNPay, dữ liệu và bảo mật.
5. [Kiểm thử và truy vết](testing.md): kịch bản, kết quả thực chạy và giới hạn bằng chứng.
6. [API của ứng dụng](api.md): endpoint, phân quyền và dữ liệu checkout.
7. [Đề cương báo cáo và bảo vệ](report-outline.md): cấu trúc báo cáo giữa kỳ/cuối kỳ, slide.

## Bản đồ màn hình

| Nhóm                   | Đường dẫn                                                                                                              |
| ---------------------- | ---------------------------------------------------------------------------------------------------------------------- |
| Trang chủ              | `/`                                                                                                                    |
| Tìm kiếm và bộ sưu tập | `/san-pham`                                                                                                            |
| Chi tiết               | `/san-pham/[slug]`                                                                                                     |
| Giỏ hàng, checkout     | `/gio-hang`, `/thanh-toan`                                                                                             |
| Tài khoản              | `/dang-nhap`, `/dang-ky`, `/tai-khoan`                                                                                 |
| Địa chỉ và đơn hàng    | `/tai-khoan/dia-chi`, `/tai-khoan/don-hang`, `/tai-khoan/don-hang/[id]`                                                |
| Quản trị               | `/admin`, `/admin/san-pham`, `/admin/danh-muc`, `/admin/kho`, `/admin/don-hang`, `/admin/voucher`, `/admin/khach-hang` |

## Wireframe theo giao diện đã triển khai

```text
Desktop storefront
┌─────────────────────────────────────────────────────────────┐
│ Thanh thông báo / Logo / Danh mục / Tìm kiếm / Tài khoản / Giỏ │
├────────────────────────────┬────────────────────────────────┤
│ Tiêu đề bộ sưu tập + CTA    │ Khung sản phẩm nổi bật         │
├────────────────────────────┴────────────────────────────────┤
│ Thông tin giao hàng / an tâm / chất liệu                     │
│ Danh mục: Áo / Quần / Váy / Áo khoác                         │
│ Lưới sản phẩm 4 cột                                         │
│ Nội dung bộ sưu tập + CTA / Footer                          │
└─────────────────────────────────────────────────────────────┘
Mobile: menu thu gọn, hero một cột, sản phẩm hai cột.
Checkout: địa chỉ + ghi chú + phương thức | tóm tắt + voucher.
Admin: menu bên trái | chỉ số, bảng dữ liệu và biểu mẫu.
```

Ảnh chụp giao diện sau kiểm thử nằm tại `docs/screenshots` nếu có trong bản bàn giao. Ảnh hàng hóa trong UI là minh họa SVG nội bộ.
