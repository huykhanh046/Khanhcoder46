# Thiết kế hệ thống

## Kiến trúc triển khai

```mermaid
flowchart TB
 Browser[Trình duyệt: React / cart localStorage]
 Pages[Next.js Server Components: tra cứu, tài khoản, quản trị]
 API[Route Handler /api: kiểm tra Origin, session, Zod, role]
 Auth[iron-session + bcrypt / RateLimit]
 Orders[Module orders: transaction, giữ tồn, trạng thái]
 Pay[Module payment: ký URL, xác minh VNPay IPN]
 Prisma[Prisma Client JS + adapter-pg]
 DB[(PostgreSQL)]
 Worker[Worker orders:watch]
 VNPay[VNPay sandbox - tùy chọn]
 Browser --> Pages
 Browser --> API
 Pages --> Auth
 Pages --> Prisma
 API --> Auth
 API --> Orders
 API --> Pay
 Orders --> Prisma
 Pay --> Prisma
 Prisma --> DB
 Worker --> Orders
 Browser -. chuyển hướng .-> VNPay
 VNPay -. IPN đã ký .-> API
```

Một ứng dụng Next.js xử lý UI và API. Server Components đọc DB, Client Components gọi API cho thay đổi dữ liệu. Role đọc từ DB theo userId trong phiên, không nhận từ client. Các trang admin và API đều kiểm tra quyền. Worker là process riêng, không được giả định sống lại cùng snapshot.

## ERD

```mermaid
erDiagram
 User ||--o{ Address : owns
 User ||--o{ Order : places
 User o|--o{ StockMovement : performs
 Category ||--o{ Product : contains
 Product ||--o{ Variant : has
 Variant ||--o{ OrderItem : references
 Variant ||--o{ StockMovement : records
 Order ||--|{ OrderItem : contains
 Order ||--|{ OrderEvent : logs
 Voucher o|--o{ Order : applies
 User {
  string id PK
  string email UK
  string name
  string passwordHash
  enum role
  datetime createdAt
 }
 Address {
  string id PK
  string userId FK
  string name
  string phone
  string detail
 }
 Category {
  string id PK
  string name
  string slug UK
 }
 Product {
  string id PK
  string categoryId FK
  string slug UK
  string name
  int price
  int compareAt
  string_array images
  boolean active
  boolean featured
 }
 Variant {
  string id PK
  string productId FK
  string sku UK
  string size
  string color
  int stock
  boolean active
 }
 Voucher {
  string id PK
  string code UK
  int percent
  int minOrder
  int maxDiscount
  int usageLimit
  int used
  datetime startsAt
  datetime expiresAt
  boolean active
 }
 Order {
  string id PK
  string userId FK
  string idempotencyKey
  string voucherId FK
  string recipient
  string phone
  string address
  string note
  int subtotal
  int discount
  int shipping
  int total
  enum status
  enum paymentMethod
  enum paymentStatus
  datetime expiresAt
  string paymentRef UK
 }
 OrderItem {
  string id PK
  string orderId FK
  string variantId FK
  string productName
  string sku
  string size
  string color
  string image
  int price
  int quantity
 }
 OrderEvent {
  string id PK
  string orderId FK
  enum status
  string message
  datetime createdAt
 }
 StockMovement {
  string id PK
  string variantId FK
  string actorId FK
  int delta
  string reason
  datetime createdAt
 }
 RateLimit {
  string key PK
  int count
  datetime resetAt
 }
```

Chi tiết đầy đủ tại `prisma/schema.prisma`; ERD rút gọn một số thuộc tính mô tả/timestamps. `OrderItem` lặp thông tin sản phẩm có chủ đích để bảo toàn lịch sử. Unique `(productId,size,color)` và `(userId,idempotencyKey)`. Stock không âm, tổng tiền hợp lệ, giới hạn voucher và giá được bổ sung bằng CHECK trong migration thứ hai. Không cascade xóa lịch sử bán hàng. Địa chỉ là snapshot trên đơn, không liên kết động tới Address.

## Sequence — Checkout và idempotency

```mermaid
sequenceDiagram
 actor C as Khách hàng
 participant UI as Checkout UI
 participant API as API orders
 participant S as Order module
 participant DB as PostgreSQL
 C->>UI: Xác nhận thông tin và tổng tiền
 UI->>API: POST items + địa chỉ + UUID + expectedTotal
 API->>API: Origin + session + Zod
 API->>S: createOrder(userId, input)
 S->>DB: BEGIN SERIALIZABLE
 S->>DB: Tìm userId + UUID
 alt Đơn đã tồn tại
  DB-->>S: Đơn cũ
  S-->>UI: Cùng mã đơn, không trừ kho
 else Lần đầu
  S->>DB: Đọc giá, biến thể, voucher
  S->>S: Tính và đối chiếu tổng tiền
  S->>DB: Claim voucher khi used dưới limit
  S->>DB: UPDATE stock = stock - q WHERE stock >= q
  alt Không đủ tồn hoặc sai dữ liệu
   S->>DB: ROLLBACK
   S-->>UI: Lỗi có thể hiểu được
  else Hợp lệ
   S->>DB: Tạo order + items snapshot + events + movements
   S->>DB: COMMIT
   S-->>UI: Mã đơn
   UI-->>C: Chi tiết đơn thành công
  end
 end
```

