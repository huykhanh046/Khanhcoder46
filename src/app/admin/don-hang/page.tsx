import { pageUser } from '@/lib/auth';
import Link from 'next/link';
import type { OrderStatus } from '@prisma/client';
import { db } from '@/lib/db';
import { money, date, statusLabel } from '@/lib/utils';
import { Button } from '@/components/ui/button';
export default async function Page({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; status?: string; page?: string }>;
}) {
  await pageUser(true);
  const q = await searchParams,
    page = Math.max(1, Math.floor(Number(q.page) || 1));
  const statuses = ['PENDING', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED'];
  const where = {
    ...(q.status && statuses.includes(q.status) ? { status: q.status as OrderStatus } : {}),
    ...(q.q
      ? {
          OR: [
            { id: { contains: q.q, mode: 'insensitive' as const } },
            { recipient: { contains: q.q, mode: 'insensitive' as const } },
            { phone: { contains: q.q } },
          ],
        }
      : {}),
  };
  const [orders, count] = await Promise.all([
    db.order.findMany({
      where,
      include: { user: { select: { email: true } } },
      orderBy: { createdAt: 'desc' },
      take: 20,
      skip: (page - 1) * 20,
    }),
    db.order.count({ where }),
  ]);
  return (
    <section className="panel">
      <h2>Đơn hàng ({count})</h2>
      <form className="inline-actions" style={{ marginBottom: 20 }}>
        <input
          name="q"
          style={{ maxWidth: 260 }}
          placeholder="Mã đơn, tên, số điện thoại"
          aria-label="Tìm đơn hàng"
          defaultValue={q.q}
        />
        <select style={{ maxWidth: 190 }} name="status" defaultValue={q.status || ''}>
          <option value="">Tất cả trạng thái</option>
          {statuses.map((s) => (
            <option value={s} key={s}>
              {statusLabel[s]}
            </option>
          ))}
        </select>
        <Button className="small" variant="outline">
          Lọc
        </Button>
      </form>
      <div className="table-scroll">
        <table>
          <thead>
            <tr>
              <th>Mã đơn / Khách hàng</th>
              <th>Ngày đặt</th>
              <th>Tổng tiền</th>
              <th>Trạng thái</th>
              <th>Thanh toán</th>
            </tr>
          </thead>
          <tbody>
            {orders.map((o) => (
              <tr key={o.id}>
                <td>
                  <Link className="text-link" href={`/admin/don-hang/${o.id}`}>
                    #{o.id.slice(-8).toUpperCase()}
                  </Link>
                  <small>
                    {o.recipient} · {o.phone}
                  </small>
                </td>
                <td>{date(o.createdAt)}</td>
                <td>{money(o.total)}</td>
                <td>
                  <span className={`badge ${o.status}`}>{statusLabel[o.status]}</span>
                </td>
                <td>
                  {o.paymentMethod}
                  <small>{statusLabel[o.paymentStatus]}</small>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {!orders.length && <p className="muted">Không có đơn phù hợp.</p>}
      <nav className="pagination">
        {Array.from({ length: Math.ceil(count / 20) }, (_, i) => i + 1).map((p) => (
          <Link
            key={p}
            className={page === p ? 'selected' : ''}
            href={`/admin/don-hang?page=${p}&q=${encodeURIComponent(q.q || '')}&status=${q.status || ''}`}
          >
            {p}
          </Link>
        ))}
      </nav>
    </section>
  );
}
