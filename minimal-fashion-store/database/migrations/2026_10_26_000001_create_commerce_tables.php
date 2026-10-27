<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('role')->default('customer')->index();
        });
        Schema::create('shops', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->unique()->constrained('users');
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('status')->default('pending')->index();
            $t->timestamps();
        });
        Schema::create('shop_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('shop_id')->constrained();
            $t->text('description');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shop_id')->constrained();
            $t->foreignId('category_id')->constrained();
            $t->string('name')->index();
            $t->string('slug')->unique();
            $t->text('description');
            $t->string('status')->default('pending')->index();
            $t->boolean('active')->default(true);
            $t->string('collection')->nullable()->index();
            $t->softDeletes();
            $t->index(['shop_id', 'status', 'active']);
            $t->timestamps();
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('alt');
            $t->unsignedInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::create('product_variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->string('sku')->unique();
            $t->string('size', 20);
            $t->string('color', 50);
            $t->unsignedBigInteger('price');
            $t->unsignedBigInteger('compare_price')->nullable();
            $t->boolean('active')->default(true);
            $t->unique(['product_id', 'size', 'color']);
            $t->timestamps();
        });
        Schema::create('inventories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_variant_id')->unique()->constrained();
            $t->unsignedInteger('stock')->default(0);
            $t->unsignedInteger('reserved')->default(0);
            $t->timestamps();
        });
        Schema::create('inventory_movements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_variant_id')->constrained();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->integer('stock_delta');
            $t->integer('reserved_delta');
            $t->string('reason');
            $t->string('event_key')->unique();
            $t->timestamps();
        });
        Schema::create('carts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained();
            $t->uuid('token')->unique();
            $t->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_variant_id')->constrained();
            $t->unsignedInteger('quantity');
            $t->unique(['cart_id', 'product_variant_id']);
            $t->timestamps();
        });
        Schema::create('addresses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone', 20);
            $t->string('address');
            $t->string('city');
            $t->timestamps();
        });
        Schema::create('vouchers', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('shop_id')->nullable()->constrained();
            $t->string('type');
            $t->unsignedBigInteger('value');
            $t->unsignedBigInteger('max_discount')->nullable();
            $t->unsignedBigInteger('min_order')->default(0);
            $t->unsignedInteger('usage_limit');
            $t->unsignedInteger('used')->default(0);
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('number')->unique();
            $t->foreignId('user_id')->constrained();
            $t->uuid('checkout_key')->unique();
            $t->string('status')->default('pending')->index();
            $t->string('payment_method');
            $t->json('shipping_address');
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('shipping');
            $t->unsignedBigInteger('total');
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('shop_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained();
            $t->foreignId('shop_id')->constrained();
            $t->string('status')->default('pending')->index();
            $t->string('inventory_status')->default('reserved');
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('shipping');
            $t->unsignedBigInteger('total');
            $t->string('tracking_number')->nullable();
            $t->timestamp('delivered_at')->nullable();
            $t->unique(['order_id', 'shop_id']);
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('shop_order_id')->constrained();
            $t->foreignId('product_variant_id')->constrained();
            $t->string('product_name');
            $t->string('sku');
            $t->string('size');
            $t->string('color');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('total');
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->unique()->constrained();
            $t->uuid('reference')->unique();
            $t->string('method');
            $t->string('status')->default('pending')->index();
            $t->unsignedBigInteger('amount');
            $t->timestamps();
        });
        Schema::create('payment_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('payment_id')->constrained();
            $t->foreignId('shop_order_id')->nullable()->constrained();
            $t->string('type');
            $t->unsignedBigInteger('amount');
            $t->string('event_key')->unique();
            $t->string('provider_reference')->nullable();
            $t->string('result_code')->nullable();
            $t->index(['type', 'created_at']);
            $t->timestamps();
        });
        Schema::create('voucher_usages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('voucher_id')->constrained();
            $t->foreignId('order_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('status')->default('reserved');
            $t->timestamps();
        });
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('product_id')->constrained();
            $t->unsignedTinyInteger('rating');
            $t->text('comment');
            $t->boolean('visible')->default(true);
            $t->unique(['user_id', 'product_id']);
            $t->timestamps();
        });
        Schema::create('wishlists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained();
            $t->unique(['user_id', 'product_id']);
            $t->timestamps();
        });
        Schema::create('return_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_item_id')->unique()->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('type');
            $t->text('reason');
            $t->string('status')->default('requested');
            $t->text('response')->nullable();
            $t->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->json('value');
            $t->timestamps();
        });
        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('subtitle')->nullable();
            $t->string('image_path')->nullable();
            $t->string('link')->default('/products');
            $t->unsignedInteger('position')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('action');
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->json('changes')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->id();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE inventories ADD CONSTRAINT inventory_nonnegative CHECK (stock >= reserved)');
            DB::statement('ALTER TABLE reviews ADD CONSTRAINT rating_range CHECK (rating BETWEEN 1 AND 5)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('return_requests');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('voucher_usages');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('shop_applications');
        Schema::dropIfExists('shops');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('role'));
    }
};
