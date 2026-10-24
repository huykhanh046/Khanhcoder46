import { createHmac, timingSafeEqual } from 'node:crypto';
import type { Order } from '@prisma/client';
import { AppError } from './errors';
import { transaction } from './orders';
export function signVnpay(params: Record<string, string>, secret: string) {
  const encoded = Object.keys(params)
    .sort()
    .map((k) => `${encodeURIComponent(k)}=${encodeURIComponent(params[k]).replace(/%20/g, '+')}`)
    .join('&');
  return { encoded, signature: createHmac('sha512', secret).update(encoded).digest('hex') };
}
export function paymentUrl(order: Order, ip: string) {
  if (
    order.paymentMethod !== 'VNPAY' ||
    order.paymentStatus === 'PAID' ||
    order.status === 'CANCELLED'
  )
    return null;
  const secret = process.env.VNPAY_HASH_SECRET,
    tmn = process.env.VNPAY_TMN_CODE;
  if (!secret || !tmn) throw new AppError('VNPay chưa được cấu hình');
  if (!order.expiresAt || order.expiresAt <= new Date())
    throw new AppError('Đơn thanh toán đã hết hạn');
  const stamp = (d: Date) =>
    new Intl.DateTimeFormat('sv-SE', {
      timeZone: 'Asia/Ho_Chi_Minh',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false,
    })
      .format(d)
      .replace(/\D/g, '');
  const { encoded, signature } = signVnpay(
    {
      vnp_Version: '2.1.0',
      vnp_Command: 'pay',
      vnp_TmnCode: tmn,
      vnp_Amount: String(order.total * 100),
      vnp_CurrCode: 'VND',
      vnp_TxnRef: order.id,
      vnp_OrderInfo: `Thanh toan ${order.id}`,
      vnp_OrderType: 'fashion',
      vnp_Locale: 'vn',
      vnp_ReturnUrl: `${process.env.APP_URL}/thanh-toan/ket-qua`,
      vnp_IpAddr: ip,
      vnp_CreateDate: stamp(order.createdAt),
      vnp_ExpireDate: stamp(order.expiresAt),
    },
    secret,
  );
  return `${process.env.VNPAY_URL || 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'}?${encoded}&vnp_SecureHash=${signature}`;
}
export async function handleIpn(params: Record<string, string>) {
  const secret = process.env.VNPAY_HASH_SECRET;
  if (!secret || !process.env.VNPAY_TMN_CODE) throw new AppError('Thanh toán chưa cấu hình', 503);
  const hash = params.vnp_SecureHash;
  const payload = { ...params };
  delete payload.vnp_SecureHash;
  delete payload.vnp_SecureHashType;
  const expected = signVnpay(payload, secret).signature;
  if (
    !hash ||
    !/^[a-fA-F0-9]{128}$/.test(hash) ||
    !timingSafeEqual(Buffer.from(hash.toLowerCase()), Buffer.from(expected))
  )
    return { RspCode: '97', Message: 'Invalid signature' };
  if (payload.vnp_TmnCode !== process.env.VNPAY_TMN_CODE)
    return { RspCode: '97', Message: 'Invalid merchant' };
  return transaction(async (tx) => {
    const order = await tx.order.findUnique({ where: { id: payload.vnp_TxnRef } });
    if (!order) return { RspCode: '01', Message: 'Order not found' };
    if (payload.vnp_Amount !== String(order.total * 100) || order.paymentMethod !== 'VNPAY')
      return { RspCode: '04', Message: 'Invalid amount or method' };
    if (order.paymentStatus === 'PAID') return { RspCode: '02', Message: 'Already confirmed' };
    if (order.status === 'CANCELLED' || !order.expiresAt || order.expiresAt <= new Date())
      return { RspCode: '02', Message: 'Order expired; manual reconciliation required' };
    if (payload.vnp_ResponseCode !== '00' || payload.vnp_TransactionStatus !== '00')
      return { RspCode: '00', Message: 'Failed payment acknowledged' };
    if (!payload.vnp_TransactionNo)
      return { RspCode: '99', Message: 'Missing transaction reference' };
    await tx.order.update({
      where: { id: order.id },
      data: {
        paymentStatus: 'PAID',
        paymentRef: payload.vnp_TransactionNo,
        events: { create: { status: order.status, message: 'VNPay IPN: đã xác minh thanh toán' } },
      },
    });
    return { RspCode: '00', Message: 'Confirm success' };
  });
}
