import Link from 'next/link';
import { money } from '@/lib/utils';
type CardProduct = {
  slug: string;
  name: string;
  price: number;
  compareAt: number | null;
  images: string[];
  category: { name: string };
};
export function ProductCard({ product: p }: { product: CardProduct }) {
  return (
    <article className="product-card">
      <Link href={`/san-pham/${p.slug}`}>
        <div className="product-image">
          <img src={p.images[0]} alt={p.name} loading="lazy" />
          {p.compareAt && p.compareAt > p.price ? (
            <span className="tag sale">−{Math.round((1 - p.price / p.compareAt) * 100)}%</span>
          ) : (
            <span className="tag">ESSENTIALS</span>
          )}
        </div>
        <div className="product-info">
          <p>{p.category.name}</p>
          <h3>{p.name}</h3>
          <div className="price">
            {money(p.price)}
            {p.compareAt && <span className="old-price">{money(p.compareAt)}</span>}
          </div>
          <div className="swatches" aria-label="Màu kem và đen">
            <i />
            <i />
          </div>
        </div>
      </Link>
    </article>
  );
}
