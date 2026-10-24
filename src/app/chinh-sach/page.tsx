export const metadata = { title: 'Chính sách mua hàng' };
export default function Page() {
  return (
    <article className="container policy">
      <div className="page-heading" style={{ paddingTop: 40 }}>
        <span className="eyebrow">WE ARE HERE TO HELP</span>
        <h1>Chính sách mua hàng</h1>
        <p>Thông tin áp dụng cho website trình diễn của đồ án KH Fashion.</p>
      </div>
      <h2>Giao hàng & thanh toán</h2>
      <p>
        Phí giao hàng cố định 30.000đ. Miễn phí với giá trị hàng trước giảm giá từ 699.000đ. COD
        thanh toán khi nhận hàng; VNPay chỉ dùng sandbox khi được cấu hình. Website demo không phát
        sinh giao vận thực tế.
      </p>
      <h2>Hủy đơn & đổi trả</h2>
      <p>
        Bạn có thể tự hủy đơn đang chờ xác nhận và chưa thanh toán. Đơn đã xác nhận cần quản trị
        viên xử lý. Khi hủy hợp lệ, tồn kho và lượt voucher được hoàn lại một lần. Đơn đã giao không
        thể hủy. Quy trình trả hàng và hoàn tiền chưa được triển khai trong phiên bản này.
      </p>
      <h2 id="size">Hướng dẫn chọn size</h2>
      <p>Bảng tham khảo cho mẫu thiết kế demo. Số đo thực tế có thể khác theo sản phẩm.</p>
      <div className="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Size</th>
              <th>Ngực (cm)</th>
              <th>Eo (cm)</th>
              <th>Hông (cm)</th>
            </tr>
          </thead>
          <tbody>
            {[
              ['S', '82–86', '64–68', '88–92'],
              ['M', '86–90', '68–72', '92–96'],
              ['L', '90–94', '72–76', '96–100'],
              ['XL', '94–100', '76–82', '100–106'],
            ].map((row) => (
              <tr key={row[0]}>
                {row.map((v, i) => (
                  <td key={i}>{v}</td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <h2>Dữ liệu & quyền riêng tư</h2>
      <p>
        Thông tin tài khoản và đơn hàng được lưu trong cơ sở dữ liệu của dự án. Mật khẩu được băm;
        phiên đăng nhập dùng cookie HttpOnly. Chỉ nhập dữ liệu giả khi thử nghiệm. Không dùng thông
        tin thẻ ngân hàng thật trong môi trường sandbox.
      </p>
    </article>
  );
}
