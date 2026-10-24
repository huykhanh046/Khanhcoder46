import 'dotenv/config';
import { beforeAll, afterAll, describe, it, expect } from 'vitest';
import { randomUUID } from 'node:crypto';
import { db } from '../src/lib/db';
import { createOrder, changeOrder, expireOrders } from '../src/lib/orders';
import { handleIpn, signVnpay } from '../src/lib/payment';
const tag = `test-${randomUUID()}`;
let customer: string,
  other: string,
  admin: string,
  product: string,
  variant: string,
  category: string,
  voucher: string;
const payload = (key = randomUUID()) => ({
  items: [{ variantId: variant, quantity: 1 }],
  recipient: 'Khách kiểm thử',
  phone: '0901234567',
  address: '123 Đường Kiểm Thử, TP. Hồ Chí Minh',
  note: '',
  voucher: '',
  idempotencyKey: key,
  paymentMethod: 'COD' as const,
});
beforeAll(async () => {
  const a = await db.user.create({
    data: { email: `${tag}@test.example`, name: 'Integration test', passwordHash: 'unused' },
  });
  customer = a.id;
  other = (
    await db.user.create({
      data: { email: `other-${tag}@test.example`, name: 'Other test', passwordHash: 'unused' },
    })
  ).id;
  admin = (
    await db.user.create({
      data: {
        email: `admin-${tag}@test.example`,
        name: 'Admin test',
        passwordHash: 'unused',
        role: 'ADMIN',
      },
    })
  ).id;
  category = (await db.category.create({ data: { name: tag, slug: tag } })).id;
  product = (
    await db.product.create({
      data: {
        name: 'Test limited product',
        slug: tag,
        description: 'For automated tests',
        material: 'Test',
        price: 399000,
        images: ['/images/product-1.svg'],
        categoryId: category,
      },
    })
  ).id;
  variant = (
    await db.variant.create({
      data: { productId: product, sku: tag, size: 'S', color: 'Kem', stock: 1 },
    })
  ).id;
  voucher = (
    await db.voucher.create({
      data: {
        code: tag.toUpperCase(),
        percent: 10,
        minOrder: 100000,
        maxDiscount: 100000,
        usageLimit: 1,
        startsAt: new Date(Date.now() - 60000),
        expiresAt: new Date(Date.now() + 3600000),
      },
    })
  ).id;
});
afterAll(async () => {
  const users = [customer, other, admin].filter(Boolean);
  const orders = await db.order.findMany({
    where: { userId: { in: users } },
    select: { id: true },
  });
  const ids = orders.map((o) => o.id);
  await db.orderEvent.deleteMany({ where: { orderId: { in: ids } } });
  await db.orderItem.deleteMany({ where: { orderId: { in: ids } } });
  await db.order.deleteMany({ where: { id: { in: ids } } });
  await db.stockMovement.deleteMany({ where: { variantId: variant } });
  if (variant) await db.variant.delete({ where: { id: variant } });
  if (product) await db.product.delete({ where: { id: product } });
  if (category) await db.category.delete({ where: { id: category } });
  if (voucher) await db.voucher.delete({ where: { id: voucher } });
  await db.user.deleteMany({ where: { id: { in: users } } });
  await db.$disconnect();
});
describe.sequential('PostgreSQL transactions', () => {
  it('only one buyer can purchase the last item', async () => {
    const results = await Promise.allSettled([
      createOrder(customer, payload()),
      createOrder(other, payload()),
    ]);
    expect(results.filter((r) => r.status === 'fulfilled')).toHaveLength(1);
    expect(results.filter((r) => r.status === 'rejected')).toHaveLength(1);
    expect((await db.variant.findUniqueOrThrow({ where: { id: variant } })).stock).toBe(0);
    const order = results.find((r) => r.status === 'fulfilled');
    if (order?.status === 'fulfilled')
      await changeOrder(order.value.id, { id: order.value.userId, role: 'CUSTOMER' }, 'CANCELLED');
  });
  it('repeated checkout creates exactly one order and one stock deduction', async () => {
    const key = randomUUID();
    const orders = await Promise.all([
      createOrder(customer, payload(key)),
      createOrder(customer, payload(key)),
    ]);
    expect(orders[0].id).toBe(orders[1].id);
    expect(await db.order.count({ where: { userId: customer, idempotencyKey: key } })).toBe(1);
    expect((await db.variant.findUniqueOrThrow({ where: { id: variant } })).stock).toBe(0);
    await changeOrder(orders[0].id, { id: customer, role: 'CUSTOMER' }, 'CANCELLED');
  });
  it('forbids another customer and restocks once for concurrent cancellation', async () => {
    const order = await createOrder(customer, payload());
    await expect(
      changeOrder(order.id, { id: other, role: 'CUSTOMER' }, 'CANCELLED'),
    ).rejects.toThrow('Không tìm thấy');
    await Promise.all([
      changeOrder(order.id, { id: customer, role: 'CUSTOMER' }, 'CANCELLED'),
      changeOrder(order.id, { id: customer, role: 'CUSTOMER' }, 'CANCELLED'),
    ]);
    expect((await db.variant.findUniqueOrThrow({ where: { id: variant } })).stock).toBe(1);
  });
  it('limits concurrent voucher claims and releases usage on cancellation', async () => {
    await db.variant.update({ where: { id: variant }, data: { stock: 2 } });
    const input = { ...payload(), voucher: tag.toUpperCase() };
    const results = await Promise.allSettled([
      createOrder(customer, input),
      createOrder(other, { ...input, idempotencyKey: randomUUID() }),
    ]);
    expect(results.filter((r) => r.status === 'fulfilled')).toHaveLength(1);
    expect((await db.voucher.findUniqueOrThrow({ where: { id: voucher } })).used).toBe(1);
    const order = results.find((r) => r.status === 'fulfilled');
    if (order?.status === 'fulfilled')
      await changeOrder(order.value.id, { id: order.value.userId, role: 'CUSTOMER' }, 'CANCELLED');
    expect((await db.voucher.findUniqueOrThrow({ where: { id: voucher } })).used).toBe(0);
  });
  it('snapshots price and enforces transitions through COD delivery', async () => {
    const order = await createOrder(customer, payload());
    await db.product.update({ where: { id: product }, data: { price: 999000 } });
    expect(order.items[0].price).toBe(399000);
    expect(order.total).toBe(429000);
    await expect(
      changeOrder(order.id, { id: admin, role: 'ADMIN' }, 'DELIVERED'),
    ).rejects.toThrow();
    await changeOrder(order.id, { id: admin, role: 'ADMIN' }, 'CONFIRMED');
    await expect(
      changeOrder(order.id, { id: customer, role: 'CUSTOMER' }, 'CANCELLED'),
    ).rejects.toThrow();
    await changeOrder(order.id, { id: admin, role: 'ADMIN' }, 'SHIPPING');
    const final = await changeOrder(order.id, { id: admin, role: 'ADMIN' }, 'DELIVERED');
    expect(final.paymentStatus).toBe('PAID');
    await expect(
      changeOrder(order.id, { id: admin, role: 'ADMIN' }, 'CANCELLED'),
    ).rejects.toThrow();
  });
  it('verifies VNPay signatures, amount and repeated IPN (synthetic callback)', async () => {
    process.env.VNPAY_HASH_SECRET = 'synthetic-test-secret';
    process.env.VNPAY_TMN_CODE = 'TEST';
    const o = await createOrder(customer, { ...payload(), paymentMethod: 'VNPAY' });
    const p = {
      vnp_TmnCode: 'TEST',
      vnp_TxnRef: o.id,
      vnp_Amount: String(o.total * 100),
      vnp_ResponseCode: '00',
      vnp_TransactionStatus: '00',
      vnp_TransactionNo: `tx-${tag}`,
    };
    expect((await handleIpn({ ...p, vnp_SecureHash: 'bad' })).RspCode).toBe('97');
    const bad = { ...p, vnp_Amount: '100' };
    expect(
      (
        await handleIpn({
          ...bad,
          vnp_SecureHash: signVnpay(bad, process.env.VNPAY_HASH_SECRET).signature,
        })
      ).RspCode,
    ).toBe('04');
    const signed = { ...p, vnp_SecureHash: signVnpay(p, process.env.VNPAY_HASH_SECRET).signature };
    expect((await handleIpn(signed)).RspCode).toBe('00');
    expect((await handleIpn(signed)).RspCode).toBe('02');
    expect((await db.order.findUniqueOrThrow({ where: { id: o.id } })).paymentStatus).toBe('PAID');
  });
  it('expires unpaid VNPay orders and restores inventory', async () => {
    await db.variant.update({ where: { id: variant }, data: { stock: 1 } });
    const o = await createOrder(customer, { ...payload(), paymentMethod: 'VNPAY' });
    await db.order.update({
      where: { id: o.id },
      data: { expiresAt: new Date(Date.now() - 1000) },
    });
    await expireOrders();
    const cancelled = await db.order.findUniqueOrThrow({ where: { id: o.id } });
    expect(cancelled.status).toBe('CANCELLED');
    expect((await db.variant.findUniqueOrThrow({ where: { id: variant } })).stock).toBe(1);
    delete process.env.VNPAY_HASH_SECRET;
    delete process.env.VNPAY_TMN_CODE;
  });
});
