<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Collection;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Notifications\ResetPasswordQueued;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ReturnService;
use App\Services\VnpayService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompletionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['role' => 'admin'])->save();

        return $u;
    }

    private function variant(): ProductVariant
    {
        $v = ProductVariant::factory()->create();
        $v->inventory()->create(['stock' => 5]);

        return $v;
    }

    private function order(string $method = 'cod', bool $multi = false, string $shipping = 'standard'): Order
    {
        config(['store.vnpay.tmn_code' => 'TEST', 'store.vnpay.hash_secret' => 'secret']);
        $cart = Cart::factory()->create();
        $variants = [$this->variant()];
        if ($multi) {
            $variants[] = $this->variant();
        }
        foreach ($variants as $v) {
            app(CartService::class)->add($cart, $v->id, 1);
        }

        return app(CheckoutService::class)->place($cart, $cart->user_id, ['name' => 'Khách', 'phone' => '0901234567', 'address' => '12 Đường Mẫu', 'city' => 'Hà Nội'], $method, null, (string) Str::uuid(), $shipping);
    }

    private function payload(Order $order, array $override = []): array
    {
        $d = array_merge(['vnp_TmnCode' => 'TEST', 'vnp_TxnRef' => $order->payment->reference, 'vnp_Amount' => (string) ($order->total * 100), 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00', 'vnp_TransactionNo' => '123456'], $override);
        $d['vnp_SecureHash'] = app(VnpayService::class)->signature($d);

        return $d;
    }

    private function deliver(Order $order): void
    {
        foreach ($order->shopOrders as $s) {
            foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $state) {
                app(OrderService::class)->transition($s, $state);
            }
        }
    }

    public function test_login_skips_a_guest_item_that_was_hidden_after_being_added(): void
    {
        $v = $this->variant();
        $guest = Cart::create(['token' => (string) Str::uuid()]);
        app(CartService::class)->add($guest, $v->id, 1);
        $v->product->update(['active' => false]);
        $u = User::factory()->create(['password' => 'Password12345']);
        $this->withSession(['cart_id' => $guest->id])->post('/login', ['email' => $u->email, 'password' => 'Password12345'])->assertRedirect('/account')->assertSessionHas('notice');
        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseMissing('carts', ['id' => $guest->id]);
    }

    public function test_express_shipping_is_server_priced_and_snapshotted_per_shop(): void
    {
        SiteSetting::create(['key' => 'shipping', 'value' => ['standard_fee' => 31000, 'express_fee' => 57000]]);
        $order = $this->order('cod', true, 'express');
        $this->assertSame(114000, $order->shipping);
        $this->assertSame('express', $order->shipping_method);
        $this->assertSame(57000, $order->shopOrders->first()->shipping);
        SiteSetting::where('key', 'shipping')->update(['value' => json_encode(['standard_fee' => 1, 'express_fee' => 2])]);
        $this->assertSame(114000, $order->fresh()->shipping);
        $this->actingAs($order->user)->get('/orders/'.$order->id)->assertOk()->assertSee('Giao hàng nhanh');
    }

    public function test_fake_shipping_method_cannot_create_an_order(): void
    {
        $this->expectException(ValidationException::class);
        $this->order('cod', false, 'free-hacked');
    }

    public function test_shipping_settings_are_admin_only_and_require_positive_fees(): void
    {
        $this->actingAs(User::factory()->create())->put('/admin/shipping', ['standard_fee' => 1, 'express_fee' => 1])->assertForbidden();
        $this->actingAs($this->admin())->put('/admin/shipping', ['standard_fee' => -1, 'express_fee' => 50000])->assertSessionHasErrors('standard_fee');
        $this->put('/admin/shipping', ['standard_fee' => 35000, 'express_fee' => 65000])->assertRedirect();
        $this->get('/admin/shipping')->assertOk();
        $this->assertSame(35000, SiteSetting::where('key', 'shipping')->first()->value['standard_fee']);
    }

    public function test_collection_crud_filters_and_delete_preserves_products(): void
    {
        $this->actingAs($this->admin())->post('/admin/collections', ['name' => 'Mùa thu', 'slug' => 'mua-thu', 'description' => 'Thiết kế mùa thu', 'position' => 1, 'active' => 1])->assertRedirect('/admin/collections');
        $c = Collection::firstOrFail();
        $p = Product::factory()->create(['name' => 'Áo mùa thu', 'collection_id' => $c->id]);
        $this->get('/collections')->assertOk()->assertSee('Mùa thu');
        $this->get('/products?collection=mua-thu')->assertOk()->assertSee('Áo mùa thu');
        $this->get('/admin/collections/'.$c->id.'/edit')->assertOk();
        $this->patch('/admin/collections/'.$c->id, ['name' => 'Thu mới', 'slug' => 'thu-moi', 'description' => 'Mới', 'position' => 0, 'active' => 1])->assertRedirect();
        $this->assertSame('Thu mới', $c->fresh()->name);
        $this->delete('/admin/collections/'.$c->id)->assertRedirect();
        $this->assertNull($p->fresh()->collection_id);
        $this->assertDatabaseHas('products', ['id' => $p->id]);
    }

    public function test_voucher_edit_cannot_reduce_limit_below_used_or_change_existing_order_discount(): void
    {
        $voucher = Voucher::factory()->create(['code' => 'EDIT', 'type' => 'fixed', 'value' => 10000]);
        $cart = Cart::factory()->create();
        $v = $this->variant();
        app(CartService::class)->add($cart, $v->id, 1);
        $order = app(CheckoutService::class)->place($cart, $cart->user_id, ['name' => 'Khách', 'phone' => '0901234567', 'address' => '12 Đường', 'city' => 'Hà Nội'], 'cod', 'EDIT', (string) Str::uuid());
        $data = ['code' => 'EDIT', 'type' => 'fixed', 'value' => 50000, 'min_order' => 0, 'usage_limit' => 10, 'starts_at' => now()->subDay()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString()];
        $this->actingAs($this->admin());
        $this->get('/admin/vouchers')->assertOk();
        $this->get('/admin/vouchers/'.$voucher->id.'/edit')->assertOk();
        $this->patch('/admin/vouchers/'.$voucher->id, $data)->assertRedirect('/admin/vouchers');
        $this->assertSame(10000, $order->fresh()->discount);
        $this->assertSame(50000, $voucher->fresh()->value);
        $voucher->update(['used' => 5]);
        $this->patch('/admin/vouchers/'.$voucher->id, array_merge($data, ['usage_limit' => 4]))->assertSessionHasErrors('usage_limit');
        $this->delete('/admin/vouchers/'.$voucher->id)->assertSessionHasErrors('voucher');
    }

    public function test_percent_voucher_caps_values_and_empty_voucher_can_be_deleted(): void
    {
        $this->actingAs($this->admin());
        $data = ['code' => 'TOOBIG', 'type' => 'percent', 'value' => 10001, 'min_order' => 0, 'usage_limit' => 10, 'starts_at' => now()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString()];
        $this->post('/admin/vouchers', $data)->assertSessionHasErrors('value');
        $v = Voucher::factory()->create();
        $this->delete('/admin/vouchers/'.$v->id)->assertRedirect();
        $this->assertDatabaseMissing('vouchers', ['id' => $v->id]);
    }

    public function test_unpaid_vnpay_suborder_cannot_be_cancelled_individually(): void
    {
        $order = $this->order('vnpay', true);
        try {
            app(OrderService::class)->transition($order->shopOrders->first(), 'cancelled');
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertSame('pending', $order->shopOrders->first()->fresh()->status);
        }
        $this->actingAs($this->admin())->post('/admin/orders/'.$order->id.'/cancel')->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('cancelled', $order->payment->fresh()->status);
        $result = app(VnpayService::class)->ipn($this->payload($order));
        $this->assertSame('02', $result['RspCode']);
        $this->assertSame(0, PaymentTransaction::where('type', 'capture')->count());
    }

    public function test_cod_payment_becomes_paid_after_remaining_shop_is_collected(): void
    {
        $order = $this->order('cod', true);
        [$a,$b] = $order->shopOrders->all();
        $service = app(OrderService::class);
        $service->transition($a, 'cancelled');
        foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $state) {
            $service->transition($b, $state);
        }$service->recordMoney($b, 'capture', 'COD-REMAINING', $b->total);
        $this->assertSame('paid', $order->payment->fresh()->status);
        $this->assertSame($order->total, $order->payment->amount);
    }

    public function test_refunded_online_order_cannot_be_dispatched(): void
    {
        $order = $this->order('vnpay');
        app(VnpayService::class)->ipn($this->payload($order));
        $s = $order->shopOrders->first();
        app(OrderService::class)->recordMoney($s, 'refund', 'REF-BEFORE-SHIP', $s->total);
        $this->expectException(ValidationException::class);
        app(OrderService::class)->transition($s, 'confirmed');
    }

    public function test_callbacks_are_audited_without_duplication_of_capture(): void
    {
        $order = $this->order('vnpay');
        $d = $this->payload($order);
        $service = app(VnpayService::class);
        $service->ipn($d);
        $service->ipn($d);
        $callback = PaymentCallback::firstOrFail();
        $this->assertSame(2, $callback->attempts);
        $this->assertSame('02', $callback->response_code);
        $this->assertTrue($callback->signature_valid);
        $this->assertSame(1, PaymentTransaction::where('type', 'capture')->count());
        $bad = $d;
        $bad['vnp_SecureHash'] = 'bad';
        $this->assertSame('97', $service->ipn($bad)['RspCode']);
        $this->assertSame(2, PaymentCallback::count());
        $this->actingAs($this->admin())->get('/admin/payments')->assertOk()->assertSee($order->payment->reference);
    }

    public function test_incomplete_signed_ipn_does_not_cancel_or_pay_order(): void
    {
        $order = $this->order('vnpay');
        $d = $this->payload($order);
        unset($d['vnp_TransactionStatus']);
        $d['vnp_SecureHash'] = app(VnpayService::class)->signature($d);
        $this->assertSame('97', app(VnpayService::class)->ipn($d)['RspCode']);
        $this->assertSame('pending', $order->payment->fresh()->status);
        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_customer_cannot_unhide_moderated_review_and_admin_can_read_deleted_product_review(): void
    {
        $order = $this->order();
        $this->deliver($order);
        $product = $order->shopOrders->first()->items->first()->variant->product;
        $review = Review::create(['user_id' => $order->user_id, 'product_id' => $product->id, 'rating' => 4, 'comment' => 'Bản đầu', 'visible' => false]);
        $this->actingAs($order->user)->post('/products/'.$product->id.'/reviews', ['rating' => 5, 'comment' => 'Đã sửa'])->assertRedirect();
        $this->assertEquals(false, $review->fresh()->visible);
        $product->delete();
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('Đã sửa');
    }

    public function test_return_request_and_restock_are_idempotent_and_enforce_ownership(): void
    {
        $order = $this->order();
        $this->deliver($order);
        $item = $order->shopOrders->first()->items->first();
        $service = app(ReturnService::class);
        $request = $service->request($order->user, $item, ['type' => 'return', 'reason' => 'Không vừa']);
        $again = $service->request($order->user, $item, ['type' => 'return', 'reason' => 'Retry']);
        $this->assertSame($request->id, $again->id);
        try {
            $service->restock($request);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertSame(4, $item->variant->inventory->fresh()->stock);
        }
        $service->transition($request, 'approved', 'Đồng ý');
        $service->transition($request, 'received', 'Đã nhận');
        $this->actingAs(User::factory()->create())->post('/manage/returns/'.$request->id.'/restock', ['confirmed' => 1])->assertForbidden();
        $this->actingAs($this->admin())->post('/manage/returns/'.$request->id.'/restock', ['confirmed' => 1])->assertRedirect();
        $service->restock($request);
        $this->assertSame(5, $item->variant->inventory->fresh()->stock);
        $this->assertSame(1, DB::table('inventory_movements')->where('event_key', 'return:'.$request->id)->count());
        $this->assertSame(0, PaymentTransaction::where('type', 'refund')->count());
    }

    public function test_homepage_sections_can_be_hidden_and_menu_extended(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', ['name' => 'Shop', 'email' => 'shop@example.test', 'phone' => '0901234567', 'address' => 'Hà Nội', 'slogan' => 'Đơn giản', 'primary' => '#111111', 'secondary' => '#eeeeee', 'font' => 'Arial', 'font_size' => 16, 'spacing' => 24, 'radius' => 0, 'sections' => ['new', '', 'categories', '', ''], 'menu' => [['label' => 'Sản phẩm', 'url' => '/products'], ['label' => 'Bộ sưu tập', 'url' => '/collections']]])->assertRedirect();
        $settings = SiteSetting::where('key', 'brand')->first()->value;
        $this->assertSame(['new', 'categories'], $settings['sections']);
        $this->get('/')->assertOk()->assertSee('Bộ sưu tập');
    }

    public function test_cart_warns_and_checkout_is_blocked_when_stock_changes(): void
    {
        $v = $this->variant();
        $cart = Cart::factory()->create();
        app(CartService::class)->add($cart, $v->id, 2);
        $v->inventory->update(['stock' => 1]);
        $this->actingAs(User::findOrFail($cart->user_id))->get('/cart')->assertOk()->assertSee('không đủ số lượng')->assertDontSee('Tiến hành đặt hàng');
        $this->get('/checkout')->assertRedirect('/cart');
        $this->assertSame(0, Order::count());
    }

    public function test_reset_password_revokes_database_sessions_and_queues_notification(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => 'OldPassword123']);
        DB::table('sessions')->insert(['id' => Str::random(40), 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        Notification::fake();
        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();
        Notification::assertSentTo($user, ResetPasswordQueued::class, function ($notification) use ($user) {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->post('/reset-password', ['email' => $user->email, 'token' => $notification->token, 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect('/login');

            return true;
        });
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_password_change_revokes_other_sessions_and_rotates_remember_token(): void
    {
        config(['session.driver' => 'database']);
        $user = User::factory()->create(['password' => 'OldPassword123']);
        $old = $user->remember_token;
        $other = Str::random(40);
        DB::table('sessions')->insert(['id' => $other, 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->actingAs($user)->put('/account/password',['current_password' => 'OldPassword123', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123'])->assertRedirect();
        $this->assertDatabaseMissing('sessions',['id' => $other]);
        $this->assertNotSame($old,$user->fresh()->remember_token);
        $this->get('/account')->assertOk();
    }
}
