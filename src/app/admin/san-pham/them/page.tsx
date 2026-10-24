import { pageUser } from '@/lib/auth';
import { db } from '@/lib/db';
import { ProductForm } from '@/components/admin-forms';
export default async function Page() {
  await pageUser(true);
  const categories = await db.category.findMany();
  return (
    <section className="panel">
      <h2>Thêm sản phẩm</h2>
      <ProductForm categories={categories} />
    </section>
  );
}
