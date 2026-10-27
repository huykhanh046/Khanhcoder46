# Sơ đồ hệ thống

ERD đầy đủ: [erd.mmd](erd.mmd), sinh từ schema MySQL đang chạy. Từ điển PK/FK/unique/index: [schema-dictionary.md](schema-dictionary.md). Quan hệ FK trong ERD biểu thị parent → children; quan hệ one-to-one được ràng buộc thêm bằng UNIQUE trong từ điển (inventories.variant, payment.order, shops.owner).

## Use Case Diagram (Mermaid flowchart)
```mermaid
flowchart LR
 Guest[Khách vãng lai] --> Browse[Xem, tìm kiếm, lọc sản phẩm]
 Guest --> Cart[Giỏ hàng]
 Guest --> Auth[Đăng ký / đăng nhập]
 Customer[Khách hàng] --> Browse
 Customer --> Cart
 Customer --> Checkout[Đặt hàng COD / VNPay]
 Customer --> Account[Hồ sơ / địa chỉ / yêu thích]
 Customer --> Track[Theo dõi / hủy đơn]
 Customer --> Review[Đánh giá / yêu cầu đổi trả]
 Seller[Người bán] --> Apply[Đăng ký shop]
 Seller --> Catalog[Quản lý sản phẩm và kho shop]
 Seller --> Fulfill[Xử lý đơn và đổi trả shop]
 Seller --> Report[Báo cáo shop]
 Admin[Quản trị] --> Catalog
 Admin --> Fulfill
 Admin --> Moderate[Duyệt shop / sản phẩm / đánh giá]
 Admin --> Settings[Người dùng / voucher / banner / cấu hình]
 Admin --> Money[Ghi thu / hoàn thực tế và audit]
 VNPay[Cổng VNPay] --> IPN[Thông báo thanh toán ký số]
 Checkout --> IPN
```

## Kiến trúc
```mermaid
flowchart TB
 Browser[Trình duyệt: Blade / Tailwind / Alpine / Chart.js] --> Web[Routes web + CSRF + Auth + Throttle]
 Web --> Controller[Controllers + FormRequest / validation]
 Controller --> Policy[Policies / Gates]
 Controller --> Services[Cart / Checkout / Shipping / Voucher / Inventory / Order / VNPay / Return / Review / Report Services]
 Services --> Models[Eloquent Models]
 Models --> DB[(MySQL 8.4 InnoDB)]
 Services --> Queue[(Database queue)]
 Queue --> Worker[Queue worker]
 Worker --> SMTP[SMTP hoặc log local]
 Scheduler[Scheduler mỗi phút] --> Expire[orders:expire]
 Expire --> Services
 Gateway[VNPay Sandbox] --> IPN[Signed GET /payments/ipn]
 IPN --> Services
```

## Class Diagram (các lớp nghiệp vụ chính)
```mermaid
classDiagram
 class CheckoutController {
  +index()
  +store(CheckoutRequest)
 }
 class CheckoutService {
  +place(Cart, userId, address, method, code, key, shippingMethod) Order
 }
 class CartService {
  +current() Cart
  +merge()
  +add()
  +quote() array
 }
 class VoucherService {
  +calculate(code, groups, lock) array
 }
 class InventoryService {
  +change()
  +release(ShopOrder)
  +consume(ShopOrder)
  +adjust()
 }
 class OrderService {
  +transition()
  +cancelCustomer()
  +cancelAll()
  +aggregate()
  +expire()
  +recordMoney()
 }
 class VnpayService {
  +configured() bool
  +signature(data) string
  +url(Order, ip) string
  +ipn(data) array
 }
 class ShippingService {
  +methods() array
  +resolve(method) array
 }
 class ReviewService {
  +save(user, product, data)
 }
 class ReturnService {
  +request(user, item, data) ReturnRequest
  +transition(request, status, response)
  +restock(request)
 }
 class ReportService {
  +data(shopId, from, to) array
 }
 class Order {
  +shopOrders()
  +payment()
  +voucherUsage()
 }
 class ShopOrder {
  +order()
  +items()
  +transactions()
 }
 class ProductVariant {
  +product()
  +inventory()
 }
 CheckoutController --> CheckoutService
 CheckoutService --> ShippingService
 CheckoutService --> VoucherService
 CheckoutService --> InventoryService
 CheckoutService --> Order
 OrderService --> InventoryService
 OrderService --> ShopOrder
 VnpayService --> OrderService
 VnpayService --> Order
 Order "1" --> "1..*" ShopOrder
 InventoryService --> ProductVariant
 ReportService --> ShopOrder
 ReturnService --> InventoryService
 ReturnService --> Order
 ReviewService --> ShopOrder
```

