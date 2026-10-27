# Từ điển dữ liệu thực tế

Xuất từ information_schema của MySQL 8.4 bằng `php scripts/export-schema.php`. Xem schema.json để xử lý tự động. CHECK: inventories.stock >= inventories.reserved; reviews.rating BETWEEN 1 AND 5. Tồn và tiền không âm bằng UNSIGNED; các kiểm tra nghiệp vụ khác ở service/validation.

## addresses

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| user_id | bigint unsigned | NO | NULL | MUL |
| name | varchar(255) | NO | NULL |  |
| phone | varchar(20) | NO | NULL |  |
| address | varchar(255) | NO | NULL |  |
| city | varchar(255) | NO | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `addresses_user_id_foreign` (user_id)
- `PRIMARY` UNIQUE (id)
- FK `user_id` → `users.id`

## audit_logs

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| user_id | bigint unsigned | YES | NULL | MUL |
| action | varchar(255) | NO | NULL |  |
| subject_type | varchar(255) | NO | NULL |  |
| subject_id | bigint unsigned | NO | NULL |  |
| changes | json | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `audit_logs_user_id_foreign` (user_id)
- `PRIMARY` UNIQUE (id)
- FK `user_id` → `users.id`

## banners

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| title | varchar(255) | NO | NULL |  |
| subtitle | varchar(255) | YES | NULL |  |
| image_path | varchar(255) | YES | NULL |  |
| link | varchar(255) | NO | /products |  |
| position | int unsigned | NO | 0 |  |
| active | tinyint(1) | NO | 1 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)

## cache

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| key | varchar(255) | NO | NULL | PRI |
| value | mediumtext | NO | NULL |  |
| expiration | int | NO | NULL | MUL |

Indexes:
- `cache_expiration_index` (expiration)
- `PRIMARY` UNIQUE (key)

## cache_locks

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| key | varchar(255) | NO | NULL | PRI |
| owner | varchar(255) | NO | NULL |  |
| expiration | int | NO | NULL | MUL |

Indexes:
- `cache_locks_expiration_index` (expiration)
- `PRIMARY` UNIQUE (key)

## cart_items

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| cart_id | bigint unsigned | NO | NULL | MUL |
| product_variant_id | bigint unsigned | NO | NULL | MUL |
| quantity | int unsigned | NO | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `cart_items_cart_id_product_variant_id_unique` UNIQUE (cart_id, product_variant_id)
- `cart_items_product_variant_id_foreign` (product_variant_id)
- `PRIMARY` UNIQUE (id)
- FK `cart_id` → `carts.id`
- FK `product_variant_id` → `product_variants.id`

## carts

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| user_id | bigint unsigned | YES | NULL | UNI |
| token | char(36) | NO | NULL | UNI |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `carts_token_unique` UNIQUE (token)
- `carts_user_id_unique` UNIQUE (user_id)
- `PRIMARY` UNIQUE (id)
- FK `user_id` → `users.id`

## categories

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| name | varchar(255) | NO | NULL |  |
| slug | varchar(255) | NO | NULL | UNI |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `categories_slug_unique` UNIQUE (slug)
- `PRIMARY` UNIQUE (id)

## collections

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| name | varchar(100) | NO | NULL |  |
| slug | varchar(120) | NO | NULL | UNI |
| description | text | YES | NULL |  |
| image_path | varchar(255) | YES | NULL |  |
| active | tinyint(1) | NO | 1 | MUL |
| position | int unsigned | NO | 0 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `collections_active_index` (active)
- `collections_slug_unique` UNIQUE (slug)
- `PRIMARY` UNIQUE (id)

## failed_jobs

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| uuid | varchar(255) | NO | NULL | UNI |
| connection | text | NO | NULL |  |
| queue | text | NO | NULL |  |
| payload | longtext | NO | NULL |  |
| exception | longtext | NO | NULL |  |
| failed_at | timestamp | NO | CURRENT_TIMESTAMP |  |

Indexes:
- `failed_jobs_uuid_unique` UNIQUE (uuid)
- `PRIMARY` UNIQUE (id)

## inventories

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| product_variant_id | bigint unsigned | NO | NULL | UNI |
| stock | int unsigned | NO | 0 |  |
| reserved | int unsigned | NO | 0 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `inventories_product_variant_id_unique` UNIQUE (product_variant_id)
- `PRIMARY` UNIQUE (id)
- FK `product_variant_id` → `product_variants.id`

