import { pageUser } from '@/lib/auth';
import Link from 'next/link';
import { db } from '@/lib/db';
import { date, money } from '@/lib/utils';
import { Button } from '@/components/ui/button';
export default async function Page({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; id?: string; page?: string }>;
}) {
  await pageUser(true);
  const q = await searchParams,
    page = Math.max(1, Math.floor(Number(q.page) || 1));
  const where = {
    role: 'CUSTOMER' as const,
    ...(q.q
      ? {
          OR: [
            { name: { contains: q.q, mode: 'insensitive' as const } },
            { email: { contains: q.q, mode: 'insensitive' as const } },
          ],
        }
      : {}),
  };
  const [users, count] = await Promise.all([
    db.user.findMany({
      where,
      include: { _count: { select: { orders: true } } },
      orderBy: { createdAt: 'desc' },
      take: 20,
      skip: (page - 1) * 20,
    }),
    db.user.count({ where }),
  ]);
  const selected = q.id
    ? await db.user.findFirst({
        where: { id: q.id, role: 'CUSTOMER' },
        select: { name: true, orders: { orderBy: { createdAt: 'desc' }, take: 50 } },
      })
    : null;
  return (
    <>
      <section className="panel">
        <h2>Khách hàng ({count})</h2>
        <form className="inline-actions" style={{ marginBottom: 20 }}>
          <input
            style={{ maxWidth: 300 }}
            name="q"
            aria-label="Tìm khách hàng"
            placeholder="Tên hoặc email"
            defaultValue={q.q}
          />
          <Button variant="outline" className="small">
            Tìm kiếm
          </Button>
        </form>
        <div className="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Khách hàng</th>
                <th>Ngày tham gia</th>
                <th>Số đơn</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id}>
                  <td>
                    {u.name}
                    <small>{u.email}</small>
                  </td>
                  <td>{date(u.createdAt)}</td>
                  <td>{u._count.orders}</td>
                  <td>
                    <Link className="text-link" href={`/admin/khach-hang?id=${u.id}`}>
                      Lịch sử mua
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <nav className="pagination">
          {Array.from({ length: Math.ceil(count / 20) }, (_, i) => i + 1).map((p) => (
            <Link
              key={p}
              className={page === p ? 'selected' : ''}
              href={`/admin/khach-hang?page=${p}&q=${encodeURIComponent(q.q || '')}`}
            >
              {p}
            </Link>
          ))}
        </nav>
      </section>
      {selected && (
        <section className="panel">
          <h2>Lịch sử của {selected.name}</h2>
          {selected.orders.length ? (
            selected.orders.map((o) => (
              <p key={o.id}>
                <Link className="text-link" href={`/admin/don-hang/${o.id}`}>
                  #{o.id.slice(-8).toUpperCase()} · {money(o.total)} · {date(o.createdAt)}
                </Link>
              </p>
            ))
          ) : (
            <p>Khách hàng chưa đặt hàng.</p>
          )}
        </section>
      )}
    </>
  );
}
