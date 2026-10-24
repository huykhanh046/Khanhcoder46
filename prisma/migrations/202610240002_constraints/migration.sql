ALTER TABLE "Variant" ADD CONSTRAINT "Variant_stock_nonnegative" CHECK (stock >= 0);
ALTER TABLE "Product" ADD CONSTRAINT "Product_price_positive" CHECK (price >= 1000 AND ("compareAt" IS NULL OR "compareAt" >= price));
ALTER TABLE "Voucher" ADD CONSTRAINT "Voucher_valid_limits" CHECK (percent BETWEEN 1 AND 100 AND used >= 0 AND used <= "usageLimit" AND "minOrder" >= 0 AND "maxDiscount" >= 0 AND "expiresAt" > "startsAt");
ALTER TABLE "OrderItem" ADD CONSTRAINT "OrderItem_valid_values" CHECK (quantity > 0 AND price >= 0);
ALTER TABLE "Order" ADD CONSTRAINT "Order_valid_total" CHECK (subtotal >= 0 AND discount >= 0 AND shipping >= 0 AND total = subtotal - discount + shipping AND total >= 0);
CREATE INDEX "Order_status_createdAt_idx" ON "Order" (status,"createdAt");
CREATE INDEX "Order_expiresAt_idx" ON "Order" ("expiresAt") WHERE "paymentMethod"='VNPAY' AND status='PENDING' AND "paymentStatus"='UNPAID';
CREATE INDEX "StockMovement_createdAt_idx" ON "StockMovement" ("createdAt");