## inventory_movements

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| product_variant_id | bigint unsigned | NO | NULL | MUL |
| user_id | bigint unsigned | YES | NULL | MUL |
| stock_delta | int | NO | NULL |  |
| reserved_delta | int | NO | NULL |  |
| reason | varchar(255) | NO | NULL |  |
| event_key | varchar(255) | NO | NULL | UNI |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `inventory_movements_event_key_unique` UNIQUE (event_key)
- `inventory_movements_product_variant_id_foreign` (product_variant_id)
- `inventory_movements_user_id_foreign` (user_id)
- `PRIMARY` UNIQUE (id)
- FK `product_variant_id` → `product_variants.id`
- FK `user_id` → `users.id`

## job_batches

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | varchar(255) | NO | NULL | PRI |
| name | varchar(255) | NO | NULL |  |
| total_jobs | int | NO | NULL |  |
| pending_jobs | int | NO | NULL |  |
| failed_jobs | int | NO | NULL |  |
| failed_job_ids | longtext | NO | NULL |  |
| options | mediumtext | YES | NULL |  |
| cancelled_at | int | YES | NULL |  |
| created_at | int | NO | NULL |  |
| finished_at | int | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)

## jobs

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| queue | varchar(255) | NO | NULL | MUL |
| payload | longtext | NO | NULL |  |
| attempts | tinyint unsigned | NO | NULL |  |
| reserved_at | int unsigned | YES | NULL |  |
| available_at | int unsigned | NO | NULL |  |
| created_at | int unsigned | NO | NULL |  |

Indexes:
- `jobs_queue_index` (queue)
- `PRIMARY` UNIQUE (id)

## migrations

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | int unsigned | NO | NULL | PRI |
| migration | varchar(255) | NO | NULL |  |
| batch | int | NO | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)

## notifications

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| type | varchar(255) | NO | NULL |  |
| notifiable_type | varchar(255) | NO | NULL | MUL |
| notifiable_id | bigint unsigned | NO | NULL |  |
| data | text | NO | NULL |  |
| read_at | timestamp | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `notifications_notifiable_type_notifiable_id_index` (notifiable_type, notifiable_id)
- `PRIMARY` UNIQUE (id)

## order_items

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| shop_order_id | bigint unsigned | NO | NULL | MUL |
| product_variant_id | bigint unsigned | NO | NULL | MUL |
| product_name | varchar(255) | NO | NULL |  |
| sku | varchar(255) | NO | NULL |  |
| size | varchar(255) | NO | NULL |  |
| color | varchar(255) | NO | NULL |  |
| unit_price | bigint unsigned | NO | NULL |  |
| quantity | int unsigned | NO | NULL |  |
| total | bigint unsigned | NO | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `order_items_product_variant_id_foreign` (product_variant_id)
- `order_items_shop_order_id_foreign` (shop_order_id)
- `PRIMARY` UNIQUE (id)
- FK `product_variant_id` → `product_variants.id`
- FK `shop_order_id` → `shop_orders.id`

## orders

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| number | char(36) | NO | NULL | UNI |
| user_id | bigint unsigned | NO | NULL | MUL |
| checkout_key | char(36) | NO | NULL | UNI |
| status | varchar(255) | NO | pending | MUL |
| payment_method | varchar(255) | NO | NULL |  |
| shipping_address | json | NO | NULL |  |
| subtotal | bigint unsigned | NO | NULL |  |
| discount | bigint unsigned | NO | 0 |  |
| shipping | bigint unsigned | NO | NULL |  |
| total | bigint unsigned | NO | NULL |  |
| expires_at | timestamp | YES | NULL | MUL |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |
| shipping_method | varchar(255) | NO | standard |  |
| shipping_label | varchar(255) | NO | Giao hàng tiêu chuẩn |  |

Indexes:
- `orders_checkout_key_unique` UNIQUE (checkout_key)
- `orders_expires_at_index` (expires_at)
- `orders_number_unique` UNIQUE (number)
- `orders_status_index` (status)
- `orders_user_id_foreign` (user_id)
- `PRIMARY` UNIQUE (id)
- FK `user_id` → `users.id`

## password_reset_tokens

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| email | varchar(255) | NO | NULL | PRI |
| token | varchar(255) | NO | NULL |  |
| created_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (email)

## payment_callbacks

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| payment_id | bigint unsigned | YES | NULL | MUL |
| fingerprint | char(64) | NO | NULL | UNI |
| transaction_reference | varchar(100) | YES | NULL | MUL |
| provider_reference | varchar(100) | YES | NULL |  |
| response_code | varchar(10) | NO | NULL |  |
| signature_valid | tinyint(1) | NO | NULL |  |
| verdict | varchar(150) | NO | NULL |  |
| attempts | int unsigned | NO | 0 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `payment_callbacks_fingerprint_unique` UNIQUE (fingerprint)
- `payment_callbacks_payment_id_foreign` (payment_id)
- `payment_callbacks_transaction_reference_index` (transaction_reference)
- `PRIMARY` UNIQUE (id)
- FK `payment_id` → `payments.id`

