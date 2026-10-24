import { pageUser } from '@/lib/auth';
import Link from 'next/link';
import { db } from '@/lib/db';
import { InventoryForm } from '@/components/admin-forms';
import { Button } from '@/components/ui/button';
import { date } from '@/lib/utils';
export default async function Page({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; low?: string; page?: string }>;
}) {
  await pageUser(true);
  const q = await searchParams,
    page = Math.max(1, Math.floor(Number(q.page) || 1));
  const where = {
    ...(q.low === '1' ? { stock: { lte: 5 } } : {}),
    ...(q.q
      ? {
          OR: [
            { sku: { contains: q.q, mode: 'insensitive' as const } },
            { product: { name: { contains: q.q, mode: 'insensitive' as const } } },
          ],
        }
      : {}),
  };
  const [variants, count, movements] = await Promise.all([
    db.variant.findMany({
      where,
      include: { product: true },
      orderBy: { sku: 'asc' },
      take: 20,
      skip: (page - 1) * 20,
    }),
    db.variant.count({ where }),
    db.stockMovement.findMany({
      include: { variant: { include: { product: true } }, actor: { select: { name: true } } },
      orderBy: { createdAt: 'desc' },
      take: 20,
    }),
  ]);
  return (
    <>
      <section className="panel">
        <h2>Tồn kho theo biến thể ({count})</h2>
        <form className="inline-actions" style={{ marginBottom: 20 }}>
          <input
            style={{ maxWidth: 260 }}
            name="q"
            placeholder="Tên sản phẩm hoặc SKU"
            aria-label="Tìm tồn kho"
            defaultValue={q.q}
          />
          <label className="check-label">
            <input name="low" type="checkbox" value="1" defaultChecked={q.low === '1'} />
            Sắp hết (≤5)
          </label>
          <Button className="small" variant="outline">
            Lọc
          </Button>
        </form>
        <div className="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Sản phẩm / SKU</th>
                <th>Size / Màu</th>
                <th>Còn lại</th>
                <th>Điều chỉnh</th>
              </tr>
            </thead>
            <tbody>
              {variants.map((v) => (
                <tr key={v.id}>
                  <td>
                    {v.product.name}
                    <small>{v.sku}</small>
                  </td>
                  <td>
                    {v.size} / {v.color}
                  </td>
                  <td>
                    <span className="badge">{v.stock}</span>
                  </td>
                  <td>
                    <InventoryForm variantId={v.id} />
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
              href={`/admin/kho?page=${p}&q=${encodeURIComponent(q.q || '')}&low=${q.low || ''}`}
            >
              {p}
            </Link>
          ))}
        </nav>
      </section>
      <section className="panel">
        <h2>20 thay đổi gần nhất</h2>
        <div className="table-scroll">
          <table>
            <thead>
              <tr>
                <th>Thời gian</th>
                <th>SKU</th>
                <th>Thay đổi</th>
                <th>Lý do / Người thực hiện</th>
              </tr>
            </thead>
            <tbody>
              {movements.map((m) => (
                <tr key={m.id}>
                  <td>{date(m.createdAt)}</td>
                  <td>{m.variant.sku}</td>
                  <td>
                    {m.delta > 0 ? '+' : ''}
                    {m.delta}
                  </td>
                  <td>
                    {m.reason}
                    <small>{m.actor?.name || 'Khởi tạo hệ thống'}</small>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </>
  );
}
