<?php

use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (DB::getDriverName() !== 'mysql' || ! str_ends_with(DB::connection()->getDatabaseName(), '_test')) {
    throw new RuntimeException('Use an isolated MySQL *_test database.');
}
Artisan::call('migrate:fresh', ['--force' => true]);
DB::statement('ALTER TABLE product_variants AUTO_INCREMENT = 1000');
Artisan::call('db:seed', ['--force' => true]);
$shopCount = PaymentTransaction::where('type', 'capture')->join('shop_orders', 'shop_orders.id', '=', 'payment_transactions.shop_order_id')->distinct()->count('shop_orders.shop_id');
$bad = 0;
foreach (Inventory::all() as $inventory) {
    $totals = DB::table('inventory_movements')->where('product_variant_id', $inventory->product_variant_id)->selectRaw('SUM(stock_delta) as stock, SUM(reserved_delta) as reserved')->first();
    if ((int) $totals->stock !== $inventory->stock || (int) $totals->reserved !== $inventory->reserved) {
        $bad++;
    }
}
$passed = Product::count() === 40 && ProductVariant::count() === 240 && Order::count() === 18 && $shopCount === 2 && $bad === 0;
echo json_encode(['passed' => $passed, 'products' => Product::count(), 'variants' => ProductVariant::count(), 'minimum_variant_id' => ProductVariant::min('id'), 'orders' => Order::count(), 'shops_with_revenue' => $shopCount, 'inventory_ledger_mismatches' => $bad], JSON_PRETTY_PRINT).PHP_EOL;
exit($passed ? 0 : 1);
