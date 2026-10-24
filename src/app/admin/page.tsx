import { pageUser } from '@/lib/auth';
import Link from 'next/link';
import { db } from '@/lib/db';
import { money, date, statusLabel } from '@/lib/utils';
export default async function Page() {
  await pageUser(true);
  const [revenue, orders, pending, low, recent] = await Promise.all([
    db.order.aggregate({
      where: { status: 'DELIVERED', paymentStatus: 'PAID' },
      _sum: { total: true },
    }),
    db.order.count(),
    db.order.count({ where: { status: 'PENDING' } }),
    db.variant.count({ where: { active: true, stock: { lte: 5 }, product: { active: true } } }),
    db.order.findMany({
      orderBy: { createdAt: 'desc' },
      take: 8,
      include: { user: { select: { name: true } } },
    }),
  ]);
  return (
    <>
      <div className="stats">
        {[
          ['Doanh thu đã giao', money(revenue._sum.total || 0)],
          ['Tổng đơn hàng', orders],
          ['Chờ xác nhận', pending],
          ['Biến thể sắp hết', low],
        ].map(([title, value]) => (
          <div className="stat" key={title}>
            <p>{title}</p>
            <strong>{value}</strong>
          </div>
        ))}
      </div>
      <section className="panel">
        <h2>Đơn hàng gần đây</h2>
        {recent.length ? (
          <div className="table-scroll">
            <table>
              <thead>
                <tr>
                  <th>Mã đơn / Khách hàng</th>
                  <th>Ngày đặt</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                {recent.map((o) => (
                  <tr key={o.id}>
                    <td>
                      <Link className="text-link" href={`/admin/don-hang/${o.id}`}>
                        #{o.id.slice(-8).toUpperCase()}
                      </Link>
                      <small>{o.user.name}</small>
                    </td>
                    <td>{date(o.createdAt)}</td>
                    <td>{money(o.total)}</td>
                    <td>
                      <span className={`badge ${o.status}`}>{statusLabel[o.status]}</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        ) : (
          <p className="muted">Chưa có đơn hàng. Đơn mới sẽ xuất hiện tại đây.</p>
        )}
      </section>
      <section className="panel">
        <h2>Cần chú ý</h2>
        <p className="muted">Có {low} biến thể đang còn tối đa 5 sản phẩm.</p>
        <Link href="/admin/kho?low=1" className="text-link">
          Kiểm tra tồn kho →
        </Link>
      </section>
    </>
  );
}
