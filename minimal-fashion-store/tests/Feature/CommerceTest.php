<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\ReportService;
use App\Services\VnpayService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommerceTest extends TestCase
{
    use RefreshDatabase;

    private function variant(int $stock = 10, ?Shop $shop = null, int $price = 200000): ProductVariant
    {
        $product = Product::factory()->create($shop ? ['shop_id' => $shop->id] : []);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => $price]);
        $variant->inventory()->create(['stock' => $stock]);

        return $variant;
    }

    private function cart(User $user, array $variants): Cart
    {
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        foreach ($variants as $v) {
            app(CartService::class)->add($cart, $v->id, 1);
        }

        return $cart;
    }

    private function place(Cart $cart, string $method = 'cod', ?string $voucher = null, ?string $key = null): Order
    {
        return app(CheckoutService::class)->place($cart, $cart->user_id, ['name' => 'Khách thử', 'phone' => '0901234567', 'address' => '12 Đường Mẫu', 'city' => 'Hà Nội'], $method, $voucher, $key ?? (string) Str::uuid());
    }

    private function online(): Order
    {
        config(['store.vnpay.tmn_code' => 'TEST', 'store.vnpay.hash_secret' => 'test-secret']);

        return $this->place($this->cart(User::factory()->create(), [$this->variant()]), 'vnpay');
    }

    private function signedPayload(Order $order, array $overrides = []): array
    {
        $data = array_merge(['vnp_TmnCode' => 'TEST', 'vnp_TxnRef' => $order->payment->reference, 'vnp_Amount' => (string) ($order->total * 100), 'vnp_CurrCode' => 'VND', 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00', 'vnp_TransactionNo' => '123456'], $overrides);
        $data['vnp_SecureHash'] = app(VnpayService::class)->signature($data);

        return $data;
    }

    public function test_registration_login_and_role_cannot_be_injected(): void
    {
        $this->post('/register', ['name' => 'Khách', 'email' => 'a@example.test', 'password' => 'Password12345', 'password_confirmation' => 'Password12345', 'role' => 'admin'])->assertRedirect('/account');
        $user = User::where('email', 'a@example.test')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check('Password12345', $user->password));
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $user->email, 'password' => 'Password12345'])->assertRedirect('/account');
        $this->assertAuthenticatedAs($user);
    }

    public function test_guest_pages_render_and_guest_cart_persists_in_mysql(): void
    {
        $v = $this->variant();
        $this->get('/')->assertOk();
        $this->get('/products')->assertOk();
        $this->get('/products/'.$v->product_id)->assertOk();
        $this->post('/cart/items', ['product_variant_id' => $v->id, 'quantity' => 2])->assertRedirect('/cart');
        $this->get('/cart')->assertOk()->assertSee('400,000');
        $this->assertDatabaseHas('cart_items', ['product_variant_id' => $v->id, 'quantity' => 2]);
    }

    public function test_cart_merge_and_ownership(): void
    {
        $v = $this->variant();
        $u = User::factory()->create(['password' => 'Password12345']);
        $guest = Cart::create(['token' => (string) Str::uuid()]);
        app(CartService::class)->add($guest, $v->id, 2);
        $own = $this->cart($u, [$v]);
        $this->withSession(['cart_id' => $guest->id])->post('/login', ['email' => $u->email, 'password' => 'Password12345'])->assertRedirect();
        $this->assertSame(3, $own->items()->first()->quantity);
        $other = $this->cart(User::factory()->create(), [$v]);
        $this->patch('/cart/items/'.$other->items()->first()->id, ['quantity' => 2])->assertForbidden();
    }

    public function test_cart_stock_and_current_prices_are_revalidated(): void
    {
        $u = User::factory()->create();
        $v = $this->variant(1);
        $cart = $this->cart($u, [$v]);
        $v->update(['price' => 250000]);
        $order = $this->place($cart);
        $this->assertSame(280000, $order->total);
        $this->assertSame(250000, $order->shopOrders->first()->items->first()->unit_price);
        $second = Cart::factory()->create();
        $second->items()->create(['product_variant_id' => $v->id, 'quantity' => 1]);
        try {
            $this->place($second);
            $this->fail('Expected no stock');
        } catch (ValidationException $e) {
            $this->assertSame(1, Order::count());
        }$this->assertSame(1, $v->inventory->fresh()->reserved);
    }

    public function test_cod_split_order_snapshot_and_idempotency(): void
    {
        $u = User::factory()->create();
        $a = $this->variant();
        $b = $this->variant();
        $cart = $this->cart($u, [$a, $b]);
        $key = (string) Str::uuid();
        $order = $this->place($cart, 'cod', null, $key);
        $this->assertCount(2, $order->shopOrders);
        $this->assertSame(460000, $order->total);
        $this->assertSame('pending', $order->payment->status);
        $this->assertSame(0, PaymentTransaction::count());
        $this->assertSame($order->id, $this->place($cart, 'cod', null, $key)->id);
        $name = $a->product->name;
        $a->product->update(['name' => 'Đã thay đổi']);
        $this->assertSame($name, $order->shopOrders->first()->items->first()->product_name);
    }

    public function test_voucher_allocation_scope_expiry_and_usage_limit(): void
    {
        $a = $this->variant();
        $b = $this->variant();
        $v = Voucher::factory()->create(['code' => 'SHOP', 'shop_id' => $a->product->shop_id, 'type' => 'fixed', 'value' => 50000, 'usage_limit' => 1]);
        $order = $this->place($this->cart(User::factory()->create(), [$a, $b]), 'cod', 'SHOP');
        $this->assertSame(50000, $order->discount);
        $this->assertSame(50000, $order->shopOrders()->where('shop_id', $a->product->shop_id)->first()->discount);
        $this->assertSame(0, $order->shopOrders()->where('shop_id', $b->product->shop_id)->first()->discount);
        try {
            app(VoucherService::class)->calculate('SHOP', [$a->product->shop_id => 200000]);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertSame(1, $v->fresh()->used);
        }
        app(OrderService::class)->cancelCustomer($order);
        $this->assertSame(0, $v->fresh()->used);
        $v->update(['ends_at' => now()->subSecond()]);
        $this->expectException(ValidationException::class);
        app(VoucherService::class)->calculate('SHOP', [$a->product->shop_id => 200000]);
    }

    public function test_percent_voucher_uses_integer_rounding_and_maximum(): void
    {
        $v = Voucher::factory()->create(['code' => 'TEN', 'value' => 1000, 'max_discount' => 10000]);
        $result = app(VoucherService::class)->calculate('TEN', [1 => 100001, 2 => 99999]);
        $this->assertSame(10000, $result['discount']);
        $this->assertSame(10000, array_sum($result['allocations']));
        $this->expectException(ValidationException::class);
        app(VoucherService::class)->calculate('MISSING', [1 => 1]);
    }

    public function test_payment_signature_amount_duplicate_and_browser_return(): void
    {
        $order = $this->online();
        $service = app(VnpayService::class);
        $data = $this->signedPayload($order);
        $bad = $data;
        $bad['vnp_SecureHash'] = 'bad';
        $this->assertSame('97', $service->ipn($bad)['RspCode']);
        $this->assertSame('04', $service->ipn($this->signedPayload($order, ['vnp_Amount' => '1']))['RspCode']);
        $this->actingAs($order->user)->get('/payments/return?'.http_build_query($data))->assertOk();
        $this->assertSame('pending', $order->payment->fresh()->status);
        $this->assertSame('00', $service->ipn($data)['RspCode']);
        $this->assertSame('02', $service->ipn($data)['RspCode']);
        $this->assertSame('paid', $order->payment->fresh()->status);
        $this->assertSame(1, PaymentTransaction::where('type', 'capture')->count());
    }

    public function test_failed_payment_and_expiry_release_only_once(): void
    {
        $order = $this->online();
        $item = $order->shopOrders->first()->items->first();
        $service = app(VnpayService::class);
        $service->ipn($this->signedPayload($order, ['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']));
        $service->ipn($this->signedPayload($order, ['vnp_ResponseCode' => '24', 'vnp_TransactionStatus' => '02']));
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, Inventory::where('product_variant_id', $item->product_variant_id)->first()->reserved);
        $expired = $this->online();
        $expired->update(['expires_at' => now()->subMinute()]);
        app(OrderService::class)->expire($expired);
        app(OrderService::class)->expire($expired);
        $this->assertSame('expired', $expired->payment->fresh()->status);
        $this->assertSame('02', $service->ipn($this->signedPayload($expired))['RspCode']);
        $this->assertSame(0, PaymentTransaction::where('type', 'capture')->count());
    }

    public function test_cancellation_and_state_machine_and_revenue(): void
    {
        $u = User::factory()->create();
        $v = $this->variant();
        $order = $this->place($this->cart($u, [$v]));
        $sub = $order->shopOrders->first();
        $service = app(OrderService::class);
        try {
            $service->transition($sub, 'delivered');
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertSame('pending', $sub->fresh()->status);
        }
        foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $s) {
            $service->transition($sub, $s);
        }$this->assertSame(9, $v->inventory->fresh()->stock);
        $this->assertSame(0, $v->inventory->fresh()->reserved);
        $range = [null, now()->toDateString(), now()->toDateString()];
        $this->assertSame(0, app(ReportService::class)->data(...$range)['revenue']);
        $service->recordMoney($sub, 'capture', 'COD-1', $sub->total);
        $this->assertSame(230000, app(ReportService::class)->data(...$range)['revenue']);
        $service->recordMoney($sub, 'refund', 'REF-1', 30000);
        $this->assertSame(200000, app(ReportService::class)->data(...$range)['revenue']);
    }

    public function test_seller_cannot_access_other_shops_and_customer_cannot_manage(): void
    {
        $v = $this->variant();
        $seller = $v->product->shop->owner;
        $seller->forceFill(['role' => 'seller'])->save();
        $other = $this->variant();
        $order = $this->place($this->cart(User::factory()->create(), [$other]));
        $this->actingAs($seller)->get('/manage/products/'.$v->product_id.'/edit')->assertOk();
        $this->get('/manage/products/'.$other->product_id.'/edit')->assertForbidden();
        $this->get('/manage/orders/'.$order->shopOrders->first()->id)->assertForbidden();
        $this->get('/admin')->assertForbidden();
        $this->actingAs(User::factory()->create())->get('/manage')->assertForbidden();
    }

    public function test_product_crud_variant_validation_and_soft_delete(): void
    {
        $v = $this->variant();
        $seller = $v->product->shop->owner;
        $seller->forceFill(['role' => 'seller'])->save();
        $this->actingAs($seller);
        $this->post('/manage/products', ['name' => 'Áo mới', 'category_id' => $v->product->category_id, 'description' => 'Mô tả', 'active' => 1, 'shop_id' => 999])->assertRedirect();
        $product = Product::latest('id')->first();
        $this->assertSame($v->product->shop_id, $product->shop_id);
        $this->assertSame('pending', $product->status);
        $this->post('/manage/products/'.$product->id.'/variants', ['sku' => 'SKU-NEW', 'size' => 'S', 'color' => 'Đen', 'price' => 100000, 'stock' => 3])->assertRedirect();
        $this->assertDatabaseHas('product_variants', ['sku' => 'SKU-NEW']);
        $this->post('/manage/products/'.$product->id.'/variants', ['sku' => 'SKU-OTHER', 'size' => 'S', 'color' => 'Đen', 'price' => 100000, 'stock' => 3])->assertSessionHasErrors('size');
        $this->patch('/manage/products/'.$product->id, ['name' => 'Áo sửa', 'category_id' => $v->product->category_id, 'description' => 'Mô tả sửa', 'active' => 1])->assertRedirect();
        $this->delete('/manage/products/'.$product->id)->assertRedirect('/manage/products');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_review_requires_delivery_and_return_ownership(): void
    {
        $u = User::factory()->create();
        $v = $this->variant();
        $order = $this->place($this->cart($u, [$v]));
        $sub = $order->shopOrders->first();
        $this->actingAs($u)->post('/products/'.$v->product_id.'/reviews', ['rating' => 5, 'comment' => 'Tốt'])->assertForbidden();
        foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $s) {
            app(OrderService::class)->transition($sub, $s);
        }
        $this->post('/products/'.$v->product_id.'/reviews', ['rating' => 5, 'comment' => 'Tốt'])->assertRedirect();
        $this->assertSame(1, Review::count());
        $item = $sub->items->first();
        $this->post('/order-items/'.$item->id.'/returns', ['type' => 'return', 'reason' => 'Không vừa'])->assertRedirect();
        $this->assertDatabaseHas('return_requests', ['order_item_id' => $item->id, 'status' => 'requested']);
        $this->actingAs(User::factory()->create())->post('/order-items/'.$item->id.'/returns', ['type' => 'return', 'reason' => 'Không vừa'])->assertForbidden();
    }

    public function test_unconfigured_vnpay_is_not_a_fake_success(): void
    {
        config(['store.vnpay.tmn_code' => null, 'store.vnpay.hash_secret' => null]);
        $cart = $this->cart(User::factory()->create(), [$this->variant()]);
        $this->expectException(ValidationException::class);
        $this->place($cart, 'vnpay');
    }

    public function test_admin_pages_and_settings_persist(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);
        $this->get('/manage')->assertOk();
        $this->get('/admin')->assertOk();
        $this->get('/manage/orders')->assertOk();
        $this->get('/manage/inventory')->assertOk();
        $this->get('/manage/returns')->assertOk();
        $this->put('/admin/settings', ['name' => 'Cửa hàng của tôi', 'email' => 'shop@example.test', 'phone' => '0901234567', 'address' => 'Hà Nội', 'slogan' => 'Mặc đẹp', 'primary' => '#111111', 'secondary' => '#eeeeee', 'font' => 'Arial', 'font_size' => 16, 'spacing' => 24, 'radius' => 0, 'sections' => ['new'], 'menu' => [['label' => 'Sản phẩm', 'url' => '/products']]])->assertRedirect();
        $this->assertDatabaseHas('site_settings', ['key' => 'brand']);
    }

    public function test_password_reset_requires_valid_token_and_changes_hash(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->post('/reset-password', ['email' => $user->email, 'token' => 'bad', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertSessionHasErrors('email');
        $this->post('/reset-password', ['email' => $user->email, 'token' => $token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
    }

    public function test_http_checkout_ignores_client_supplied_prices(): void
    {
        $user = User::factory()->create();
        $v = $this->variant();
        $this->cart($user, [$v]);
        $this->actingAs($user)->post('/checkout', ['name' => 'Người mua', 'phone' => '0901234567', 'address' => '12 Đường Mẫu', 'city' => 'Hà Nội', 'payment_method' => 'cod', 'checkout_key' => (string) Str::uuid(), 'total' => 1, 'price' => 1])->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame(230000, $order->total);
        $this->get('/orders/'.$order->id)->assertOk();
    }

    public function test_multiple_variants_keep_separate_stock_and_catalog_filters(): void
    {
        $v = $this->variant(0);
        $second = ProductVariant::factory()->create(['product_id' => $v->product_id, 'size' => 'L', 'color' => 'Trắng', 'price' => 300000]);
        $second->inventory()->create(['stock' => 3]);
        $user = User::factory()->create();
        $cart = $this->cart($user, [$second]);
        $order = $this->place($cart);
        $this->assertSame(0, $v->inventory->fresh()->reserved);
        $this->assertSame(1, $second->inventory->fresh()->reserved);
        $this->get('/products?size=L&color=Tr%E1%BA%AFng&min=250000&sort=price_desc')->assertOk()->assertSee($v->product->name);
        $this->get('/products?size=XXXL')->assertOk()->assertSee('Chưa có sản phẩm phù hợp.');
    }

    public function test_manual_money_replay_is_idempotent_and_over_refund_fails(): void
    {
        $order = $this->place($this->cart(User::factory()->create(), [$this->variant()]));
        $sub = $order->shopOrders->first();
        $service = app(OrderService::class);
        foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $state) {
            $service->transition($sub, $state);
        }
        $service->recordMoney($sub, 'capture', 'REPLAY', $sub->total);
        $service->recordMoney($sub, 'capture', 'REPLAY', $sub->total);
        $this->assertSame(1, PaymentTransaction::count());
        $service->recordMoney($sub, 'refund', 'REFUND', 100000);
        $service->recordMoney($sub, 'refund', 'REFUND', 100000);
        $this->assertSame(2, PaymentTransaction::count());
        $this->expectException(ValidationException::class);
        $service->recordMoney($sub, 'refund', 'TOO-MUCH', 200000);
    }

    public function test_partial_shop_refund_does_not_block_other_paid_shop(): void
    {
        config(['store.vnpay.tmn_code' => 'TEST', 'store.vnpay.hash_secret' => 'test-secret']);
        $order = $this->place($this->cart(User::factory()->create(), [$this->variant(), $this->variant()]), 'vnpay');
        app(VnpayService::class)->ipn($this->signedPayload($order));
        [$a,$b] = $order->shopOrders->all();
        $service = app(OrderService::class);
        $service->transition($a, 'cancelled');
        $service->recordMoney($a, 'refund', 'PARTIAL', $a->total);
        $service->transition($b, 'confirmed');
        $this->assertSame('confirmed', $b->fresh()->status);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('partially_refunded', $order->payment->fresh()->status);
    }

    public function test_inventory_cannot_be_adjusted_below_reserved(): void
    {
        $v = $this->variant(2);
        $this->place($this->cart(User::factory()->create(), [$v]));
        $this->expectException(ValidationException::class);
        app(InventoryService::class)->adjust($v->id, -2, 'Kiểm kho');
    }

    public function test_upload_rejects_svg_and_seller_ownership(): void
    {
        Storage::fake('public');
        $v = $this->variant();
        $seller = $v->product->shop->owner;
        $seller->forceFill(['role' => 'seller'])->save();
        $this->actingAs($seller)->post('/manage/products/'.$v->product_id.'/images', ['images' => [UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')]])->assertSessionHasErrors('images.0');
        $other = $this->variant();
        $this->post('/manage/variants/'.$other->id.'/inventory', ['delta' => 10, 'reason' => 'x'])->assertForbidden();
    }

    public function test_return_window_and_status_transitions_are_enforced(): void
    {
        $u = User::factory()->create();
        $order = $this->place($this->cart($u, [$this->variant()]));
        $sub = $order->shopOrders->first();
        foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $s) {
            app(OrderService::class)->transition($sub, $s);
        }$sub->refresh()->update(['delivered_at' => now()->subDays(15)]);
        $this->actingAs($u)->post('/order-items/'.$sub->items->first()->id.'/returns', ['type' => 'return', 'reason' => 'Không vừa'])->assertStatus(422);
    }
}
