import { Prisma, type OrderStatus } from '@prisma/client';
import { db } from './db';
import { AppError } from './errors';
import { calculateTotals, canTransition } from './rules';
import type { CheckoutInput } from './validation';
export async function transaction<T>(
  work: (tx: Prisma.TransactionClient) => Promise<T>,
): Promise<T> {
  for (let attempt = 0; ; attempt++) {
    try {
      return await db.$transaction(work, {
        isolationLevel: 'Serializable',
        maxWait: 10000,
        timeout: 15000,
      });
    } catch (e) {
      if (
        e instanceof Prisma.PrismaClientKnownRequestError &&
        ['P2034', 'P2002'].includes(e.code) &&
        attempt < 4
      )
        continue;
      throw e;
    }
  }
}
export async function createOrder(userId: string, input: CheckoutInput) {
  return transaction(async (tx) => {
    const existing = await tx.order.findUnique({
      where: { userId_idempotencyKey: { userId, idempotencyKey: input.idempotencyKey } },
      include: { items: true },
    });
    if (existing) return existing;
    if (
      input.paymentMethod === 'VNPAY' &&
      (!process.env.VNPAY_TMN_CODE || !process.env.VNPAY_HASH_SECRET)
    )
      throw new AppError('VNPay sandbox chưa được cấu hình');
    const quantities = new Map<string, number>();
    for (const item of input.items)
      quantities.set(item.variantId, (quantities.get(item.variantId) || 0) + item.quantity);
    if ([...quantities.values()].some((q) => q > 20))
      throw new AppError('Tối đa 20 sản phẩm mỗi biến thể');
    const variants = await tx.variant.findMany({
      where: { id: { in: [...quantities.keys()] }, active: true, product: { active: true } },
      include: { product: true },
      orderBy: { id: 'asc' },
    });
    if (variants.length !== quantities.size)
      throw new AppError('Sản phẩm không còn được bán. Vui lòng cập nhật giỏ hàng');
    const subtotal = variants.reduce((sum, v) => sum + v.product.price * quantities.get(v.id)!, 0);
    let voucher = null;
    if (input.voucher) {
      voucher = await tx.voucher.findUnique({ where: { code: input.voucher.toUpperCase() } });
      const now = new Date();
      if (
        !voucher ||
        !voucher.active ||
        voucher.startsAt > now ||
        voucher.expiresAt <= now ||
        voucher.used >= voucher.usageLimit
      )
        throw new AppError('Voucher không hợp lệ, đã hết hạn hoặc hết lượt');
      const claimed = await tx.voucher.updateMany({
        where: { id: voucher.id, used: { lt: voucher.usageLimit } },
        data: { used: { increment: 1 } },
      });
      if (!claimed.count) throw new AppError('Voucher đã hết lượt sử dụng');
    }
    const totals = calculateTotals(subtotal, voucher);
    if (input.expectedTotal !== undefined && input.expectedTotal !== totals.total)
      throw new AppError(
        'Giá hoặc phí đã thay đổi. Vui lòng tải lại trang để xác nhận tổng tiền mới',
        409,
      );
    for (const v of variants) {
      const q = quantities.get(v.id)!;
      const changed = await tx.variant.updateMany({
        where: { id: v.id, stock: { gte: q }, active: true },
        data: { stock: { decrement: q } },
      });
      if (!changed.count)
        throw new AppError(`${v.product.name} (${v.size}, ${v.color}) không đủ tồn kho`, 409);
      await tx.stockMovement.create({
        data: { variantId: v.id, delta: -q, reason: 'Giữ hàng cho đơn mới', actorId: userId },
      });
    }
    return tx.order.create({
      data: {
        userId,
        idempotencyKey: input.idempotencyKey,
        recipient: input.recipient,
        phone: input.phone,
        address: input.address,
        note: input.note,
        paymentMethod: input.paymentMethod,
        voucherId: voucher?.id,
        voucherCode: voucher?.code,
        ...totals,
        expiresAt: input.paymentMethod === 'VNPAY' ? new Date(Date.now() + 15 * 60000) : null,
        items: {
          create: variants.map((v) => ({
            variantId: v.id,
            productName: v.product.name,
            sku: v.sku,
            size: v.size,
            color: v.color,
            image: v.product.images[0],
            price: v.product.price,
            quantity: quantities.get(v.id)!,
          })),
        },
        events: {
          create: { status: 'PENDING', message: 'Đơn hàng được tạo, tồn kho đã được giữ' },
        },
      },
      include: { items: true },
    });
  });
}
export async function changeOrder(
  id: string,
  user: { id: string; role: string },
  status: OrderStatus,
) {
  return transaction(async (tx) => {
    const order = await tx.order.findUnique({ where: { id }, include: { items: true } });
    if (!order || (user.role !== 'ADMIN' && order.userId !== user.id))
      throw new AppError('Không tìm thấy đơn hàng', 404);
    if (!canTransition(order.status, status, user.role === 'ADMIN'))
      throw new AppError('Không thể chuyển sang trạng thái này');
    if (order.status === status) return order;
    if (status === 'CANCELLED' && order.paymentStatus === 'PAID')
      throw new AppError('Đơn đã thanh toán cần quy trình hoàn tiền; chưa hỗ trợ tự động');
    if (status === 'CONFIRMED' && order.paymentMethod === 'VNPAY' && order.paymentStatus !== 'PAID')
      throw new AppError('Đơn VNPay chưa thanh toán');
    if (status === 'CANCELLED') {
      for (const item of [...order.items].sort((a, b) => a.variantId.localeCompare(b.variantId))) {
        await tx.variant.update({
          where: { id: item.variantId },
          data: { stock: { increment: item.quantity } },
        });
        await tx.stockMovement.create({
          data: {
            variantId: item.variantId,
            delta: item.quantity,
            reason: `Hoàn tồn ${order.id}`,
            actorId: user.id,
          },
        });
      }
      if (order.voucherId)
        await tx.voucher.update({
          where: { id: order.voucherId },
          data: { used: { decrement: 1 } },
        });
    }
    return tx.order.update({
      where: { id },
      data: {
        status,
        ...(status === 'DELIVERED' && order.paymentMethod === 'COD'
          ? { paymentStatus: 'PAID' }
          : {}),
        events: {
          create: {
            status,
            message: `${user.role === 'ADMIN' ? 'Quản trị viên' : 'Khách hàng'}: ${status}`,
          },
        },
      },
    });
  });
}
export async function expireOrders() {
  const orders = await db.order.findMany({
    where: {
      paymentMethod: 'VNPAY',
      paymentStatus: 'UNPAID',
      status: 'PENDING',
      expiresAt: { lte: new Date() },
    },
    select: { id: true, userId: true },
  });
  for (const order of orders) {
    try {
      await changeOrder(order.id, { id: order.userId, role: 'CUSTOMER' }, 'CANCELLED');
    } catch (e) {
      if (!(e instanceof AppError)) throw e;
    }
  }
  return orders.length;
}
