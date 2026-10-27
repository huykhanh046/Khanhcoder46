# Routes thực tế

Sinh bằng `php artisan route:list --json`. Middleware web gồm session, CSRF; IPN GET dùng chữ ký. Các route framework storage/up được liệt kê để đối chiếu.

| Method | URI | Name | Handler | Middleware |
|---|---|---|---|---|
| GET / HEAD | / | home | App\Http\Controllers\CatalogController@home | web |
| GET / HEAD | account | account | App\Http\Controllers\AccountController@index | web, auth, auth.session |
| PATCH | account | — | App\Http\Controllers\AccountController@update | web, auth, auth.session |
| PUT | account/password | — | App\Http\Controllers\AccountController@password | web, auth, auth.session, throttle:6,1 |
| POST | addresses | — | App\Http\Controllers\AccountController@address | web, auth, auth.session |
| PATCH | addresses/{address} | — | App\Http\Controllers\AccountController@updateAddress | web, auth, auth.session |
| DELETE | addresses/{address} | — | App\Http\Controllers\AccountController@deleteAddress | web, auth, auth.session |
| GET / HEAD | admin | — | App\Http\Controllers\AdminController@index | web, auth, auth.session, can:admin |
| POST | admin/banners | — | App\Http\Controllers\AdminController@banner | web, auth, auth.session, can:admin |
| PATCH | admin/banners/{banner} | — | App\Http\Controllers\AdminController@updateBanner | web, auth, auth.session, can:admin |
| DELETE | admin/banners/{banner} | — | App\Http\Controllers\AdminController@deleteBanner | web, auth, auth.session, can:admin |
| POST | admin/categories | — | App\Http\Controllers\AdminController@category | web, auth, auth.session, can:admin |
| PATCH | admin/categories/{category} | — | App\Http\Controllers\AdminController@updateCategory | web, auth, auth.session, can:admin |
| DELETE | admin/categories/{category} | — | App\Http\Controllers\AdminController@deleteCategory | web, auth, auth.session, can:admin |
| GET / HEAD | admin/collections | admin.collections.index | App\Http\Controllers\CollectionController@index | web, auth, auth.session, can:admin |
| POST | admin/collections | admin.collections.store | App\Http\Controllers\CollectionController@store | web, auth, auth.session, can:admin |
| GET / HEAD | admin/collections/create | admin.collections.create | App\Http\Controllers\CollectionController@create | web, auth, auth.session, can:admin |
| PUT / PATCH | admin/collections/{collection} | admin.collections.update | App\Http\Controllers\CollectionController@update | web, auth, auth.session, can:admin |
| DELETE | admin/collections/{collection} | admin.collections.destroy | App\Http\Controllers\CollectionController@destroy | web, auth, auth.session, can:admin |
| GET / HEAD | admin/collections/{collection}/edit | admin.collections.edit | App\Http\Controllers\CollectionController@edit | web, auth, auth.session, can:admin |
| POST | admin/orders/{order}/cancel | — | App\Http\Controllers\FinanceController@cancel | web, auth, auth.session, can:admin |
| GET / HEAD | admin/payments | — | App\Http\Controllers\FinanceController@index | web, auth, auth.session, can:admin |
| PATCH | admin/products/{product}/moderate | — | App\Http\Controllers\AdminController@moderate | web, auth, auth.session, can:admin |
| PATCH | admin/reviews/{review} | — | App\Http\Controllers\AdminController@review | web, auth, auth.session, can:admin |
| PUT | admin/settings | — | App\Http\Controllers\AdminController@settings | web, auth, auth.session, can:admin |
| GET / HEAD | admin/shipping | — | App\Http\Controllers\FinanceController@shipping | web, auth, auth.session, can:admin |
| PUT | admin/shipping | — | App\Http\Controllers\FinanceController@updateShipping | web, auth, auth.session, can:admin |
| PATCH | admin/shops/{shop} | — | App\Http\Controllers\AdminController@shop | web, auth, auth.session, can:admin |
| PATCH | admin/users/{user} | — | App\Http\Controllers\AdminController@user | web, auth, auth.session, can:admin |
| GET / HEAD | admin/vouchers | admin.vouchers.index | App\Http\Controllers\VoucherAdminController@index | web, auth, auth.session, can:admin |
| POST | admin/vouchers | admin.vouchers.store | App\Http\Controllers\VoucherAdminController@store | web, auth, auth.session, can:admin |
| GET / HEAD | admin/vouchers/create | admin.vouchers.create | App\Http\Controllers\VoucherAdminController@create | web, auth, auth.session, can:admin |
| PUT / PATCH | admin/vouchers/{voucher} | admin.vouchers.update | App\Http\Controllers\VoucherAdminController@update | web, auth, auth.session, can:admin |
| DELETE | admin/vouchers/{voucher} | admin.vouchers.destroy | App\Http\Controllers\VoucherAdminController@destroy | web, auth, auth.session, can:admin |
| GET / HEAD | admin/vouchers/{voucher}/edit | admin.vouchers.edit | App\Http\Controllers\VoucherAdminController@edit | web, auth, auth.session, can:admin |
| PATCH | admin/vouchers/{voucher}/toggle | — | App\Http\Controllers\AdminController@toggleVoucher | web, auth, auth.session, can:admin |
| GET / HEAD | cart | cart | App\Http\Controllers\CartController@index | web |
| POST | cart/items | — | App\Http\Controllers\CartController@store | web, throttle:60,1 |
| PATCH | cart/items/{item} | — | App\Http\Controllers\CartController@update | web |
| DELETE | cart/items/{item} | — | App\Http\Controllers\CartController@destroy | web |
| GET / HEAD | checkout | — | App\Http\Controllers\CheckoutController@index | web, auth, auth.session |
| POST | checkout | — | App\Http\Controllers\CheckoutController@store | web, auth, auth.session, throttle:10,1 |
| GET / HEAD | collections | collections | App\Http\Controllers\CollectionController@publicIndex | web |
| GET / HEAD | forgot-password | password.request | Closure | web, guest |
| POST | forgot-password | password.email | App\Http\Controllers\AuthController@forgot | web, guest, throttle:3,1 |
| GET / HEAD | login | login | Closure | web, guest |
| POST | login | — | App\Http\Controllers\AuthController@login | web, guest, throttle:6,1 |
| POST | logout | logout | App\Http\Controllers\AuthController@logout | web, auth, auth.session |
| GET / HEAD | manage | manage.dashboard | App\Http\Controllers\ManageController@dashboard | web, auth, auth.session, can:manage |
| DELETE | manage/images/{image} | manage. | App\Http\Controllers\ManageController@deleteImage | web, auth, auth.session, can:manage |
| GET / HEAD | manage/inventory | manage. | App\Http\Controllers\ManageController@movements | web, auth, auth.session, can:manage |
| GET / HEAD | manage/orders | manage. | App\Http\Controllers\ManageController@orders | web, auth, auth.session, can:manage |
| GET / HEAD | manage/orders/{shopOrder} | manage. | App\Http\Controllers\ManageController@showOrder | web, auth, auth.session, can:manage |
| POST | manage/orders/{shopOrder}/money | manage. | App\Http\Controllers\ManageController@money | web, auth, auth.session, can:manage, can:admin |
| POST | manage/orders/{shopOrder}/transition | manage. | App\Http\Controllers\ManageController@transition | web, auth, auth.session, can:manage |
| POST | manage/products | manage.products.store | App\Http\Controllers\ManageController@store | web, auth, auth.session, can:manage |
| GET / HEAD | manage/products | manage.products.index | App\Http\Controllers\ManageController@products | web, auth, auth.session, can:manage |
| GET / HEAD | manage/products/create | manage.products.create | App\Http\Controllers\ManageController@create | web, auth, auth.session, can:manage |
| PUT / PATCH | manage/products/{product} | manage.products.update | App\Http\Controllers\ManageController@update | web, auth, auth.session, can:manage |
| DELETE | manage/products/{product} | manage.products.destroy | App\Http\Controllers\ManageController@destroy | web, auth, auth.session, can:manage |
| GET / HEAD | manage/products/{product}/edit | manage.products.edit | App\Http\Controllers\ManageController@edit | web, auth, auth.session, can:manage |
| POST | manage/products/{product}/images | manage. | App\Http\Controllers\ManageController@images | web, auth, auth.session, can:manage |
| POST | manage/products/{product}/variants | manage. | App\Http\Controllers\ManageController@variant | web, auth, auth.session, can:manage |
| GET / HEAD | manage/returns | manage. | App\Http\Controllers\ManageController@returns | web, auth, auth.session, can:manage |
| PATCH | manage/returns/{returnRequest} | manage. | App\Http\Controllers\ManageController@updateReturn | web, auth, auth.session, can:manage |
| POST | manage/returns/{returnRequest}/restock | manage. | App\Http\Controllers\ManageController@restockReturn | web, auth, auth.session, can:manage |
| PATCH | manage/variants/{variant} | manage. | App\Http\Controllers\ManageController@updateVariant | web, auth, auth.session, can:manage |
| POST | manage/variants/{variant}/inventory | manage. | App\Http\Controllers\ManageController@inventory | web, auth, auth.session, can:manage |
| POST | order-items/{item}/returns | — | App\Http\Controllers\AccountController@returnItem | web, auth, auth.session |
| GET / HEAD | orders | orders.index | App\Http\Controllers\OrderController@index | web, auth, auth.session |
| GET / HEAD | orders/{order} | orders.show | App\Http\Controllers\OrderController@show | web, auth, auth.session |
| POST | orders/{order}/cancel | — | App\Http\Controllers\OrderController@cancel | web, auth, auth.session |
| POST | orders/{order}/pay | — | App\Http\Controllers\OrderController@pay | web, auth, auth.session |
| GET / HEAD | payments/ipn | payments.ipn | App\Http\Controllers\OrderController@ipn | web, throttle:120,1 |
| GET / HEAD | payments/return | payments.return | App\Http\Controllers\OrderController@result | web, auth, auth.session |
| GET / HEAD | products | products.index | App\Http\Controllers\CatalogController@index | web |
| GET / HEAD | products/{product} | products.show | App\Http\Controllers\CatalogController@show | web |
| POST | products/{product}/reviews | — | App\Http\Controllers\AccountController@review | web, auth, auth.session, throttle:10,1 |
| GET / HEAD | register | register | Closure | web, guest |
| POST | register | — | App\Http\Controllers\AuthController@register | web, guest, throttle:6,1 |
| POST | reset-password | password.update | App\Http\Controllers\AuthController@reset | web, guest, throttle:6,1 |
| GET / HEAD | reset-password/{token} | password.reset | Closure | web, guest |
| POST | shop-applications | — | App\Http\Controllers\AccountController@applyShop | web, auth, auth.session |
| GET / HEAD | storage/{path} | storage.local | Closure |  |
| PUT | storage/{path} | storage.local.upload | Closure |  |
| GET / HEAD | up | — | Closure |  |
| GET / HEAD | wishlist | — | App\Http\Controllers\AccountController@wishlist | web, auth, auth.session |
| POST | wishlist/{product} | — | App\Http\Controllers\AccountController@toggleWishlist | web, auth, auth.session |
