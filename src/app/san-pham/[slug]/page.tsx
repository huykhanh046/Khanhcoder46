import { notFound } from 'next/navigation';
import Link from 'next/link';
import { db } from '@/lib/db';
import { ProductDetail } from '@/components/product-detail';
import { ProductCard } from '@/components/product-card';
export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const p = await db.product.findUnique({ where: { slug } });
  return { title: p?.name || 'Sản phẩm' };
}
export default async function Detail({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params;
  const p = await db.product.findUnique({
    where: { slug },
    include: { category: true, variants: { where: { active: true }, orderBy: { sku: 'asc' } } },
  });
  if (!p || !p.active) notFound();
  const related = await db.product.findMany({
    where: { active: true, categoryId: p.categoryId, id: { not: p.id } },
    include: { category: true },
    take: 4,
  });
  return (
    <div className="container">
      <div className="breadcrumb">
        <Link href="/">Trang chủ</Link> / <Link href="/san-pham">Bộ sưu tập</Link> / {p.name}
      </div>
      <ProductDetail product={p} />
      <section className="section" style={{ borderTop: '1px solid var(--line)' }}>
        <div className="section-title">
          <div>
            <span className="eyebrow">COMPLETE YOUR WARDROBE</span>
            <h2>Có thể bạn cũng thích</h2>
          </div>
        </div>
        <div className="product-grid">
          {related.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      </section>
    </div>
  );
}
