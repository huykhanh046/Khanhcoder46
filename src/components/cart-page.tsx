'use client';
import Link from 'next/link';
import { ShoppingBag, ArrowRight, Trash2 } from 'lucide-react';
import { useCart } from './cart-provider';
import { Button } from './ui/button';
import { money } from '@/lib/utils';
export function CartPage() {
  const { items, ready, update, remove } = useCart();
  const subtotal = items.reduce((s, i) => s + i.price * i.quantity, 0),
    shipping = subtotal >= 699000 ? 0 : 30000;
  return (
    <div className="container">
      <div className="breadcrumb">
        <Link href="/">Trang chủ</Link> / Giỏ hàng
      </div>
      <div className="page-heading">
        <span className="eyebrow">YOUR EVERYDAY PICKS</span>
        <h1>Giỏ hàng của bạn</h1>
        <p>{items.length} lựa chọn cho tủ đồ của bạn.</p>
      </div>
      {!ready ? (
        <div className="skeleton" />
      ) : items.length ? (
        <div className="cart-grid">
          <div>
            {items.map((i) => (
              <div className="cart-item" key={i.variantId}>
                <img src={i.image} alt={i.name} />
                <div className="info">
                  <Link href={`/san-pham/${i.slug}`}>
                    <h3>{i.name}</h3>
                  </Link>
                  <p className="muted" style={{ fontSize: 12, margin: '0 0 6px' }}>
                    {i.color} / {i.size}
                  </p>
                  <div className="price">{money(i.price)}</div>
                  <div className="quantity">
                    <button
                      aria-label={`Giảm ${i.name}`}
                      disabled={i.quantity <= 1}
                      onClick={() => update(i.variantId, i.quantity - 1)}
                    >
                      −
                    </button>
                    <span>{i.quantity}</span>
                    <button
                      aria-label={`Tăng ${i.name}`}
                      disabled={i.quantity >= Math.min(i.stock, 20)}
                      onClick={() => update(i.variantId, i.quantity + 1)}
                    >
                      +
                    </button>
                  </div>
                </div>
                <div style={{ textAlign: 'right' }}>
                  <div className="price">{money(i.price * i.quantity)}</div>
                  <button
                    className="btn btn-ghost"
                    aria-label={`Xóa ${i.name}`}
                    onClick={() => remove(i.variantId)}
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>
            ))}
            <p className="muted" style={{ fontSize: 12, marginTop: 20 }}>
              Giá và tồn kho được kiểm tra lại khi xác nhận đặt hàng.
            </p>
            <Link href="/san-pham" className="text-link">
              Tiếp tục mua sắm <ArrowRight size={14} />
            </Link>
          </div>
          <aside className="summary">
            <h2>Tóm tắt giỏ hàng</h2>
            <div className="summary-row">
              <span>Tạm tính</span>
              <span>{money(subtotal)}</span>
            </div>
            <div className="summary-row">
              <span>Phí vận chuyển</span>
              <span>{shipping ? money(shipping) : 'Miễn phí'}</span>
            </div>
            <div className="summary-row total">
              <span>Dự kiến</span>
              <span>{money(subtotal + shipping)}</span>
            </div>
            <small>
              {subtotal < 699000
                ? `Mua thêm ${money(699000 - subtotal)} để được miễn phí vận chuyển.`
                : 'Đơn hàng của bạn được miễn phí vận chuyển.'}
            </small>
            <Button asChild className="full">
              <Link href="/thanh-toan">
                Tiến hành thanh toán <ArrowRight size={16} />
              </Link>
            </Button>
            <p style={{ fontSize: 11, color: 'var(--muted)', marginTop: 14 }}>
              Voucher được áp dụng ở bước thanh toán.
            </p>
          </aside>
        </div>
      ) : (
        <div style={{ paddingBottom: 70 }} className="empty">
          <ShoppingBag size={40} style={{ margin: 'auto' }} strokeWidth={1} />
          <h2>Giỏ hàng đang chờ bạn</h2>
          <p>Khám phá một vài món đồ phù hợp với phong cách của bạn.</p>
          <Button asChild>
            <Link href="/san-pham">Khám phá bộ sưu tập</Link>
          </Button>
        </div>
      )}
    </div>
  );
}
