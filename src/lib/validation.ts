import { z } from 'zod';
export const addressSchema = z.object({
  name: z.string().trim().min(2).max(100),
  phone: z.string().regex(/^(?:\+84|0)[0-9]{9,10}$/, 'Số điện thoại không hợp lệ'),
  detail: z.string().trim().min(10).max(500),
});
export const checkoutSchema = z.object({
  items: z
    .array(z.object({ variantId: z.string().min(1), quantity: z.number().int().min(1).max(20) }))
    .min(1)
    .max(50),
  recipient: addressSchema.shape.name,
  phone: addressSchema.shape.phone,
  address: addressSchema.shape.detail,
  note: z.string().trim().max(500).default(''),
  voucher: z.string().trim().max(40).default(''),
  idempotencyKey: z.string().uuid(),
  expectedTotal: z.number().int().nonnegative().optional(),
  paymentMethod: z.enum(['COD', 'VNPAY']).default('COD'),
});
export type CheckoutInput = z.infer<typeof checkoutSchema>;
export const productSchema = z
  .object({
    name: z.string().trim().min(3).max(150),
    slug: z
      .string()
      .regex(/^[a-z0-9]+(?:-[a-z0-9]+)*$/)
      .max(150),
    description: z.string().trim().min(10).max(5000),
    material: z.string().trim().min(2).max(200),
    price: z.number().int().min(1000).max(100_000_000),
    compareAt: z.number().int().min(1000).max(100_000_000).nullable().default(null),
    categoryId: z.string().min(1),
    images: z
      .array(
        z
          .string()
          .regex(
            /^\/images\/[a-zA-Z0-9._-]+\.(?:svg|png|jpg|jpeg|webp)$/,
            'Dùng đường dẫn ảnh trong /images/',
          ),
      )
      .min(1)
      .max(8),
    active: z.boolean().default(true),
    featured: z.boolean().default(false),
  })
  .refine((v) => v.compareAt === null || v.compareAt >= v.price, {
    message: 'Giá gốc phải lớn hơn hoặc bằng giá bán',
    path: ['compareAt'],
  });
export const variantSchema = z.object({
  sku: z.string().trim().min(2).max(60),
  size: z.string().trim().min(1).max(20),
  color: z.string().trim().min(1).max(40),
  active: z.boolean().default(true),
});
export const voucherSchema = z
  .object({
    code: z
      .string()
      .trim()
      .toUpperCase()
      .regex(/^[A-Z0-9]{3,30}$/),
    percent: z.number().int().min(1).max(100),
    minOrder: z.number().int().min(0).max(100_000_000),
    maxDiscount: z.number().int().min(1000).max(100_000_000),
    usageLimit: z.number().int().min(1).max(1_000_000),
    startsAt: z.iso.datetime(),
    expiresAt: z.iso.datetime(),
    active: z.boolean().default(true),
  })
  .refine((v) => new Date(v.expiresAt) > new Date(v.startsAt), {
    message: 'Ngày kết thúc phải sau ngày bắt đầu',
  });
