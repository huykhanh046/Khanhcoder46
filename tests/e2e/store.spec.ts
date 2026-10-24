import { test, expect } from '@playwright/test';
import { randomUUID, createHash } from 'node:crypto';
import { hash } from 'bcryptjs';
import { PrismaClient } from '@prisma/client';
import { PrismaPg } from '@prisma/adapter-pg';
const db = new PrismaClient({
  adapter: new PrismaPg({ connectionString: process.env.DATABASE_URL }),
});
const createdUsers: string[] = [];
const createdOrders: string[] = [];
const adminEmail = `e2e-admin-${randomUUID()}@test.example`;
const testIp = `198.51.100.${(parseInt(randomUUID().slice(0, 4), 16) % 250) + 1}`;
test.beforeAll(async () => {
  const admin = await db.user.create({
    data: {
      email: adminEmail,
      name: 'Admin E2E',
      role: 'ADMIN',
      passwordHash: await hash('KhFashion@2026', 12),
    },
  });
  createdUsers.push(admin.id);
});
async function post(
  request: import('@playwright/test').APIRequestContext,
  path: string,
  data: unknown = {},
  method = 'POST',
) {
  return request.fetch(`/api/${path}`, {
    method,
    data,
    headers: { Origin: process.env.APP_URL || 'http://localhost:3000', 'x-forwarded-for': testIp },
  });
}
test.afterAll(async () => {
  // Remove only the fixtures created by this worker; return their stock before cleanup.
  for (const id of createdOrders) {
    const o = await db.order.findUnique({ where: { id }, include: { items: true } });
    if (!o) continue;
    for (const i of o.items)
      await db.variant.update({
        where: { id: i.variantId },
        data: { stock: { increment: i.quantity } },
      });
    if (o.voucherId)
      await db.voucher.update({ where: { id: o.voucherId }, data: { used: { decrement: 1 } } });
    await db.orderEvent.deleteMany({ where: { orderId: id } });
    await db.orderItem.deleteMany({ where: { orderId: id } });
    await db.order.delete({ where: { id } });
  }
  for (const id of createdUsers) {
    const u = await db.user.findUnique({ where: { id }, select: { email: true } });
    if (u)
      await db.rateLimit.deleteMany({
        where: { key: createHash('sha256').update(`email:${u.email}`).digest('hex') },
      });
    await db.stockMovement.deleteMany({ where: { actorId: id } });
    await db.address.deleteMany({ where: { userId: id } });
    await db.user.deleteMany({ where: { id } });
  }
  await db.rateLimit.deleteMany({
    where: { key: createHash('sha256').update(`auth:${testIp}`).digest('hex') },
  });
  await db.$disconnect();
});
test('browse, filter, gallery, cart, COD checkout and admin delivery', async ({
  page,
  request,
}, info) => {
  const suffix = randomUUID().slice(0, 8),
    email = `e2e-${info.project.name}-${suffix}@test.example`;
  const response = await post(request, 'auth/register', {
    email,
    name: 'Khách E2E',
    password: 'TestPass@2026',
  });
  expect(response.ok()).toBeTruthy();
  const user = await db.user.findUniqueOrThrow({ where: { email } });
  createdUsers.push(user.id);
  await page.context().addCookies(await request.storageState().then((s) => s.cookies));
  await page.goto('/');
  await expect(page.getByRole('heading', { name: /Đơn giản hơn/ })).toBeVisible();
  await expect(page.locator('body')).toHaveJSProperty(
    'scrollWidth',
    await page.locator('body').evaluate((e) => e.clientWidth),
  );
  await page.goto('/san-pham?q=Essential');
  await expect(page.getByRole('heading', { name: 'Áo thun Essential', exact: true })).toBeVisible();
  await page.getByRole('heading', { name: 'Áo thun Essential', exact: true }).click();
  await page.getByRole('button', { name: 'Xem ảnh 2' }).click();
  await expect(page.locator('.gallery-main')).toHaveAttribute('src', /detail/);
  await page.getByRole('button', { name: 'M', exact: true }).click();
  await page.getByRole('button', { name: 'Kem', exact: true }).click();
  await page.getByRole('button', { name: 'Thêm vào giỏ hàng' }).click();
  await expect(page.getByRole('status')).toContainText('Đã thêm');
  await page.goto('/gio-hang');
  await expect(page.getByRole('heading', { name: 'Áo thun Essential', exact: true })).toBeVisible();
  await page.reload();
  await expect(page.locator('.cart-item')).toHaveCount(1);
  await page.getByRole('button', { name: 'Tăng Áo thun Essential' }).click();
  await page.getByRole('link', { name: /Tiến hành thanh toán/ }).click();
  await page.getByLabel('Họ tên người nhận').fill('Khách E2E');
  await page.getByLabel('Số điện thoại', { exact: true }).fill('0901234567');
  await page
    .getByLabel('Địa chỉ đầy đủ')
    .fill('123 Đường Kiểm Thử, phường Sài Gòn, TP. Hồ Chí Minh');
  await page.getByRole('textbox', { name: 'Mã ưu đãi', exact: true }).fill('WELCOME10');
  await page.getByRole('button', { name: 'Áp dụng', exact: true }).click();
  await expect(page.getByText('Đã áp dụng WELCOME10')).toBeVisible();
  await expect(page.locator('.summary-row.total')).toContainText('478.200');
  await page.getByRole('button', { name: 'Xác nhận đặt hàng' }).click();
  await expect(page.getByRole('status')).toContainText('Đặt hàng thành công');
  const id = new URL(page.url()).pathname.split('/').pop()!;
  createdOrders.push(id);
  const order = await db.order.findUniqueOrThrow({ where: { id } });
  expect(order.total).toBe(478200);
  await expect(page.getByRole('heading', { name: 'Thông tin giao hàng' })).toBeVisible();
  const admin = await post(request, 'auth/login', {
    email: adminEmail,
    password: 'KhFashion@2026',
  });
  expect(admin.ok()).toBeTruthy();
  await page.context().clearCookies();
  await page.context().addCookies((await request.storageState()).cookies);
  await page.goto(`/admin/don-hang/${id}`);
  await page.getByRole('button', { name: 'Xác nhận đơn', exact: true }).click();
  await expect(page.getByRole('button', { name: 'Bắt đầu giao' })).toBeVisible();
  await page.getByRole('button', { name: 'Bắt đầu giao' }).click();
  await page.getByRole('button', { name: 'Đã giao thành công' }).click();
  await expect(page.locator('.order-head .badge')).toHaveText('Đã giao hàng');
  expect((await db.order.findUniqueOrThrow({ where: { id } })).paymentStatus).toBe('PAID');
});
test('enforces authentication, ownership, role and mutation origin', async ({ request }) => {
  let r = await post(request, 'admin/categories', { name: 'Forbidden', slug: 'forbidden' });
  expect(r.status()).toBe(401);
  const email = `e2e-security-${randomUUID()}@test.example`;
  r = await post(request, 'auth/register', {
    email,
    name: 'Khách bảo mật',
    password: 'TestPass@2026',
  });
  expect(r.ok()).toBeTruthy();
  const u = await db.user.findUniqueOrThrow({ where: { email } });
  createdUsers.push(u.id);
  r = await post(request, 'admin/categories', { name: 'Forbidden', slug: 'forbidden' });
  expect(r.status()).toBe(403);
  r = await request.post('/api/account/profile', {
    data: { name: 'Cross origin' },
    headers: { Origin: 'https://attacker.example' },
  });
  expect(r.status()).toBe(403);
  r = await post(request, 'orders/not-my-order/cancel');
  expect(r.status()).toBe(404);
  r = await post(request, 'account/addresses', {
    name: 'Test',
    phone: 'invalid',
    detail: '123 Test Street',
  });
  expect(r.status()).toBe(400);
});
test('responsive layout and admin screens have no broken images', async ({
  page,
  request,
}, info) => {
  const r = await post(request, 'auth/login', { email: adminEmail, password: 'KhFashion@2026' });
  expect(r.ok()).toBeTruthy();
  await page.context().addCookies((await request.storageState()).cookies);
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  for (const path of [
    '/',
    '/san-pham',
    '/san-pham/ao-1',
    '/admin',
    '/admin/san-pham',
    '/admin/danh-muc',
    '/admin/kho',
    '/admin/don-hang',
    '/admin/voucher',
    '/admin/khach-hang',
    '/tai-khoan',
    '/tai-khoan/dia-chi',
  ]) {
    await page.goto(path);
    await expect(page.locator('h1').first()).toBeVisible();
    expect(
      await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1),
    ).toBe(true);
    await page
      .locator('img')
      .evaluateAll((imgs) => imgs.forEach((img) => ((img as HTMLImageElement).loading = 'eager')));
    await expect
      .poll(() =>
        page
          .locator('img')
          .evaluateAll((imgs) =>
            imgs.every(
              (img) =>
                (img as HTMLImageElement).complete && (img as HTMLImageElement).naturalWidth > 0,
            ),
          ),
      )
      .toBe(true);
  }
  expect(errors).toEqual([]);
  await page.goto('/');
  await page
    .locator('img')
    .evaluateAll((imgs) => imgs.forEach((img) => ((img as HTMLImageElement).loading = 'eager')));
  await expect
    .poll(() =>
      page
        .locator('img')
        .evaluateAll((imgs) =>
          imgs.every(
            (img) =>
              (img as HTMLImageElement).complete && (img as HTMLImageElement).naturalWidth > 0,
          ),
        ),
    )
    .toBe(true);
  await page.screenshot({ path: `test-results/home-${info.project.name}.png`, fullPage: true });
  if (info.project.name === 'mobile') {
    await page.getByRole('button', { name: 'Mở menu' }).click();
    await expect(page.getByRole('navigation', { name: 'Điều hướng chính' })).toBeVisible();
  }
});
test('admin creates product and variant, adjusts stock and manages voucher', async ({
  page,
  request,
}) => {
  const suffix = randomUUID().slice(0, 8),
    name = `Sản phẩm E2E ${suffix}`,
    slug = `e2e-${suffix}`,
    code = `E2E${suffix.toUpperCase()}`;
  const login = await post(request, 'auth/login', {
    email: adminEmail,
    password: 'KhFashion@2026',
  });
  expect(login.ok()).toBeTruthy();
  await page.context().addCookies((await request.storageState()).cookies);
  let productId: string | undefined;
  let voucherId: string | undefined;
  try {
    await page.goto('/admin/san-pham/them');
    await page.getByLabel('Tên sản phẩm', { exact: true }).fill(name);
    await page.getByLabel('Đường dẫn (slug)').fill(slug);
    await page.getByLabel('Chất liệu', { exact: true }).fill('Cotton kiểm thử');
    await page.getByLabel('Giá bán (đ)').fill('350000');
    await page
      .getByLabel('Mô tả', { exact: true })
      .fill('Sản phẩm được tạo bởi bài kiểm thử trình duyệt.');
    await page.getByRole('button', { name: 'Lưu sản phẩm', exact: true }).click();
    await expect(page.getByRole('link', { name, exact: true })).toBeVisible();
    const product = await db.product.findUniqueOrThrow({ where: { slug } });
    productId = product.id;
    await page.getByRole('link', { name, exact: true }).click();
    const variantSection = page
      .locator('section')
      .filter({ has: page.getByRole('heading', { name: 'Thêm biến thể', exact: true }) });
    await variantSection.getByLabel('SKU', { exact: true }).fill(`E2E-${suffix}`);
    await variantSection.getByLabel('Size', { exact: true }).fill('M');
    await variantSection.getByLabel('Màu', { exact: true }).fill('Kem');
    await variantSection.getByRole('button', { name: /Thêm biến thể/ }).click();
    await expect(page.getByRole('heading', { name: 'Các biến thể (1)' })).toBeVisible();
    const variant = await db.variant.findFirstOrThrow({ where: { productId } });
    await page.goto(`/admin/kho?q=E2E-${suffix}`);
    await page.getByRole('button', { name: 'Điều chỉnh', exact: true }).click();
    await page.getByLabel('Số lượng điều chỉnh', { exact: true }).fill('7');
    await page.getByLabel('Lý do', { exact: true }).fill('Nhập kho kiểm thử');
    await page.getByRole('button', { name: 'Lưu', exact: true }).click();
    await expect
      .poll(async () => (await db.variant.findUniqueOrThrow({ where: { id: variant.id } })).stock)
      .toBe(7);
    const negative = await post(request, `admin/inventory/${variant.id}`, {
      delta: -8,
      reason: 'Thử trừ vượt tồn',
    });
    expect(negative.status()).toBe(400);
    await page.goto('/admin/voucher');
    const form = page
      .locator('section')
      .filter({ has: page.getByRole('heading', { name: 'Tạo mã ưu đãi', exact: true }) });
    await form.getByLabel('Mã voucher', { exact: true }).fill(code);
    await form.getByLabel('Bắt đầu (giờ địa phương)').fill('2026-01-01T00:00');
    await form.getByLabel('Kết thúc (giờ địa phương)').fill('2030-01-01T00:00');
    await form.getByRole('button', { name: 'Tạo voucher', exact: true }).click();
    await expect(page.locator('summary').filter({ hasText: code })).toBeVisible();
    voucherId = (await db.voucher.findUniqueOrThrow({ where: { code } })).id;
    await page.goto(`/admin/san-pham?q=${encodeURIComponent(name)}`);
    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Ẩn', exact: true }).click();
    await expect
      .poll(async () => (await db.product.findUniqueOrThrow({ where: { id: productId } })).active)
      .toBe(false);
  } finally {
    if (!productId) productId = (await db.product.findUnique({ where: { slug } }))?.id;
    if (productId) {
      const vs = await db.variant.findMany({ where: { productId } });
      await db.stockMovement.deleteMany({ where: { variantId: { in: vs.map((v) => v.id) } } });
      await db.variant.deleteMany({ where: { productId } });
      await db.product.delete({ where: { id: productId } });
    }
    if (!voucherId) voucherId = (await db.voucher.findUnique({ where: { code } }))?.id;
    if (voucherId) await db.voucher.delete({ where: { id: voucherId } });
  }
});

