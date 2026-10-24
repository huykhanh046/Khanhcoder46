import { pageUser } from '@/lib/auth';
import { notFound } from 'next/navigation';
import { db } from '@/lib/db';
import { ProductForm, VariantForm } from '@/components/admin-forms';
export default async function Page({ params }: { params: Promise<{ id: string }> }) {
  await pageUser(true);
  const { id } = await params;
  const p = await db.product.findUnique({
    where: { id },
    include: { variants: { orderBy: { sku: 'asc' } } },
  });
  if (!p) notFound();
  const categories = await db.category.findMany();
  return (
    <>
      <section className="panel">
        <h2>Chỉnh sửa sản phẩm</h2>
        <ProductForm product={p} categories={categories} />
      </section>
      <section className="panel">
        <h2>Thêm biến thể</h2>
        <VariantForm productId={p.id} />
      </section>
      <section className="panel">
        <h2>Các biến thể ({p.variants.length})</h2>
        <p className="muted">Điều chỉnh số lượng ở mục Tồn kho để giữ lịch sử thay đổi.</p>
        {p.variants.map((v) => (
          <details key={v.id} style={{ borderTop: '1px solid var(--line)', padding: '15px 0' }}>
            <summary style={{ cursor: 'pointer', marginBottom: 12 }}>
              {v.sku} — {v.size} / {v.color} — Tồn {v.stock} {v.active ? '' : '(đã ẩn)'}
            </summary>
            <VariantForm productId={p.id} variant={v} />
          </details>
        ))}
      </section>
    </>
  );
}
