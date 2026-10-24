import { pageUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { CategoryForm } from '@/components/admin-forms';
import { ActionButton } from '@/components/account';
export default async function Page() {
  await pageUser(true);
  const categories = await db.category.findMany({
    include: { _count: { select: { products: true } } },
    orderBy: { name: 'asc' },
  });
  return (
    <>
      <section className="panel">
        <h2>Thêm danh mục</h2>
        <CategoryForm />
      </section>
      <section className="panel">
        <h2>Danh mục sản phẩm</h2>
        {categories.map((c) => (
          <details key={c.id} style={{ padding: '15px 0', borderTop: '1px solid var(--line)' }}>
            <summary style={{ cursor: 'pointer', marginBottom: 12 }}>
              {c.name} · {c._count.products} sản phẩm
            </summary>
            <CategoryForm category={c} />
            <div style={{ marginTop: 10 }}>
              <ActionButton
                path={`admin/categories/${c.id}`}
                method="DELETE"
                label="Xóa danh mục trống"
                confirmMessage="Xóa danh mục? Danh mục chứa sản phẩm sẽ không thể xóa."
              />
            </div>
          </details>
        ))}
      </section>
    </>
  );
}