## Activity Diagram — checkout
```mermaid
flowchart TD
 A[Giỏ hàng] --> B{Đã đăng nhập?}
 B -- Chưa --> C[Đăng nhập và merge giỏ]
 C --> D[Nhập địa chỉ, chọn vận chuyển và COD/VNPay]
 B -- Rồi --> D
 D --> E[Server validate và transaction]
 E --> F{Checkout key đã có?}
 F -- Có --> G[Trả đơn cũ]
 F -- Chưa --> H[Khóa biến thể, tính giá, khóa voucher]
 H --> I{Đủ kho và voucher hợp lệ?}
 I -- Không --> J[Rollback và hiển thị lỗi]
 I -- Có --> K[Tạo order, shop_orders, snapshots, reserve kho]
 K --> L[Tạo payment, ghi voucher usage, xóa cart, commit]
 L --> M{Phương thức?}
 M -- COD --> N[Trang chi tiết / email queue]
 M -- VNPay --> O[Redirect gateway, chờ IPN]
 O --> P{IPN hợp lệ, thành công, chưa hết hạn?}
 P -- Có --> Q[Capture ledger và paid]
 P -- Không --> R[Thất bại/hết hạn: hủy và release; sai chữ ký: từ chối]
```

## Sequence — đặt hàng
```mermaid
sequenceDiagram
 actor Customer as Khách hàng
 participant HTTP as CheckoutController
 participant Checkout as CheckoutService
 participant DB as MySQL
 participant Inventory as InventoryService
 participant Queue as Queue
 Customer->>HTTP: POST /checkout + CSRF + checkout_key
 HTTP->>HTTP: Auth + CheckoutRequest validation
 HTTP->>Checkout: place(cart, user, address, method, voucher, key, shippingMethod)
 Checkout->>DB: BEGIN, lock cart, check key
 Checkout->>DB: Lock variants theo ID, lấy giá server
 Checkout->>DB: Lock voucher, validate và phân bổ
 Checkout->>DB: INSERT order/shop_orders/order_items snapshots
 loop Mỗi variant theo ID
  Checkout->>Inventory: change(0, +quantity, reserve:itemId)
  Inventory->>DB: SELECT inventory FOR UPDATE
  Inventory->>DB: UPDATE reserved + INSERT movement
 end
 Checkout->>DB: INSERT payment/usage, DELETE cart_items, COMMIT
 Checkout->>Queue: afterCommit OrderPlaced
 Checkout-->>HTTP: Order
 HTTP-->>Customer: Chi tiết đơn hoặc redirect VNPay
```

## Sequence — VNPay
```mermaid
sequenceDiagram
 actor Customer as Khách hàng
 participant App as Laravel
 participant Gateway as VNPay Sandbox
 participant DB as MySQL
 Customer->>App: Đặt hàng online
 App->>DB: Reserve inventory + payment pending
 App-->>Customer: URL pay HMAC-SHA512
 Customer->>Gateway: Mở URL, nhập thông tin sandbox
 Gateway->>App: Signed GET IPN
 App->>App: Verify signature / merchant / amount / currency
 App->>DB: BEGIN, lock order, check payment + expiry
 alt Thành công lần đầu và chưa hết hạn
  App->>DB: Capture từng shop, payment paid, COMMIT
  App-->>Gateway: RspCode 00
 else Callback trùng hoặc hết hạn
  App->>DB: Không ghi capture lại, late ghi rejected
  App-->>Gateway: RspCode 02
 else Thất bại hợp lệ
  App->>DB: Failed event, cancel, release, COMMIT
  App-->>Gateway: RspCode 00
 end
 App->>DB: Ghi payment_callbacks fingerprint, attempts, verdict
 Gateway-->>Customer: Redirect Return
 Customer->>App: GET /payments/return
 App->>DB: Đọc payment thuộc user
 App-->>Customer: Hiển thị trạng thái đã xác nhận từ DB
```

## Sequence — hai khách tranh hàng cuối
```mermaid
sequenceDiagram
 participant A as Checkout A
 participant B as Checkout B
 participant DB as MySQL InnoDB
 A->>DB: BEGIN, lock cart A
 B->>DB: BEGIN, lock cart B
 A->>DB: SELECT variant FOR UPDATE (được khóa)
 B->>DB: SELECT cùng variant FOR UPDATE (đợi)
 A->>DB: Lock inventory: stock=1, reserved=0
 A->>DB: UPDATE reserved=1, INSERT order/item/movement
 A->>DB: COMMIT
 DB-->>B: Nhận lock variant sau commit A
 B->>DB: Lock inventory: stock=1, reserved=1
 B->>B: available=0 < quantity=1
 B->>DB: ROLLBACK toàn bộ đơn B
 B-->>B: ValidationException hết hàng
```

## State machine — đơn con
```mermaid
stateDiagram-v2
 [*] --> pending
 pending --> confirmed
 pending --> cancelled
 confirmed --> packing
 confirmed --> cancelled
 packing --> shipping
 packing --> cancelled
 shipping --> delivered
 delivered --> completed
 completed --> [*]
 cancelled --> [*]
```
Đổi/trả có state riêng và giao dịch refund riêng; không làm đơn con nhảy ngược từ delivered. Parent có pending/processing/delivered/completed/cancelled do OrderService.aggregate tính.

## State machine — yêu cầu đổi/trả
```mermaid
stateDiagram-v2
 [*] --> requested
 requested --> approved
 requested --> rejected
 approved --> received
 received --> resolved
 rejected --> [*]
 resolved --> [*]
```
Nhập lại kho là hành động idempotent riêng khi received/resolved; không đồng nghĩa đã hoàn tiền. /manage/returns gọi ReturnService, còn ghi hoàn thực tế gọi OrderService.recordMoney.
