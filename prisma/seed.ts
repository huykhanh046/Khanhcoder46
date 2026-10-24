import { PrismaPg } from '@prisma/adapter-pg';
import 'dotenv/config';
import { PrismaClient } from '@prisma/client';
import { hash } from 'bcryptjs';
const db = new PrismaClient({
  adapter: new PrismaPg({ connectionString: process.env.DATABASE_URL }),
});
async function main() {
  if (process.env.NODE_ENV === 'production' && process.env.ALLOW_DEMO_SEED !== 'true')
    throw new Error('Demo seed disabled in production');
  const passwordHash = await hash('KhFashion@2026', 12);
  for (const [email, name, role] of [
    ['admin@khfashion.vn', 'Quản trị KH Fashion', 'ADMIN'],
    ['khach@khfashion.vn', 'Nguyễn Minh Anh', 'CUSTOMER'],
  ] as const) {
    await db.user.upsert({
      where: { email },
      update: {},
      create: { email, name, role, passwordHash },
    });
  }
  const groups = [
    ['ao', 'Áo'],
    ['quan', 'Quần'],
    ['vay', 'Váy & Đầm'],
    ['ao-khoac', 'Áo khoác'],
  ];
  const names = [
    [
      'Áo thun Essential',
      'Áo sơ mi Linen',
      'Áo polo Everyday',
      'Áo kiểu Soft Touch',
      'Áo thun Weekend',
    ],
    [
      'Quần ống rộng Studio',
      'Quần tây Modern',
      'Quần short City',
      'Quần jeans Straight',
      'Quần linen Relaxed',
    ],
    ['Đầm midi Duyên', 'Váy xếp ly Muse', 'Đầm linen Nắng', 'Chân váy A-line', 'Đầm suông Sunday'],
    ['Blazer Urban', 'Áo khoác Denim', 'Cardigan Cloud', 'Áo khoác Trench', 'Áo khoác Bomber'],
  ];
  for (let g = 0; g < groups.length; g++) {
    const [slug, name] = groups[g];
    const cat = await db.category.upsert({ where: { slug }, update: {}, create: { slug, name } });
    for (let i = 0; i < 5; i++) {
      const index = g * 5 + i,
        productSlug = `${slug}-${i + 1}`;
      const p = await db.product.upsert({
        where: { slug: productSlug },
        update: {},
        create: {
          slug: productSlug,
          name: names[g][i],
          description:
            'Thiết kế thanh lịch, dễ phối cho mọi khoảnh khắc thường ngày. Đường may tỉ mỉ, phom dáng thoải mái giúp bạn tự tin thể hiện phong cách riêng. Giặt nhẹ dưới 30°C, không dùng chất tẩy và phơi nơi thoáng mát.',
          material:
            g === 1
              ? 'Cotton pha linen'
              : g === 3
                ? 'Cotton dệt cao cấp'
                : 'Cotton tự nhiên, mềm thoáng',
          price: [249000, 399000, 329000, 459000, 289000][i] + g * 100000,
          compareAt: i % 2 === 0 ? [349000, 499000, 429000, 559000, 389000][i] + g * 100000 : null,
          images: [`/images/product-${index + 1}.svg`, `/images/product-${index + 1}-detail.svg`],
          featured: i < 2,
          categoryId: cat.id,
        },
      });
      for (const size of ['S', 'M', 'L', 'XL'])
        for (const color of ['Kem', 'Đen']) {
          const sku = `KH-${index + 1}-${size}-${color === 'Kem' ? 'CREAM' : 'BLACK'}`;
          const existing = await db.variant.findUnique({ where: { sku } });
          if (!existing)
            await db.variant.create({
              data: {
                productId: p.id,
                sku,
                size,
                color,
                stock: size === 'XL' ? 3 : 12,
                movements: {
                  create: { delta: size === 'XL' ? 3 : 12, reason: 'Tồn kho khởi tạo demo' },
                },
              },
            });
        }
    }
  }
  await db.voucher.upsert({
    where: { code: 'WELCOME10' },
    update: {},
    create: {
      code: 'WELCOME10',
      percent: 10,
      minOrder: 300000,
      maxDiscount: 100000,
      usageLimit: 100,
      startsAt: new Date('2026-01-01'),
      expiresAt: new Date('2030-12-31'),
    },
  });
  const customer = await db.user.findUniqueOrThrow({ where: { email: 'khach@khfashion.vn' } });
  if (!(await db.address.count({ where: { userId: customer.id } })))
    await db.address.create({
      data: {
        userId: customer.id,
        name: customer.name,
        phone: '0901234567',
        detail: '123 Nguyễn Huệ, phường Sài Gòn, TP. Hồ Chí Minh',
      },
    });
  console.log(
    'Seed complete: 20 products, 160 variants, demo accounts and WELCOME10. Existing data preserved.',
  );
}
main().finally(() => db.$disconnect());
