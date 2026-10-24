import { NextRequest, NextResponse } from 'next/server';
import { stat } from 'node:fs/promises';
import { join } from 'node:path';
import { z, ZodError } from 'zod';
import { Prisma } from '@prisma/client';
import { hash, compare } from 'bcryptjs';
import { createHash } from 'node:crypto';
import { db } from '@/lib/db';
import { requireUser, session } from '@/lib/auth';
import { AppError } from '@/lib/errors';
import { calculateTotals } from '@/lib/rules';
import {
  addressSchema,
  checkoutSchema,
  productSchema,
  variantSchema,
  voucherSchema,
} from '@/lib/validation';
import { changeOrder, createOrder, transaction } from '@/lib/orders';
import { handleIpn, paymentUrl } from '@/lib/payment';
export const runtime = 'nodejs';

async function parseProduct(body: unknown) {
  const input = productSchema.parse(body);
  for (const image of input.images) {
    try {
      if (!(await stat(join(process.cwd(), 'public', image))).isFile()) throw new Error();
    } catch {
      throw new AppError(
        `Không tìm thấy tệp ảnh ${image}. Thêm ảnh vào public/images trước khi lưu`,
      );
    }
  }
  return input;
}

const authSchema = z.object({
  email: z
    .email()
    .max(200)
    .transform((v) => v.toLowerCase().trim()),
  password: z
    .string()
    .min(8, 'Mật khẩu cần ít nhất 8 ký tự')
    .max(72)
    .refine((v) => Buffer.byteLength(v, 'utf8') <= 72, 'Mật khẩu tối đa 72 byte UTF-8'),
  name: z.string().trim().min(2).max(100).optional(),
});
async function rateLimit(key: string, limit: number) {
  const now = new Date();
  const bucket = await db.$queryRaw<
    { count: number }[]
  >`INSERT INTO "RateLimit" ("key","count","resetAt") VALUES (${key},1,${new Date(Date.now() + 15 * 60000)}) ON CONFLICT ("key") DO UPDATE SET "count"=CASE WHEN "RateLimit"."resetAt"<${now} THEN 1 ELSE "RateLimit"."count"+1 END, "resetAt"=CASE WHEN "RateLimit"."resetAt"<${now} THEN ${new Date(Date.now() + 15 * 60000)} ELSE "RateLimit"."resetAt" END RETURNING "count"`;
  if (bucket[0].count > limit)
    throw new AppError('Bạn thao tác quá nhiều. Vui lòng thử lại sau 15 phút', 429);
}
async function route(req: NextRequest, ctx: { params: Promise<{ path: string[] }> }) {
  try {
    const { path } = await ctx.params;
    const key = path.join('/'),
      method = req.method;
    if (key === 'payments/vnpay/ipn' && method === 'GET') {
      const entries = [...req.nextUrl.searchParams.entries()];
      if (new Set(entries.map(([k]) => k)).size !== entries.length)
        throw new AppError('Duplicate payment parameters');
      return NextResponse.json(await handleIpn(Object.fromEntries(entries)));
    }
    if (!['POST', 'PATCH', 'DELETE'].includes(method))
      throw new AppError('Không tìm thấy API', 404);
    const origin = req.headers.get('origin');
    if (!origin || origin !== new URL(process.env.APP_URL || 'http://localhost:3000').origin)
      throw new AppError('Nguồn yêu cầu không hợp lệ', 403);
    const raw = await req.text();
    if (raw.length > 64000) throw new AppError('Dữ liệu quá lớn', 413);
    let body: unknown = {};
    try {
      body = raw ? JSON.parse(raw) : {};
    } catch {
      throw new AppError('JSON không hợp lệ');
    }
    if (key === 'auth/login' || key === 'auth/register') {
      const input = authSchema.parse(body);
      const ip = req.headers.get('x-forwarded-for')?.split(',')[0]?.trim() || 'local';
      await rateLimit(createHash('sha256').update(`auth:${ip}`).digest('hex'), 40);
      await rateLimit(createHash('sha256').update(`email:${input.email}`).digest('hex'), 12);
      let user;
      if (key === 'auth/register') {
        if (!input.name) throw new AppError('Vui lòng nhập họ tên');
        user = await db.user.create({
          data: {
            name: input.name,
            email: input.email,
            passwordHash: await hash(input.password, 12),
          },
        });
      } else {
        user = await db.user.findUnique({ where: { email: input.email } });
        const valid = await compare(
          input.password,
          user?.passwordHash || '$2b$12$SLrRqMfbVB4zR2byq2EDb.9J/eW1WoK9b/J4uZqwD8RxXX4ZwcYjK',
        );
        if (!user || !valid) throw new AppError('Email hoặc mật khẩu không chính xác', 401);
      }
      const s = await session();
      s.userId = user.id;
      await s.save();
      return NextResponse.json({ ok: true, role: user.role });
    }
    if (key === 'auth/logout') {
      const s = await session();
      s.destroy();
      return NextResponse.json({ ok: true });
    }
    const user = await requireUser(key.startsWith('admin/'));
    let result: unknown = { ok: true };
    if (key === 'account/profile' && method === 'PATCH') {
      const input = z.object({ name: z.string().trim().min(2).max(100) }).parse(body);
      result = await db.user.update({
        where: { id: user.id },
        data: input,
        select: { id: true, name: true },
      });
    } else if (key === 'account/addresses' && method === 'POST') {
      const input = addressSchema.parse(body);
      result = await transaction(async (tx) => {
        const count = await tx.address.count({ where: { userId: user.id } });
        if (count >= 10) throw new AppError('Tối đa 10 địa chỉ');
        return tx.address.create({ data: { ...input, userId: user.id } });
      });
    } else if (path[0] === 'account' && path[1] === 'addresses' && path[2] && method === 'PATCH') {
      const r = await db.address.updateMany({
        where: { id: path[2], userId: user.id },
        data: addressSchema.parse(body),
      });
      if (!r.count) throw new AppError('Không tìm thấy địa chỉ', 404);
    } else if (path[0] === 'account' && path[1] === 'addresses' && path[2] && method === 'DELETE') {
      const r = await db.address.deleteMany({ where: { id: path[2], userId: user.id } });
      if (!r.count) throw new AppError('Không tìm thấy địa chỉ', 404);
    } else if (key === 'orders/quote' && method === 'POST') {
      const input = checkoutSchema.pick({ items: true, voucher: true }).parse(body);
      const quantities = new Map<string, number>();
      for (const i of input.items)
        quantities.set(i.variantId, (quantities.get(i.variantId) || 0) + i.quantity);
      const variants = await db.variant.findMany({
        where: { id: { in: [...quantities.keys()] }, active: true, product: { active: true } },
        include: { product: true },
      });
      if (variants.length !== quantities.size)
        throw new AppError('Sản phẩm trong giỏ đã ngừng bán');
      for (const v of variants)
        if (v.stock < quantities.get(v.id)!)
          throw new AppError(`${v.product.name} không đủ tồn kho`);
      const subtotal = variants.reduce((s, v) => s + v.product.price * quantities.get(v.id)!, 0);
      let voucher = null;
      if (input.voucher) {
        voucher = await db.voucher.findUnique({ where: { code: input.voucher.toUpperCase() } });
        const now = new Date();
        if (
          !voucher ||
          !voucher.active ||
          voucher.startsAt > now ||
          voucher.expiresAt <= now ||
          voucher.used >= voucher.usageLimit
        )
          throw new AppError('Voucher không hợp lệ hoặc hết lượt');
      }
      result = calculateTotals(subtotal, voucher);
    } else if (key === 'orders' && method === 'POST') {
      const order = await createOrder(user.id, checkoutSchema.parse(body));
      result = {
        id: order.id,
        paymentUrl: paymentUrl(
          order,
          z.ipv4().safeParse(req.headers.get('x-forwarded-for')?.split(',')[0]?.trim()).success
            ? req.headers.get('x-forwarded-for')!.split(',')[0].trim()
            : '127.0.0.1',
        ),
      };
    } else if (path[0] === 'orders' && path[1] && path[2] === 'cancel' && method === 'POST') {
      result = await changeOrder(path[1], user, 'CANCELLED');
    } else if (key === 'admin/categories' && method === 'POST') {
      result = await db.category.create({
        data: z
          .object({
            name: z.string().trim().min(2).max(60),
            slug: z
              .string()
              .regex(/^[a-z0-9-]+$/)
              .max(80),
          })
          .parse(body),
      });
    } else if (path[0] === 'admin' && path[1] === 'categories' && path[2]) {
      if (method === 'DELETE') result = await db.category.delete({ where: { id: path[2] } });
      else if (method === 'PATCH')
        result = await db.category.update({
          where: { id: path[2] },
          data: z
            .object({
              name: z.string().trim().min(2).max(60),
              slug: z
                .string()
                .regex(/^[a-z0-9-]+$/)
                .max(80),
            })
            .parse(body),
        });
      else throw new AppError('Không hỗ trợ thao tác', 405);
    } else if (key === 'admin/products' && method === 'POST') {
      result = await db.product.create({ data: await parseProduct(body) });
    } else if (path[0] === 'admin' && path[1] === 'products' && path[2]) {
      if (method === 'PATCH')
        result = await db.product.update({
          where: { id: path[2] },
          data: await parseProduct(body),
        });
      else if (method === 'DELETE')
        result = await db.product.update({ where: { id: path[2] }, data: { active: false } });
      else throw new AppError('Không hỗ trợ thao tác', 405);
    } else if (path[0] === 'admin' && path[1] === 'variants' && method === 'POST') {
      const { productId, ...input } = variantSchema
        .extend({ productId: z.string().min(1) })
        .parse(body);
      result = await db.variant.create({ data: { ...input, productId } });
    } else if (path[0] === 'admin' && path[1] === 'variants' && path[2] && method === 'PATCH') {
      result = await db.variant.update({ where: { id: path[2] }, data: variantSchema.parse(body) });
    } else if (path[0] === 'admin' && path[1] === 'inventory' && path[2] && method === 'POST') {
      const input = z
        .object({
          delta: z
            .number()
            .int()
            .min(-10000)
            .max(10000)
            .refine((v) => v !== 0),
          reason: z.string().trim().min(5).max(200),
        })
        .parse(body);
      result = await transaction(async (tx) => {
        const r = await tx.variant.updateMany({
          where: { id: path[2], ...(input.delta < 0 ? { stock: { gte: -input.delta } } : {}) },
          data: { stock: { increment: input.delta } },
        });
        if (!r.count) throw new AppError('Biến thể không tồn tại hoặc tồn kho không đủ');
        return tx.stockMovement.create({
          data: { variantId: path[2], actorId: user.id, ...input },
        });
      });
    } else if (key === 'admin/vouchers' && method === 'POST') {
      result = await db.voucher.create({ data: voucherSchema.parse(body) });
    } else if (path[0] === 'admin' && path[1] === 'vouchers' && path[2] && method === 'PATCH') {
      const input = voucherSchema.parse(body);
      result = await transaction(async (tx) => {
        const v = await tx.voucher.findUniqueOrThrow({ where: { id: path[2] } });
        if (input.usageLimit < v.used) throw new AppError('Giới hạn nhỏ hơn số lượt đã dùng');
        return tx.voucher.update({ where: { id: path[2] }, data: input });
      });
    } else if (path[0] === 'admin' && path[1] === 'orders' && path[2] && method === 'PATCH') {
      const { status } = z
        .object({ status: z.enum(['PENDING', 'CONFIRMED', 'SHIPPING', 'DELIVERED', 'CANCELLED']) })
        .parse(body);
      result = await changeOrder(path[2], user, status);
    } else throw new AppError('Không tìm thấy API', 404);
    return NextResponse.json(result);
  } catch (e) {
    if (e instanceof ZodError)
      return NextResponse.json(
        { error: e.issues.map((i) => `${i.path.join('.')}: ${i.message}`).join('; ') },
        { status: 400 },
      );
    if (e instanceof AppError) return NextResponse.json({ error: e.message }, { status: e.status });
    if (e instanceof Prisma.PrismaClientKnownRequestError) {
      const messages: Record<string, string> = {
        P2002: 'Thông tin bị trùng (email, SKU, mã hoặc đường dẫn)',
        P2003: 'Dữ liệu đang được sử dụng hoặc liên kết không hợp lệ',
        P2025: 'Không tìm thấy dữ liệu',
        P2034: 'Có thao tác đồng thời, vui lòng thử lại',
      };
      return NextResponse.json(
        { error: messages[e.code] || 'Không thể cập nhật dữ liệu' },
        { status: 409 },
      );
    }
    console.error('API error', e instanceof Error ? e.message : 'unknown');
    return NextResponse.json(
      { error: 'Hệ thống tạm thời gặp lỗi. Vui lòng thử lại' },
      { status: 500 },
    );
  }
}
export { route as GET, route as POST, route as PATCH, route as DELETE };