test('customer updates profile and creates, edits and deletes own address', async ({
  page,
  request,
}) => {
  const email = `e2e-address-${randomUUID()}@test.example`;
  const registered = await post(request, 'auth/register', {
    email,
    name: 'Khách địa chỉ',
    password: 'TestPass@2026',
  });
  expect(registered.ok()).toBeTruthy();
  createdUsers.push((await db.user.findUniqueOrThrow({ where: { email } })).id);
  await page.context().addCookies((await request.storageState()).cookies);
  await page.goto('/tai-khoan');
  await page.getByLabel('Họ và tên', { exact: true }).fill('Khách đổi tên');
  await page.getByRole('button', { name: 'Lưu thông tin', exact: true }).click();
  await expect(page.getByRole('status')).toHaveText('Đã lưu thông tin');
  await page.goto('/tai-khoan/dia-chi');
  const form = page
    .locator('section')
    .filter({ has: page.getByRole('heading', { name: 'Thêm địa chỉ mới', exact: true }) });
  await form.getByLabel('Tên người nhận', { exact: true }).fill('Khách địa chỉ');
  await form.getByLabel('Số điện thoại', { exact: true }).fill('0901234567');
  await form.getByLabel('Địa chỉ đầy đủ', { exact: true }).fill('123 Đường Demo, TP. Hồ Chí Minh');
  await form.getByRole('button', { name: 'Thêm địa chỉ', exact: true }).click();
  const card = page.locator('.address-card');
  await expect(card).toHaveCount(1);
  await card.getByText('Sửa địa chỉ', { exact: true }).click();
  await card.getByLabel('Địa chỉ đầy đủ', { exact: true }).fill('456 Đường Mới, TP. Hồ Chí Minh');
  await card.getByRole('button', { name: 'Lưu địa chỉ', exact: true }).click();
  await expect(card.getByText('456 Đường Mới, TP. Hồ Chí Minh', { exact: true })).toBeVisible();
  page.once('dialog', (dialog) => dialog.accept());
  await card.getByRole('button', { name: 'Xóa địa chỉ', exact: true }).click();
  await expect(card).toHaveCount(0);
});
