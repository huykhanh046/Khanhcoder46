import { describe, it, expect } from 'vitest';
import { calculateTotals, canTransition } from '../src/lib/rules';
import { signVnpay } from '../src/lib/payment';
import { checkoutSchema } from '../src/lib/validation';
describe('Pricing and order rules', () => {
  it('rejects invalid or overflowing subtotal before database writes', () => {
    expect(() => calculateTotals(-1)).toThrow();
    expect(() => calculateTotals(2_000_000_001)).toThrow();
    expect(() => calculateTotals(1.5)).toThrow();
  });
  it('uses pre-discount subtotal for free shipping', () => {
    expect(calculateTotals(699000, { percent: 10, maxDiscount: 100000, minOrder: 300000 })).toEqual(
      { subtotal: 699000, discount: 69900, shipping: 0, total: 629100 },
    );
  });
  it('caps voucher discount and rounds to whole VND', () => {
    expect(
      calculateTotals(1999999, { percent: 10, maxDiscount: 100000, minOrder: 0 }).discount,
    ).toBe(100000);
    expect(calculateTotals(100001, { percent: 5, maxDiscount: 100000, minOrder: 0 }).discount).toBe(
      5000,
    );
  });
  it('rejects a voucher below minimum', () => {
    expect(() =>
      calculateTotals(200000, { percent: 10, maxDiscount: 100000, minOrder: 300000 }),
    ).toThrow();
  });
  it('allows customer pending cancellation only', () => {
    expect(canTransition('PENDING', 'CANCELLED', false)).toBe(true);
    expect(canTransition('CONFIRMED', 'CANCELLED', false)).toBe(false);
    expect(canTransition('PENDING', 'DELIVERED', true)).toBe(false);
    expect(canTransition('SHIPPING', 'DELIVERED', true)).toBe(true);
    expect(canTransition('DELIVERED', 'CANCELLED', true)).toBe(false);
  });
  it('rejects invalid quantities and identifiers', () => {
    expect(checkoutSchema.safeParse({ items: [{ variantId: 'x', quantity: -1 }] }).success).toBe(
      false,
    );
  });
  it('signs canonical VNPay parameters independent of insertion order', () => {
    expect(signVnpay({ b: 'hello world', a: '1' }, 'test').encoded).toBe('a=1&b=hello+world');
    expect(signVnpay({ b: 'hello world', a: '1' }, 'test').signature).toBe(
      signVnpay({ a: '1', b: 'hello world' }, 'test').signature,
    );
  });
});
