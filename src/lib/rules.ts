import { AppError } from './errors';
import type { OrderStatus } from '@prisma/client';
export function calculateTotals(
  subtotal: number,
  voucher?: { percent: number; maxDiscount: number; minOrder: number } | null,
) {
  if (!Number.isInteger(subtotal) || subtotal < 0 || subtotal > 2_000_000_000)
    throw new AppError('Giá trị tiền hàng không hợp lệ hoặc vượt giới hạn 2 tỷ đồng');
  if (voucher && subtotal < voucher.minOrder)
    throw new AppError('Đơn hàng chưa đạt giá trị tối thiểu của voucher');
  const discount = voucher
    ? Math.min(Math.floor((subtotal * voucher.percent) / 100), voucher.maxDiscount, subtotal)
    : 0;
  const shipping = subtotal >= 699000 ? 0 : 30000;
  return { subtotal, discount, shipping, total: subtotal - discount + shipping };
}
export function canTransition(from: OrderStatus, to: OrderStatus, admin: boolean) {
  if (from === to) return true;
  if (!admin) return from === 'PENDING' && to === 'CANCELLED';
  const allowed: Record<OrderStatus, OrderStatus[]> = {
    PENDING: ['CONFIRMED', 'CANCELLED'],
    CONFIRMED: ['SHIPPING', 'CANCELLED'],
    SHIPPING: ['DELIVERED'],
    DELIVERED: [],
    CANCELLED: [],
  };
  return allowed[from].includes(to);
}