## Sequence — Hai khách mua sản phẩm cuối

```mermaid
sequenceDiagram
 participant A as Transaction khách A
 participant DB as PostgreSQL - stock ban đầu 1
 participant B as Transaction khách B
 A->>DB: Đọc biến thể
 B->>DB: Đọc biến thể
 A->>DB: Giảm stock nếu stock >= 1
 B->>DB: Giảm stock nếu stock >= 1
 DB-->>A: Thành công
 A->>DB: Tạo đơn và commit
 DB-->>B: Serialization conflict
 B->>DB: Retry transaction, đọc stock = 0
 DB-->>B: Cập nhật 0 hàng
 B->>DB: Rollback
 Note over A,B: Chỉ một đơn được tạo, không có tồn kho âm
```

Thứ tự khách thắng có thể đảo. Transaction retry tối đa 5 lần; unique constraint xử lý hai request cùng UUID. Giữ thứ tự variantId khi cập nhật nhiều dòng để giảm deadlock.

## Sequence — VNPay sandbox

```mermaid
sequenceDiagram
 actor C as Khách hàng
 participant App as KH Fashion
 participant V as VNPay sandbox
 participant W as Worker
 C->>App: Đặt VNPAY
 App->>App: Transaction giữ tồn, expiresAt +15 phút
 App-->>C: URL ký HMAC-SHA512
 C->>V: Thanh toán thử
 V-->>C: Return URL
 C->>App: Trang kết quả
 App-->>C: Hướng dẫn xem trạng thái chính thức
 V->>App: IPN với chữ ký
 App->>App: Kiểm chữ ký, merchant, amount, trạng thái
 alt Đơn hợp lệ chưa hết hạn và chưa paid
  App->>App: Transaction ghi PAID + paymentRef + event
  App-->>V: RspCode 00
 else Callback lặp
  App-->>V: RspCode 02, không ghi thêm
 else Sai chữ ký hoặc amount
  App-->>V: RspCode 97 hoặc 04
 end
 W->>App: Quét unpaid pending quá hạn
 App->>App: Hủy và hoàn tồn một lần
```

URL return không cập nhật PAID. Thanh toán đến sau khi đơn bị hủy cần đối soát/hoàn tiền ngoài hệ thống; chưa triển khai tự động. Sơ đồ thể hiện thiết kế đã có mã nguồn, chưa chứng minh kết nối merchant thật.

## Activity — Xử lý đơn

```mermaid
flowchart TD
 Start([Bắt đầu]) --> Cart[Kiểm tra giỏ và đăng nhập]
 Cart --> Quote[Nhập địa chỉ / báo giá / voucher]
 Quote --> Submit[Xác nhận đặt hàng]
 Submit --> Check{Transaction hợp lệ?}
 Check -->|Không| Error[Hiển thị lỗi, không thay đổi kho]
 Error --> Quote
 Check -->|Có| Pending[PENDING - đã giữ tồn]
 Pending --> Method{Phương thức?}
 Method -->|COD| Confirm[Admin xác nhận]
 Method -->|VNPay| Paid{IPN hợp lệ trước hết hạn?}
 Paid -->|Có| Confirm
 Paid -->|Chưa| Expire{Quá 15 phút?}
 Expire -->|Có| Cancel[Hủy / hoàn tồn / hoàn lượt voucher]
 Expire -->|Chưa| Paid
 Pending -->|Khách hủy, unpaid| Cancel
 Confirm --> Shipping[SHIPPING]
 Confirm -->|Admin hủy, unpaid| Cancel
 Shipping --> Delivered[DELIVERED / COD chuyển PAID]
 Delivered --> End([Kết thúc])
 Cancel --> End
```

## Mô hình lớp và module

```mermaid
classDiagram
 class AuthModule {
  <<module>>
  session()
  currentUser()
  requireUser(admin)
  pageUser(admin)
 }
 class OrderModule {
  <<module>>
  transaction(work)
  createOrder(userId,input)
  changeOrder(id,user,status)
  expireOrders()
 }
 class RulesModule {
  <<module>>
  calculateTotals(subtotal,voucher)
  canTransition(from,to,admin)
 }
 class PaymentModule {
  <<module>>
  signVnpay(params,secret)
  paymentUrl(order,ip)
  handleIpn(params)
 }
 class RouteHandler {
  <<module>>
  GET(request,context)
  POST(request,context)
  PATCH(request,context)
  DELETE(request,context)
 }
 class PrismaClient {
  user
  product
  variant
  order
  voucher
  transaction()
 }
 RouteHandler --> AuthModule
 RouteHandler --> OrderModule
 RouteHandler --> PaymentModule
 OrderModule --> RulesModule
 OrderModule --> PrismaClient
 PaymentModule --> OrderModule
 PaymentModule --> PrismaClient
 AuthModule --> PrismaClient
```

Code sử dụng function/module TypeScript, không ép thành mô hình lớp MVC không tồn tại. Stereotype `module` phân biệt với lớp `PrismaClient` thực tế. Tách nghiệp vụ đơn hàng khỏi HTTP giúp kiểm thử transaction mà không cần trình duyệt.
