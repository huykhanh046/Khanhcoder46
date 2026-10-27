<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Không seed tài khoản demo trên production.');
        }
        if (User::exists()) {
            throw new \RuntimeException('Chỉ seed database trống. Không ghi đè dữ liệu người dùng.');
        }
        DB::transaction(function () {
            $password = Hash::make(env('DEMO_PASSWORD', 'DemoFashion123!'));
            $admin = User::create(['name' => 'Quản trị viên', 'email' => 'admin@example.test', 'password' => $password]);
            $admin->forceFill(['role' => 'admin'])->save();
            $shops = [];
            foreach (['MINIMAL Studio', 'Everyday Atelier'] as $i => $name) {
                $seller = User::create(['name' => 'Người bán '.($i + 1), 'email' => 'seller'.($i + 1).'@example.test', 'password' => $password]);
                $seller->forceFill(['role' => 'seller'])->save();
                $shops[] = Shop::create(['owner_id' => $seller->id, 'name' => $name, 'slug' => Str::slug($name), 'status' => 'approved']);
            }
            $customers = [];
            for ($i = 1; $i <= 3; $i++) {
                $customers[] = User::create(['name' => 'Khách hàng '.$i, 'email' => 'customer'.$i.'@example.test', 'password' => $password]);
            }
            $categories = [];
            foreach (['Áo', 'Quần', 'Váy', 'Áo khoác', 'Phụ kiện'] as $name) {
                $categories[] = Category::create(['name' => $name, 'slug' => Str::slug($name)]);
            }
            $names = ['Áo thun cotton', 'Quần suông linen', 'Váy midi thanh lịch', 'Áo khoác dáng rộng', 'Túi tote canvas'];
            $adjectives = ['Essential', 'Everyday', 'Soft', 'Classic', 'Relaxed', 'Urban', 'Natural', 'Signature'];
            $collections = [];
            foreach (['Everyday', 'Essential'] as $name) {
                $collections[$name] = Collection::create(['name' => $name, 'slug' => strtolower($name), 'description' => 'Những thiết kế tinh giản cho mỗi ngày.', 'image_path' => '/images/collection.svg']);
            }
            $variantIds = [];
            for ($i = 0; $i < 40; $i++) {
                $product = Product::create(['shop_id' => $shops[$i % 2]->id, 'category_id' => $categories[$i % 5]->id, 'name' => $names[$i % 5].' '.$adjectives[intdiv($i, 5)], 'slug' => 'product-'.($i + 1), 'description' => 'Thiết kế tối giản, dễ phối đồ cho nhiều dịp. Chất liệu mềm mại, thoải mái. Giặt nhẹ ở nhiệt độ thấp, phơi trong bóng râm. Đây là sản phẩm mẫu phục vụ đồ án, thông số và hình minh họa có thể thay thế.', 'status' => 'approved', 'active' => true, 'collection_id' => $collections[$i % 2 === 0 ? 'Everyday' : 'Essential']->id]);
                $product->images()->create(['path' => '/images/product-'.($i % 5 + 1).'.svg', 'alt' => 'Hình minh họa '.$product->name]);
                foreach (['S', 'M', 'L'] as $size) {
                    foreach (['Đen', 'Trắng'] as $j => $color) {
                        $v = $product->variants()->create(['sku' => 'MF-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT).'-'.$size.'-'.$j, 'size' => $size, 'color' => $color, 'price' => 199000 + ($i % 8) * 50000 + ($size === 'L' ? 10000 : 0), 'compare_price' => $i % 4 === 0 ? 699000 : null]);
                        $variantIds[] = $v->id;
                        $v->inventory()->create(['stock' => 30, 'reserved' => 0]);
                        InventoryMovement::create(['product_variant_id' => $v->id, 'stock_delta' => 30, 'reserved_delta' => 0, 'event_key' => 'seed:'.$v->id, 'reason' => 'Tồn mẫu khởi tạo']);
                    }
                }
            }
            // Explicit sold-out and low-stock examples.
            app(InventoryService::class)->adjust($variantIds[239], -30, 'Dữ liệu mẫu hết hàng');
            app(InventoryService::class)->adjust($variantIds[238], -27, 'Dữ liệu mẫu sắp hết hàng');
            Voucher::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 1000, 'max_discount' => 100000, 'min_order' => 300000, 'usage_limit' => 100, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
            Voucher::create(['code' => 'SHOP50', 'shop_id' => $shops[0]->id, 'type' => 'fixed', 'value' => 50000, 'min_order' => 300000, 'usage_limit' => 30, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
            SiteSetting::create(['key' => 'brand', 'value' => ['name' => config('store.name'), 'email' => 'hello@example.test', 'phone' => '0900 000 000', 'address' => 'Địa chỉ cửa hàng mẫu — cập nhật trong quản trị', 'slogan' => 'Những thiết kế tinh giản, chất liệu thoải mái. Dành cho từng khoảnh khắc thường ngày.', 'primary' => '#191919', 'secondary' => '#f3f2ef', 'font' => 'Arial', 'font_size' => 16, 'spacing' => 24, 'radius' => 0, 'sections' => ['categories', 'new', 'bestsellers', 'collection', 'promotion'], 'menu' => [['label' => 'Sản phẩm', 'url' => '/products'], ['label' => 'Mới nhất', 'url' => '/products?sort=new'], ['label' => 'Bộ sưu tập', 'url' => '/collections']]]]);
            Banner::create(['title' => 'Đơn giản. Là chính bạn.', 'subtitle' => 'Những món đồ bạn muốn mặc mỗi ngày. Khám phá bộ sưu tập mới với tinh thần tự do và tối giản.', 'image_path' => '/images/hero.svg', 'position' => 0]);
            Banner::create(['title' => 'Chào bạn, phong cách mới.', 'subtitle' => 'Mã WELCOME10 giảm 10%, tối đa 100.000 ₫ cho đơn từ 300.000 ₫. Áp dụng theo hạn và lượt sử dụng của voucher.', 'position' => 1]);
            foreach ($customers as $customer) {
                $customer->addresses()->create(['name' => $customer->name, 'phone' => '0901234567', 'address' => '12 Đường Mẫu, Phường Trung Tâm', 'city' => 'TP. Hồ Chí Minh']);
            }
            // Demo orders go through the same checkout/stock/state services as real orders.
            for ($i = 0; $i < 18; $i++) {
                $customer = $customers[$i % 3];
                $cart = Cart::firstOrCreate(['user_id' => $customer->id], ['token' => (string) Str::uuid()]);
                $variant = ProductVariant::findOrFail($variantIds[($i % 12) * 6]);
                app(CartService::class)->add($cart, $variant->id, 1);
                if ($i % 3 === 0) {
                    app(CartService::class)->add($cart, $variantIds[($i % 12) * 6 + 6], 1);
                }
                $order = app(CheckoutService::class)->place($cart, $customer->id, ['name' => $customer->name, 'phone' => '0901234567', 'address' => '12 Đường Mẫu', 'city' => 'TP. Hồ Chí Minh'], 'cod', null, (string) Str::uuid());
                $stage = $i % 7;
                foreach ($order->shopOrders as $sub) {
                    if ($stage === 6) {
                        app(OrderService::class)->transition($sub, 'cancelled');

                        continue;
                    }
                    foreach (array_slice(['confirmed', 'packing', 'shipping', 'delivered', 'completed'], 0, $stage) as $next) {
                        app(OrderService::class)->transition($sub, $next);
                    }
                    if (in_array($stage, [4, 5])) {
                        app(OrderService::class)->recordMoney($sub, 'capture', 'DEMO-COD-'.$sub->id, $sub->total);
                    }
                }
                $date = now()->subDays(17 - $i);
                $order->update(['created_at' => $date]);
                $order->shopOrders()->update(['created_at' => $date]);
                foreach ($order->shopOrders as $sub) {
                    if ($sub->delivered_at) {
                        $sub->update(['delivered_at' => $date]);
                    }
                }
                $order->payment->transactions()->update(['created_at' => $date]);
            }
        });
    }
}
