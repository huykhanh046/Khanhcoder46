import Link from 'next/link';
import { ArrowRight, ArrowUpRight, Truck, ShieldCheck, Leaf } from 'lucide-react';
import { db } from '@/lib/db';
import { ProductCard } from '@/components/product-card';
import { Button } from '@/components/ui/button';
export default async function Home() {
  const products = await db.product.findMany({
    where: { active: true, featured: true },
    include: { category: true },
    take: 8,
    orderBy: { createdAt: 'asc' },
  });
  return (
    <>
      <section className="hero">
        <div className="container hero-grid">
          <div className="hero-copy">
            <span className="eyebrow">THE EVERYDAY COLLECTION / 2026</span>
            <h1>
              Đơn giản hơn.
              <br />
              <em className="serif">Là chính bạn.</em>
            </h1>
            <p>
              Những thiết kế tinh giản, chất liệu dễ chịu.
              <br />
              Để mỗi ngày đều là một ngày bạn thấy tự tin.
            </p>
            <div className="hero-buttons">
              <Button asChild>
                <Link href="/san-pham">
                  Khám phá bộ sưu tập <ArrowUpRight size={17} />
                </Link>
              </Button>
              <Link href="/san-pham?sort=newest" className="text-link">
                Có gì mới? <ArrowRight size={14} />
              </Link>
            </div>
            <div className="hero-note">Chọn chất lượng. Chọn phong cách bền lâu.</div>
          </div>
          <div className="hero-art">
            <div className="hero-circle" />
            <img src="/images/product-16.svg" alt="Blazer Urban trong bộ sưu tập Everyday" />
            <span className="hero-label">EFFORTLESS STYLE, EVERY DAY.</span>
            <Link href="/san-pham/ao-1" className="hero-float">
              <img src="/images/product-1.svg" alt="Áo thun Essential" />
              <div>
                <strong>Everyday essentials</strong>
                <p>
                  Đẹp từ những điều cơ bản <ArrowUpRight size={13} style={{ display: 'inline' }} />
                </p>
              </div>
            </Link>
          </div>
        </div>
      </section>
      <section className="perks">
        <div className="container perks-grid">
          <div className="perk">
            <Truck size={25} strokeWidth={1.3} />
            <div>
              <strong>Giao hàng toàn quốc</strong>
              <span>Miễn phí cho đơn từ 699.000đ</span>
            </div>
          </div>
          <div className="perk">
            <ShieldCheck size={25} strokeWidth={1.3} />
            <div>
              <strong>Mua sắm an tâm</strong>
              <span>Kiểm tra thông tin trước khi đặt hàng</span>
            </div>
          </div>
          <div className="perk">
            <Leaf size={25} strokeWidth={1.3} />
            <div>
              <strong>Chất liệu chọn lọc</strong>
              <span>Thoải mái trong từng chuyển động</span>
            </div>
          </div>
        </div>
      </section>
      <section className="section container">
        <div className="section-title">
          <div>
            <span className="eyebrow">TÌM PHONG CÁCH CỦA BẠN</span>
            <h2>Mặc đẹp, theo cách riêng.</h2>
          </div>
          <Link href="/san-pham" className="text-link">
            Tất cả danh mục <ArrowUpRight size={14} />
          </Link>
        </div>
        <div className="category-grid">
          {[
            ['Áo', 'ao', 2],
            ['Quần', 'quan', 6],
            ['Váy & Đầm', 'vay', 11],
            ['Áo khoác', 'ao-khoac', 18],
          ].map(([name, slug, img]) => (
            <Link key={slug} className="category-card" href={`/san-pham?category=${slug}`}>
              <img src={`/images/product-${img}.svg`} alt={String(name)} loading="lazy" />
              <div>
                <h3>{name}</h3>
                <ArrowUpRight size={17} />
              </div>
            </Link>
          ))}
        </div>
      </section>
      <section className="section container" style={{ paddingTop: 10 }}>
        <div className="section-title">
          <div>
            <span className="eyebrow">CURATED FOR YOU</span>
            <h2>Những lựa chọn nổi bật</h2>
          </div>
          <Link href="/san-pham" className="text-link">
            Xem tất cả <ArrowUpRight size={14} />
          </Link>
        </div>
        <div className="product-grid">
          {products.map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      </section>
      <section className="container" style={{ paddingBottom: 64 }}>
        <div className="editorial">
          <div className="editorial-art">
            <img src="/images/product-3.svg" alt="Áo polo Everyday" loading="lazy" />
            <img src="/images/product-7.svg" alt="Quần tây Modern" loading="lazy" />
          </div>
          <div className="editorial-copy">
            <span className="eyebrow">LESS, BUT BETTER.</span>
            <h2>
              Tủ đồ nhỏ.
              <br />
              <span className="serif">Khả năng vô hạn.</span>
            </h2>
            <p>
              Từ một buổi sáng vội vã đến cuộc hẹn cuối tuần. Những món đồ cơ bản giúp bạn phối đồ
              thật tự nhiên.
            </p>
            <Button asChild variant="outline">
              <Link href="/san-pham">
                Tìm món đồ của bạn <ArrowUpRight size={16} />
              </Link>
            </Button>
          </div>
        </div>
      </section>
    </>
  );
}