## payment_transactions

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| payment_id | bigint unsigned | NO | NULL | MUL |
| shop_order_id | bigint unsigned | YES | NULL | MUL |
| type | varchar(255) | NO | NULL | MUL |
| amount | bigint unsigned | NO | NULL |  |
| event_key | varchar(255) | NO | NULL | UNI |
| provider_reference | varchar(255) | YES | NULL |  |
| result_code | varchar(255) | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `payment_transactions_event_key_unique` UNIQUE (event_key)
- `payment_transactions_payment_id_foreign` (payment_id)
- `payment_transactions_shop_order_id_foreign` (shop_order_id)
- `payment_transactions_type_created_at_index` (type, created_at)
- `PRIMARY` UNIQUE (id)
- FK `payment_id` → `payments.id`
- FK `shop_order_id` → `shop_orders.id`

## payments

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| order_id | bigint unsigned | NO | NULL | UNI |
| reference | char(36) | NO | NULL | UNI |
| method | varchar(255) | NO | NULL |  |
| status | varchar(255) | NO | pending | MUL |
| amount | bigint unsigned | NO | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `payments_order_id_unique` UNIQUE (order_id)
- `payments_reference_unique` UNIQUE (reference)
- `payments_status_index` (status)
- `PRIMARY` UNIQUE (id)
- FK `order_id` → `orders.id`

## product_images

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| product_id | bigint unsigned | NO | NULL | MUL |
| path | varchar(255) | NO | NULL |  |
| alt | varchar(255) | NO | NULL |  |
| position | int unsigned | NO | 0 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `product_images_product_id_foreign` (product_id)
- FK `product_id` → `products.id`

## product_variants

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| product_id | bigint unsigned | NO | NULL | MUL |
| sku | varchar(255) | NO | NULL | UNI |
| size | varchar(20) | NO | NULL |  |
| color | varchar(50) | NO | NULL |  |
| price | bigint unsigned | NO | NULL |  |
| compare_price | bigint unsigned | YES | NULL |  |
| active | tinyint(1) | NO | 1 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `product_variants_product_id_size_color_unique` UNIQUE (product_id, size, color)
- `product_variants_sku_unique` UNIQUE (sku)
- FK `product_id` → `products.id`

## products

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| shop_id | bigint unsigned | NO | NULL | MUL |
| category_id | bigint unsigned | NO | NULL | MUL |
| name | varchar(255) | NO | NULL | MUL |
| slug | varchar(255) | NO | NULL | UNI |
| description | text | NO | NULL |  |
| status | varchar(255) | NO | pending | MUL |
| active | tinyint(1) | NO | 1 |  |
| deleted_at | timestamp | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |
| collection_id | bigint unsigned | YES | NULL | MUL |

Indexes:
- `PRIMARY` UNIQUE (id)
- `products_category_id_foreign` (category_id)
- `products_collection_id_foreign` (collection_id)
- `products_name_index` (name)
- `products_shop_id_status_active_index` (shop_id, status, active)
- `products_slug_unique` UNIQUE (slug)
- `products_status_index` (status)
- FK `category_id` → `categories.id`
- FK `collection_id` → `collections.id`
- FK `shop_id` → `shops.id`

## return_requests

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| order_item_id | bigint unsigned | NO | NULL | UNI |
| user_id | bigint unsigned | NO | NULL | MUL |
| type | varchar(255) | NO | NULL |  |
| reason | text | NO | NULL |  |
| status | varchar(255) | NO | requested |  |
| response | text | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |
| restocked_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `return_requests_order_item_id_unique` UNIQUE (order_item_id)
- `return_requests_user_id_foreign` (user_id)
- FK `order_item_id` → `order_items.id`
- FK `user_id` → `users.id`

## reviews

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| user_id | bigint unsigned | NO | NULL | MUL |
| product_id | bigint unsigned | NO | NULL | MUL |
| rating | tinyint unsigned | NO | NULL |  |
| comment | text | NO | NULL |  |
| visible | tinyint(1) | NO | 1 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `reviews_product_id_foreign` (product_id)
- `reviews_user_id_product_id_unique` UNIQUE (user_id, product_id)
- FK `product_id` → `products.id`
- FK `user_id` → `users.id`

## sessions

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | varchar(255) | NO | NULL | PRI |
| user_id | bigint unsigned | YES | NULL | MUL |
| ip_address | varchar(45) | YES | NULL |  |
| user_agent | text | YES | NULL |  |
| payload | longtext | NO | NULL |  |
| last_activity | int | NO | NULL | MUL |

