import Link from 'next/link';
import type { Prisma } from '@prisma/client';
import { money, date, statusLabel } from '@/lib/utils';
import { ActionButton } from './account';
type Order = Prisma.OrderGetPayload<{ include: { items: true; events: true } }>;
export function OrderView({ order: o, admin = false }: { order: Order; admin?: boolean }) {
  return (
    <>
      <section className="panel">
        <div className="order-head">
          <div>
            <h2>Đơn #{o.id.slice(-8).toUpperCase()}</h2>
            <p className="muted" style={{ fontSize: 12 }}>
              {date(o.createdAt)}
            </p>
          </div>
          <span className={`badge ${o.status}`}>{statusLabel[o.status]}</span>
        </div>
        {o.items.map((i) => (
          <div key={i.id} className="cart-item">
            <img src={i.image} alt={i.productName} />
            <div className="info">
              <h3>{i.productName}</h3>
              <p className="muted" style={{ fontSize: 12 }}>
                {i.color} / {i.size} · {i.sku}
                <br />
                Số lượng: {i.quantity}
              </p>
              <span className="price">{money(i.price)}</span>
            </div>
            <strong>{money(i.price * i.quantity)}</strong>
          </div>
        ))}
        <div style={{ maxWidth: 360, marginLeft: 'auto', paddingTop: 15 }}>
          <div className="summary-row">
            <span>Tạm tính</span>
            <span>{money(o.subtotal)}</span>
          </div>
          <div className="summary-row">
            <span>Giảm giá {o.voucherCode && `(${o.voucherCode})`}</span>
            <span>−{money(o.discount)}</span>
          </div>
          <div className="summary-row">
            <span>Vận chuyển</span>
            <span>{money(o.shipping)}</span>
          </div>
          <div className="summary-row total">
            <span>Tổng cộng</span>
            <span>{money(o.total)}</span>
          </div>
        </div>
      </section>
      <div className="two-cols">
        <section className="panel">
          <h2>Thông tin giao hàng</h2>
          <p>
            <strong>{o.recipient}</strong>
            <br />
            {o.phone}
            <br />
            {o.address}
          </p>
          {o.note && <p className="muted">Ghi chú: {o.note}</p>}
        </section>
        <section className="panel">
          <h2>Thanh toán</h2>
          <p>{statusLabel[o.paymentMethod]}</p>
          <span className="badge">{statusLabel[o.paymentStatus]}</span>
          {o.paymentRef && <p style={{ fontSize: 11 }}>Mã giao dịch: {o.paymentRef}</p>}
          <p className="muted" style={{ fontSize: 12, marginTop: 15 }}>
            Thanh toán COD được ghi nhận khi đơn đã giao.
          </p>
        </section>
      </div>
      <section className="panel">
        <h2>Lịch sử đơn hàng</h2>
        <div className="timeline">
          {o.events.map((e) => (
            <p key={e.id}>
              {statusLabel[e.status]} — {e.message}
              <small>{date(e.createdAt)}</small>
            </p>
          ))}
        </div>
        <div className="inline-actions">
          {admin ? (
            ['PENDING', 'CONFIRMED'].includes(o.status) ? (
              <>
                <ActionButton
                  path={`admin/orders/${o.id}`}
                  method="PATCH"
                  data={{ status: o.status === 'PENDING' ? 'CONFIRMED' : 'SHIPPING' }}
                  label={o.status === 'PENDING' ? 'Xác nhận đơn' : 'Bắt đầu giao'}
                />
                {o.paymentStatus === 'UNPAID' && (
                  <ActionButton
                    path={`admin/orders/${o.id}`}
                    method="PATCH"
                    data={{ status: 'CANCELLED' }}
                    label="Hủy đơn"
                    confirmMessage="Hủy đơn và hoàn tồn kho?"
                  />
                )}
              </>
            ) : o.status === 'SHIPPING' ? (
              <ActionButton
                path={`admin/orders/${o.id}`}
                method="PATCH"
                data={{ status: 'DELIVERED' }}
                label="Đã giao thành công"
              />
            ) : null
          ) : o.status === 'PENDING' && o.paymentStatus === 'UNPAID' ? (
            <ActionButton
              path={`orders/${o.id}/cancel`}
              label="Hủy đơn hàng"
              confirmMessage="Bạn muốn hủy đơn hàng này?"
            />
          ) : null}
          <Link className="text-link" href={admin ? '/admin/don-hang' : '/tai-khoan/don-hang'}>
            Về danh sách đơn hàng
          </Link>
        </div>
      </section>
    </>
  );
}
