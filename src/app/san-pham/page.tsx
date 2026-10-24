import type { Prisma } from '@prisma/client';
import Link from 'next/link';
import { db } from '@/lib/db';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
export const metadata = { title: 'Bộ sưu tập' };
export default async function Catalog({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | undefined>>;
}) {
  const q = await searchParams;
  const categories = await db.category.findMany({ orderBy: { name: 'asc' } });
  const min = Math.floor(Number(q.min)),
    max = Math.floor(Number(q.max));
  const page = Math.min(Math.max(Math.floor(Number(q.page)) || 1, 1), 10000);
  const where: Prisma.ProductWhereInput = {
    active: true,
    ...(q.q ? { name: { contains: q.q.slice(0, 100), mode: 'insensitive' as const } } : {}),
    ...(q.category ? { category: { slug: q.category } } : {}),
    ...(q.sale === '1' ? { compareAt: { not: null } } : {}),
    price: {
      ...(Number.isFinite(min) && min > 0 && min <= 100_000_000 ? { gte: min } : {}),
      ...(Number.isFinite(max) && max > 0 && max <= 100_000_000 ? { lte: max } : {}),
    },
    variants: {
      some: {
        active: true,
        ...(q.size ? { size: q.size } : {}),
        ...(q.color ? { color: q.color } : {}),
        ...(q.stock === '1' ? { stock: { gt: 0 } } : {}),
      },
    },
  };
  const [count, products] = await Promise.all([
    db.product.count({ where }),
    db.product.findMany({
      where,
      include: { category: true },
      orderBy:
        q.sort === 'price-asc'
          ? { price: 'asc' }
          : q.sort === 'price-desc'
            ? { price: 'desc' }
            : { createdAt: 'desc' },
      take: 9,
      skip: (page - 1) * 9,
    }),
  ]);
  const href = (p: number) => {
    const params = new URLSearchParams(
      Object.entries(q).filter((v): v is [string, string] => typeof v[1] === 'string'),
    );
    params.set('page', String(p));
    return `/san-pham?${params}`;
  };
  return (
    <div className="container">
      <div className="breadcrumb">
        <Link href="/">Trang chủ</Link> / Bộ sưu tập
      </div>
      <div className="page-heading">
        <span className="eyebrow">THE EVERYDAY EDIT</span>
        <h1>Bộ sưu tập</h1>
        <p>Những món đồ được chọn lọc để đồng hành cùng bạn mỗi ngày.</p>
      </div>
      <div className="catalog">
        <form className="filter-form" action="/san-pham">
          <label>
            Tìm kiếm
            <input type="search" name="q" placeholder="Tên sản phẩm..." defaultValue={q.q} />
          </label>
          <label>
            Danh mục
            <select name="category" defaultValue={q.category || ''}>
              <option value="">Tất cả danh mục</option>
              {categories.map((c) => (
                <option key={c.id} value={c.slug}>
                  {c.name}
                </option>
              ))}
            </select>
          </label>
          <label>
            Size
            <select name="size" defaultValue={q.size || ''}>
              <option value="">Tất cả size</option>
              {['S', 'M', 'L', 'XL'].map((v) => (
                <option key={v}>{v}</option>
              ))}
            </select>
          </label>
          <label>
            Màu sắc
            <select name="color" defaultValue={q.color || ''}>
              <option value="">Tất cả màu</option>
              <option>Kem</option>
              <option>Đen</option>
            </select>
          </label>
          <label>
            Giá từ (đ)
            <input type="number" name="min" min="0" defaultValue={q.min} placeholder="0" />
          </label>
          <label>
            Giá đến (đ)
            <input
              type="number"
              name="max"
              min="0"
              defaultValue={q.max}
              placeholder="Không giới hạn"
            />
          </label>
          <label>
            Sắp xếp
            <select name="sort" defaultValue={q.sort || 'newest'}>
              <option value="newest">Mới nhất</option>
              <option value="price-asc">Giá tăng dần</option>
              <option value="price-desc">Giá giảm dần</option>
            </select>
          </label>
          <label className="check-label">
            <input type="checkbox" name="stock" value="1" defaultChecked={q.stock === '1'} /> Chỉ
            còn hàng
          </label>
          <label className="check-label">
            <input type="checkbox" name="sale" value="1" defaultChecked={q.sale === '1'} /> Đang
            giảm giá
          </label>
          <Button type="submit">Áp dụng bộ lọc</Button>
          <Link href="/san-pham" className="muted" style={{ fontSize: 12 }}>
            Xóa bộ lọc
          </Link>
        </form>
        <div>
          <div className="catalog-top">
            <span>
              {count} sản phẩm{q.q ? ` cho “${q.q}”` : ''}
            </span>
            <span>Chọn ít hơn. Mặc đẹp hơn.</span>
          </div>
          {products.length ? (
            <div className="product-grid">
              {products.map((p) => (
                <ProductCard key={p.id} product={p} />
              ))}
            </div>
          ) : (
            <div className="empty">
              <h2>Chưa tìm thấy sản phẩm</h2>
              <p>Thử thay đổi từ khóa hoặc bỏ bớt bộ lọc.</p>
              <Button asChild variant="outline">
                <Link href="/san-pham">Xem tất cả sản phẩm</Link>
              </Button>
            </div>
          )}
          <nav className="pagination" aria-label="Phân trang">
            {Array.from({ length: Math.ceil(count / 9) }, (_, i) => i + 1).map((p) => (
              <Link
                key={p}
                href={href(p)}
                className={p === page ? 'selected' : ''}
                aria-current={p === page ? 'page' : undefined}
              >
                {p}
              </Link>
            ))}
          </nav>
        </div>
      </div>
    </div>
  );
}
