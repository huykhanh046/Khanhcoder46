import Link from 'next/link';
import { ArrowUpRight } from 'lucide-react';
export function Footer() {
  return (
    <footer className="footer">
      <div className="container footer-grid">
        <div>
          <Link href="/" className="brand">
            KH<span>FASHION</span>
            <i>®</i>
          </Link>
          <p>
            Ít hơn, nhưng tốt hơn.
            <br />
            Trang phục đồng hành cùng phong cách của bạn.
          </p>
          <span className="eyebrow">DESIGNED FOR EVERYDAY LIVING</span>
        </div>
        <div>
          <h3>Khám phá</h3>
          <Link href="/san-pham">Tất cả sản phẩm</Link>
          <Link href="/san-pham?sort=newest">Sản phẩm mới</Link>
          <Link href="/san-pham?sale=1">Ưu đãi hôm nay</Link>
        </div>
        <div>
          <h3>Hỗ trợ khách hàng</h3>
          <Link href="/chinh-sach">Giao hàng & Đổi trả</Link>
          <Link href="/chinh-sach#size">Hướng dẫn chọn size</Link>
          <Link href="/tai-khoan">Theo dõi đơn hàng</Link>
        </div>
        <div>
          <h3>Kết nối với KH</h3>
          <a href="mailto:hello@khfashion.example">
            hello@khfashion.example <ArrowUpRight size={14} />
          </a>
          <p>
            Dự án học tập KH Fashion.
            <br />
            Thông tin liên hệ và sản phẩm minh họa.
          </p>
        </div>
      </div>
      <div className="container footer-bottom">
        <span>© {new Date().getFullYear()} KH Fashion. Đồ án chuyên ngành.</span>
        <span>Made with care, worn with confidence.</span>
      </div>
    </footer>
  );
}
