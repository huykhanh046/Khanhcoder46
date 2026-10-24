'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
export function AdminNav() {
  const path = usePathname();
  return (
    <nav className="side-nav" aria-label="Quản trị">
      {[
        ['/admin', 'Tổng quan'],
        ['/admin/san-pham', 'Sản phẩm'],
        ['/admin/danh-muc', 'Danh mục'],
        ['/admin/kho', 'Tồn kho'],
        ['/admin/don-hang', 'Đơn hàng'],
        ['/admin/voucher', 'Voucher'],
        ['/admin/khach-hang', 'Khách hàng'],
      ].map(([href, title]) => (
        <Link
          key={href}
          href={href}
          className={
            path === href || (href !== '/admin' && path.startsWith(href)) ? 'selected' : ''
          }
        >
          {title}
        </Link>
      ))}
    </nav>
  );
}
