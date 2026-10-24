import Link from 'next/link';
import { pageUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { money, date, statusLabel } from '@/lib/utils';
export default async function Page() {
  const user = await pageUser();
  const orders = await db.order.findMany({
    where: { userId: user.id },
    orderBy: { createdAt: 'desc' },
    take: 100,
  });
  return (
    <section className="panel">
      <h2>Đơn hàng của tôi</h2>
      {!orders.length ? (
        <div className="empty">
          <p>Bạn chưa có đơn hàng nào.</p>
          <Link className="text-link" href="/san-pham">
            Khám phá sản phẩm
          </Link>
        </div>
      ) : (
        <div className="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th />
              </tr>
            </thead>
            <tbody>
              {orders.map((o) => (
                <tr key={o.id}>
                  <td>#{o.id.slice(-8).toUpperCase()}</td>
                  <td>{date(o.createdAt)}</td>
                  <td>{money(o.total)}</td>
                  <td>
                    <span className={`badge ${o.status}`}>{statusLabel[o.status]}</span>
                  </td>
                  <td>
                    <Link href={`/tai-khoan/don-hang/${o.id}`} className="text-link">
                      Chi tiết
                    </Link>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <p className="muted" style={{ fontSize: 11, marginTop: 20 }}>
        Hiển thị tối đa 100 đơn gần nhất.
      </p>
    </section>
  );
}
