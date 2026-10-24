import { pageUser } from '@/lib/auth';
import Link from 'next/link';
import { db } from '@/lib/db';
import { money } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { ActionButton } from '@/components/account';
export default async function Page({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; page?: string }>;
}) {
  await pageUser(true);
  const q = await searchParams,
    page = Math.max(1, Math.floor(Number(q.page) || 1));
  const where = q.q ? { name: { contains: q.q, mode: 'insensitive' as const } } : {};
  const [products, count] = await Promise.all([
    db.product.findMany({
      where,
      include: { category: true, variants: true },
      orderBy: { createdAt: 'desc' },
      take: 20,
      skip: (page - 1) * 20,
    }),
    db.product.count({ where }),
  ]);
  return (
    <section className="panel">
      <div className="section-title">
        <h2>Sản phẩm ({count})</h2>
        <Button asChild className="small">
          <Link href="/admin/san-pham/them">+ Thêm sản phẩm</Link>
        </Button>
      </div>
      <form className="inline-actions" style={{ marginBottom: 20 }}>
        <input
          style={{ maxWidth: 300 }}
          name="q"
          placeholder="Tìm tên sản phẩm..."
          aria-label="Tìm sản phẩm"
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
              <th>Sản phẩm</th>
              <th>Giá</th>
              <th>Tồn</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            {products.map((p) => (
              <tr key={p.id}>
                <td>
                  <Link href={`/admin/san-pham/${p.id}`} className="text-link">
                    {p.name}
                  </Link>
                  <small>{p.category.name}</small>
                </td>
                <td>{money(p.price)}</td>
                <td>{p.variants.reduce((s, v) => s + v.stock, 0)}</td>
                <td>
                  <span className="badge">{p.active ? 'Đang bán' : 'Đã ẩn'}</span>
                </td>
                <td>
                  <div className="inline-actions">
                    <Link className="text-link" href={`/admin/san-pham/${p.id}`}>
                      Sửa
                    </Link>
                    {p.active && (
                      <ActionButton
                        path={`admin/products/${p.id}`}
                        method="DELETE"
                        label="Ẩn"
                        confirmMessage="Ẩn sản phẩm khỏi cửa hàng?"
                      />
                    )}
                  </div>
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
            href={`/admin/san-pham?page=${p}&q=${encodeURIComponent(q.q || '')}`}
          >
            {p}
          </Link>
        ))}
      </nav>
    </section>
  );
}
