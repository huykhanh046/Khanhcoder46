'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { api } from '@/lib/client';
import { Button } from './ui/button';
import type { Product, Voucher, Variant, Category } from '@prisma/client';
function useSubmit() {
  const [error, setError] = useState(''),
    [busy, setBusy] = useState(false);
  const router = useRouter();
  async function send(path: string, data: unknown, method = 'POST', redirect?: string) {
    setError('');
    setBusy(true);
    try {
      const result = await api(path, data, method);
      if (redirect) router.push(redirect);
      router.refresh();
      return result;
    } catch (e) {
      setError((e as Error).message);
      return null;
    } finally {
      setBusy(false);
    }
  }
  return { error, busy, send };
}
function Feedback({ error }: { error: string }) {
  return error ? (
    <div className="alert" role="alert">
      {error}
    </div>
  ) : null;
}
export function ProductForm({
  product: p,
  categories,
}: {
  product?: Product;
  categories: Category[];
}) {
  const { error, busy, send } = useSubmit();
  return (
    <form
      className="form-stack"
      onSubmit={async (e) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        await send(
          p ? `admin/products/${p.id}` : 'admin/products',
          {
            name: f.get('name'),
            slug: f.get('slug'),
            description: f.get('description'),
            material: f.get('material'),
            categoryId: f.get('categoryId'),
            price: Number(f.get('price')),
            compareAt: f.get('compareAt') ? Number(f.get('compareAt')) : null,
            images: String(f.get('images'))
              .split('\n')
              .map((s) => s.trim())
              .filter(Boolean),
            active: f.get('active') === 'on',
            featured: f.get('featured') === 'on',
          },
          p ? 'PATCH' : 'POST',
          '/admin/san-pham',
        );
      }}
    >
      <div className="form-grid">
        <label>
          Tên sản phẩm
          <input name="name" required minLength={3} maxLength={150} defaultValue={p?.name} />
        </label>
        <label>
          Đường dẫn (slug)
          <input
            name="slug"
            required
            pattern="[a-z0-9]+(-[a-z0-9]+)*"
            defaultValue={p?.slug}
            placeholder="ao-thun-essential"
          />
        </label>
        <label>
          Danh mục
          <select name="categoryId" required defaultValue={p?.categoryId || categories[0]?.id}>
            {categories.map((c) => (
              <option value={c.id} key={c.id}>
                {c.name}
              </option>
            ))}
          </select>
        </label>
        <label>
          Chất liệu
          <input
            name="material"
            required
            minLength={2}
            maxLength={200}
            defaultValue={p?.material}
          />
        </label>
        <label>
          Giá bán (đ)
          <input
            name="price"
            type="number"
            required
            min={1000}
            max={100000000}
            defaultValue={p?.price}
          />
        </label>
        <label>
          Giá gốc (tùy chọn)
          <input
            name="compareAt"
            type="number"
            min={1000}
            max={100000000}
            defaultValue={p?.compareAt || ''}
          />
        </label>
        <label className="span-2">
          Mô tả
          <textarea
            name="description"
            required
            minLength={10}
            maxLength={5000}
            defaultValue={p?.description}
          />
        </label>
        <label className="span-2">
          Ảnh (mỗi đường dẫn một dòng)
          <textarea
            name="images"
            required
            defaultValue={p?.images.join('\n') || '/images/product-1.svg'}
          />
          <small className="muted">
            Đặt tệp ảnh vào public/images rồi nhập /images/ten-tep.webp. Không nhận đường dẫn bên
            ngoài.
          </small>
        </label>
        <label className="check-label">
          <input type="checkbox" name="active" defaultChecked={p?.active ?? true} />
          Đang bán
        </label>
        <label className="check-label">
          <input type="checkbox" name="featured" defaultChecked={p?.featured ?? false} />
          Nổi bật trang chủ
        </label>
      </div>
      <Feedback error={error} />
      <Button disabled={busy} style={{ justifySelf: 'start' }}>
        {busy ? 'Đang lưu...' : 'Lưu sản phẩm'}
      </Button>
    </form>
  );
}
export function CategoryForm({ category: c }: { category?: Category }) {
  const { error, busy, send } = useSubmit();
  return (
    <form
      className="form-stack"
      onSubmit={async (e) => {
        e.preventDefault();
        const form = e.currentTarget;
        const result = await send(
          c ? `admin/categories/${c.id}` : 'admin/categories',
          Object.fromEntries(new FormData(form)),
          c ? 'PATCH' : 'POST',
        );
        if (result && !c) form.reset();
      }}
    >
      <div className="two-cols">
        <label>
          Tên danh mục
          <input name="name" required minLength={2} maxLength={60} defaultValue={c?.name} />
        </label>
        <label>
          Slug
          <input name="slug" required pattern="[a-z0-9-]+" maxLength={80} defaultValue={c?.slug} />
        </label>
      </div>
      <Feedback error={error} />
      <Button className="small" disabled={busy} style={{ justifySelf: 'start' }}>
        {c ? 'Lưu danh mục' : 'Thêm danh mục'}
      </Button>
    </form>
  );
}
export function VariantForm({ productId, variant: v }: { productId: string; variant?: Variant }) {
  const { error, busy, send } = useSubmit();
  return (
    <form
      className="form-stack"
      onSubmit={async (e) => {
        e.preventDefault();
        const form = e.currentTarget,
          f = new FormData(form);
        const result = await send(
          v ? `admin/variants/${v.id}` : 'admin/variants',
          {
            productId,
            sku: f.get('sku'),
            size: f.get('size'),
            color: f.get('color'),
            active: f.get('active') === 'on',
          },
          v ? 'PATCH' : 'POST',
        );
        if (result && !v) form.reset();
      }}
    >
      <div className="form-grid">
        <label>
          SKU
          <input name="sku" required minLength={2} maxLength={60} defaultValue={v?.sku} />
        </label>
        <label>
          Size
          <input name="size" required maxLength={20} defaultValue={v?.size} />
        </label>
        <label>
          Màu
          <input name="color" required maxLength={40} defaultValue={v?.color} />
        </label>
        <label className="check-label">
          <input name="active" type="checkbox" defaultChecked={v?.active ?? true} />
          Đang bán
        </label>
      </div>
      <Feedback error={error} />
      <Button className="small" disabled={busy} style={{ justifySelf: 'start' }}>
        {v ? 'Lưu biến thể' : 'Thêm biến thể (tồn ban đầu 0)'}
      </Button>
    </form>
  );
}
export function InventoryForm({ variantId }: { variantId: string }) {
  const { error, busy, send } = useSubmit();
  const [show, setShow] = useState(false);
  if (!show)
    return (
      <Button className="small" variant="outline" onClick={() => setShow(true)}>
        Điều chỉnh
      </Button>
    );
  return (
    <form
      className="form-stack"
      style={{ minWidth: 200 }}
      onSubmit={async (e) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        const r = await send(`admin/inventory/${variantId}`, {
          delta: Number(f.get('delta')),
          reason: f.get('reason'),
        });
        if (r) setShow(false);
      }}
    >
      <label>
        Số lượng thay đổi (+/−)
        <input
          aria-label="Số lượng điều chỉnh"
          name="delta"
          type="number"
          required
          min={-10000}
          max={10000}
        />
      </label>
      <label>
        Lý do
        <input name="reason" required minLength={5} maxLength={200} />
      </label>
      <Feedback error={error} />
      <div className="inline-actions">
        <Button className="small" disabled={busy}>
          Lưu
        </Button>
        <button type="button" className="btn btn-ghost small" onClick={() => setShow(false)}>
          Đóng
        </button>
      </div>
    </form>
  );
}
const localDate = (d: Date | string) => {
  const value = new Date(d);
  return new Date(value.getTime() - value.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
};
export function VoucherForm({ voucher: v }: { voucher?: Voucher }) {
  const { error, busy, send } = useSubmit();
  return (
    <form
      className="form-stack"
      onSubmit={async (e) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        await send(
          v ? `admin/vouchers/${v.id}` : 'admin/vouchers',
          {
            code: f.get('code'),
            percent: Number(f.get('percent')),
            minOrder: Number(f.get('minOrder')),
            maxDiscount: Number(f.get('maxDiscount')),
            usageLimit: Number(f.get('usageLimit')),
            startsAt: new Date(String(f.get('startsAt'))).toISOString(),
            expiresAt: new Date(String(f.get('expiresAt'))).toISOString(),
            active: f.get('active') === 'on',
          },
          v ? 'PATCH' : 'POST',
        );
      }}
    >
      <div className="form-grid">
        <label>
          Mã voucher
          <input name="code" required pattern="[A-Za-z0-9]{3,30}" defaultValue={v?.code} />
        </label>
        <label>
          Giảm (%)
          <input
            name="percent"
            type="number"
            required
            min={1}
            max={100}
            defaultValue={v?.percent || 10}
          />
        </label>
        <label>
          Đơn tối thiểu (đ)
          <input name="minOrder" type="number" required min={0} defaultValue={v?.minOrder || 0} />
        </label>
        <label>
          Giảm tối đa (đ)
          <input
            name="maxDiscount"
            type="number"
            required
            min={1000}
            defaultValue={v?.maxDiscount || 100000}
          />
        </label>
        <label>
          Giới hạn sử dụng
          <input
            name="usageLimit"
            type="number"
            required
            min={v?.used || 1}
            defaultValue={v?.usageLimit || 100}
          />
        </label>
        <label className="check-label">
          <input name="active" type="checkbox" defaultChecked={v?.active ?? true} />
          Có hiệu lực
        </label>
        <label>
          Bắt đầu (giờ địa phương)
          <input
            name="startsAt"
            type="datetime-local"
            required
            defaultValue={v ? localDate(v.startsAt) : undefined}
          />
        </label>
        <label>
          Kết thúc (giờ địa phương)
          <input
            name="expiresAt"
            type="datetime-local"
            required
            defaultValue={v ? localDate(v.expiresAt) : undefined}
          />
        </label>
      </div>
      <Feedback error={error} />
      <Button disabled={busy} style={{ justifySelf: 'start' }}>
        {busy ? 'Đang lưu...' : v ? 'Lưu voucher' : 'Tạo voucher'}
      </Button>
    </form>
  );
}
