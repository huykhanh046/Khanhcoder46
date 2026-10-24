'use client';
import { useState } from 'react';
import Link from 'next/link';
import { ShoppingBag, Truck, ShieldCheck } from 'lucide-react';
import { Button } from './ui/button';
import { useCart } from './cart-provider';
import { money } from '@/lib/utils';
type Product = {
  name: string;
  slug: string;
  price: number;
  compareAt: number | null;
  images: string[];
  description: string;
  material: string;
  category: { name: string };
  variants: { id: string; sku: string; size: string; color: string; stock: number }[];
};
export function ProductDetail({ product: p }: { product: Product }) {
  const [size, setSize] = useState(p.variants[0]?.size || ''),
    [color, setColor] = useState(p.variants[0]?.color || ''),
    [q, setQ] = useState(1),
    [image, setImage] = useState(p.images[0]);
  const { add } = useCart();
  const v = p.variants.find((v) => v.size === size && v.color === color);
  return (
    <div className="detail-grid">
      <div>
        <img className="gallery-main" src={image} alt={p.name} />
        <div className="thumbnails">
          {p.images.map((src, i) => (
            <button
              key={src}
              onClick={() => setImage(src)}
              className={image === src ? 'selected' : ''}
              aria-label={`Xem ảnh ${i + 1}`}
            >
              <img src={src} alt={`${p.name} góc ${i + 1}`} />
            </button>
          ))}
        </div>
      </div>
      <div className="product-detail">
        <span className="eyebrow">{p.category.name} / KH ESSENTIALS</span>
        <h1>{p.name}</h1>
        <div className="detail-price">
          {money(p.price)}
          {p.compareAt && <span className="old-price">{money(p.compareAt)}</span>}
        </div>
        <p className="detail-description">{p.description}</p>
        <div className="divider" />
        <p style={{ fontSize: 13 }}>
          Màu sắc: <strong>{color}</strong>
        </p>
        <div className="option-row">
          {[...new Set(p.variants.map((v) => v.color))].map((c) => (
            <button
              key={c}
              onClick={() => {
                setColor(c);
                setQ(1);
              }}
              className={`option ${c === color ? 'selected' : ''}`}
              aria-pressed={c === color}
            >
              {c}
            </button>
          ))}
        </div>
        <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13 }}>
          <span>
            Kích thước: <strong>{size}</strong>
          </span>
          <Link className="text-link" href="/chinh-sach#size">
            Hướng dẫn chọn size
          </Link>
        </div>
        <div className="option-row">
          {[...new Set(p.variants.map((v) => v.size))].map((s) => (
            <button
              key={s}
              onClick={() => {
                setSize(s);
                setQ(1);
              }}
              className={`option ${s === size ? 'selected' : ''}`}
              aria-pressed={s === size}
            >
              {s}
            </button>
          ))}
        </div>
        <p className="stock">
          {v && v.stock > 0
            ? `● Còn ${v.stock} sản phẩm · SKU ${v.sku}`
            : 'Hết hàng ở lựa chọn này'}
        </p>
        <div className="buy-row">
          <div className="quantity">
            <button aria-label="Giảm số lượng" onClick={() => setQ(Math.max(1, q - 1))}>
              −
            </button>
            <span>{q}</span>
            <button
              aria-label="Tăng số lượng"
              disabled={!v || q >= Math.min(v.stock, 20)}
              onClick={() => setQ(q + 1)}
            >
              +
            </button>
          </div>
          <Button
            disabled={!v || v.stock < q}
            onClick={() =>
              v &&
              add({
                variantId: v.id,
                name: p.name,
                slug: p.slug,
                price: p.price,
                image: p.images[0],
                size,
                color,
                quantity: q,
                stock: v.stock,
              })
            }
          >
            <ShoppingBag size={16} /> Thêm vào giỏ hàng
          </Button>
        </div>
        <div className="small-perks">
          <p>
            <Truck size={17} /> Miễn phí giao hàng cho đơn từ 699.000đ
          </p>
          <p>
            <ShieldCheck size={17} /> Thanh toán COD khi nhận hàng
          </p>
        </div>
        <div className="divider" />
        <h3 style={{ fontSize: 14 }}>Chất liệu & chăm sóc</h3>
        <p className="muted" style={{ fontSize: 13 }}>
          {p.material}. Giặt nhẹ dưới 30°C, không dùng chất tẩy.
        </p>
        <p className="muted" style={{ fontSize: 11 }}>
          Ảnh minh họa thiết kế. Màu hiển thị có thể khác sản phẩm thực tế.
        </p>
      </div>
    </div>
  );
}
