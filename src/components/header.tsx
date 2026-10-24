'use client';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { ShoppingBag, UserRound, Menu, X, Search, ArrowUpRight } from 'lucide-react';
import { useState } from 'react';
import { useCart } from './cart-provider';
export function Header({ user }: { user: { name: string; role: string } | null }) {
  const { items, ready } = useCart();
  const path = usePathname();
  const [open, setOpen] = useState(false);
  return (
    <>
      <div className="announcement">
        Một chút mới mẻ cho ngày thường{' '}
        <span>
          Miễn phí vận chuyển từ 699.000đ <ArrowUpRight size={12} />
        </span>
      </div>
      <header className="header">
        <div className="container header-inner">
          <Link href="/" className="brand" aria-label="KH Fashion trang chủ">
            KH<span>FASHION</span>
            <i>®</i>
          </Link>
          <nav className={open ? 'main-nav open' : 'main-nav'} aria-label="Điều hướng chính">
            <Link
              onClick={() => setOpen(false)}
              className={path === '/san-pham' ? 'active' : ''}
              href="/san-pham"
            >
              Bộ sưu tập
            </Link>
            <Link onClick={() => setOpen(false)} href="/san-pham?category=ao">
              Áo
            </Link>
            <Link onClick={() => setOpen(false)} href="/san-pham?category=quan">
              Quần
            </Link>
            <Link onClick={() => setOpen(false)} href="/san-pham?category=vay">
              Váy & Đầm
            </Link>
            <Link onClick={() => setOpen(false)} href="/san-pham?category=ao-khoac">
              Áo khoác
            </Link>
          </nav>
          <div className="header-actions">
            <Link href="/san-pham" aria-label="Tìm kiếm">
              <Search size={20} />
            </Link>
            <Link
              href={user ? '/tai-khoan' : '/dang-nhap'}
              aria-label={user ? 'Tài khoản' : 'Đăng nhập'}
            >
              <UserRound size={20} />
            </Link>
            <Link href="/gio-hang" className="bag-link" aria-label="Giỏ hàng">
              <ShoppingBag size={20} />
              <span>{ready ? items.reduce((s, i) => s + i.quantity, 0) : 0}</span>
            </Link>
            {user?.role === 'ADMIN' && (
              <Link href="/admin" className="admin-link">
                Quản trị
              </Link>
            )}
            <button
              className="mobile-menu"
              aria-label="Mở menu"
              aria-expanded={open}
              onClick={() => setOpen(!open)}
            >
              {open ? <X size={22} /> : <Menu size={22} />}
            </button>
          </div>
        </div>
      </header>
    </>
  );
}