Indexes:
- `PRIMARY` UNIQUE (id)
- `sessions_last_activity_index` (last_activity)
- `sessions_user_id_index` (user_id)

## shop_applications

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| user_id | bigint unsigned | NO | NULL | MUL |
| shop_id | bigint unsigned | NO | NULL | MUL |
| description | text | NO | NULL |  |
| status | varchar(255) | NO | pending |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `shop_applications_shop_id_foreign` (shop_id)
- `shop_applications_user_id_foreign` (user_id)
- FK `shop_id` → `shops.id`
- FK `user_id` → `users.id`

## shop_orders

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| order_id | bigint unsigned | NO | NULL | MUL |
| shop_id | bigint unsigned | NO | NULL | MUL |
| status | varchar(255) | NO | pending | MUL |
| inventory_status | varchar(255) | NO | reserved |  |
| subtotal | bigint unsigned | NO | NULL |  |
| discount | bigint unsigned | NO | 0 |  |
| shipping | bigint unsigned | NO | NULL |  |
| total | bigint unsigned | NO | NULL |  |
| tracking_number | varchar(255) | YES | NULL |  |
| delivered_at | timestamp | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `shop_orders_order_id_shop_id_unique` UNIQUE (order_id, shop_id)
- `shop_orders_shop_id_foreign` (shop_id)
- `shop_orders_status_index` (status)
- FK `order_id` → `orders.id`
- FK `shop_id` → `shops.id`

## shops

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| owner_id | bigint unsigned | NO | NULL | UNI |
| name | varchar(255) | NO | NULL |  |
| slug | varchar(255) | NO | NULL | UNI |
| status | varchar(255) | NO | pending | MUL |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `shops_owner_id_unique` UNIQUE (owner_id)
- `shops_slug_unique` UNIQUE (slug)
- `shops_status_index` (status)
- FK `owner_id` → `users.id`

## site_settings

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| key | varchar(255) | NO | NULL | UNI |
| value | json | NO | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `site_settings_key_unique` UNIQUE (key)

## users

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| name | varchar(255) | NO | NULL |  |
| email | varchar(255) | NO | NULL | UNI |
| email_verified_at | timestamp | YES | NULL |  |
| password | varchar(255) | NO | NULL |  |
| remember_token | varchar(100) | YES | NULL |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |
| role | varchar(255) | NO | customer | MUL |

Indexes:
- `PRIMARY` UNIQUE (id)
- `users_email_unique` UNIQUE (email)
- `users_role_index` (role)

## voucher_usages

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| voucher_id | bigint unsigned | NO | NULL | MUL |
| order_id | bigint unsigned | NO | NULL | UNI |
| user_id | bigint unsigned | NO | NULL | MUL |
| status | varchar(255) | NO | reserved |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `voucher_usages_order_id_unique` UNIQUE (order_id)
- `voucher_usages_user_id_foreign` (user_id)
- `voucher_usages_voucher_id_foreign` (voucher_id)
- FK `order_id` → `orders.id`
- FK `user_id` → `users.id`
- FK `voucher_id` → `vouchers.id`

## vouchers

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| code | varchar(255) | NO | NULL | UNI |
| shop_id | bigint unsigned | YES | NULL | MUL |
| type | varchar(255) | NO | NULL |  |
| value | bigint unsigned | NO | NULL |  |
| max_discount | bigint unsigned | YES | NULL |  |
| min_order | bigint unsigned | NO | 0 |  |
| usage_limit | int unsigned | NO | NULL |  |
| used | int unsigned | NO | 0 |  |
| starts_at | timestamp | NO | NULL |  |
| ends_at | timestamp | NO | NULL |  |
| active | tinyint(1) | NO | 1 |  |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `vouchers_code_unique` UNIQUE (code)
- `vouchers_shop_id_foreign` (shop_id)
- FK `shop_id` → `shops.id`

## wishlists

| Cột | Kiểu | Null | Default | Key |
|---|---|---|---|---|
| id | bigint unsigned | NO | NULL | PRI |
| user_id | bigint unsigned | NO | NULL | MUL |
| product_id | bigint unsigned | NO | NULL | MUL |
| created_at | timestamp | YES | NULL |  |
| updated_at | timestamp | YES | NULL |  |

Indexes:
- `PRIMARY` UNIQUE (id)
- `wishlists_product_id_foreign` (product_id)
- `wishlists_user_id_product_id_unique` UNIQUE (user_id, product_id)
- FK `product_id` → `products.id`
- FK `user_id` → `users.id`
