<?php

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Voucher;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\ReturnService;
use App\Services\VnpayService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (DB::getDriverName() !== 'mysql' || ! str_ends_with(DB::connection()->getDatabaseName(), '_concurrency')) {
    throw new RuntimeException('Use a dedicated MySQL *_concurrency database.');
}
if (! function_exists('pcntl_fork')) {
    throw new RuntimeException('pcntl required.');
}
config(['store.vnpay.tmn_code' => 'TEST', 'store.vnpay.hash_secret' => 'race-test-only']);

/** Two independent connections start behind a barrier. Hold row locks to force contention. */
function race(callable $task): array
{
    $dir = sys_get_temp_dir().'/minimal-race-'.bin2hex(random_bytes(6));
    mkdir($dir, 0700);
    $pids = [];
    DB::disconnect();
    for ($index = 0; $index < 2; $index++) {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('fork failed');
        }
        if ($pid === 0) {
            DB::purge();
            touch($dir.'/ready-'.$index);
            $deadline = microtime(true) + 15;
            while (! is_file($dir.'/go')) {
                if (microtime(true) > $deadline) {
                    exit(2);
                }usleep(10000);
            }
            $held = false;
            DB::listen(function ($query) use (&$held) {
                if (! $held && str_contains(strtolower($query->sql), 'for update')) {
                    $held = true;
                    usleep(200000);
                }
            });
            try {
                $result = ['result' => 'success', 'value' => $task($index)];
            } catch (ValidationException $e) {
                $result = ['result' => 'unavailable'];
            } catch (Throwable $e) {
                $result = ['result' => 'error', 'class' => get_class($e), 'message' => $e->getMessage()];
            }
            file_put_contents($dir.'/result-'.$index, json_encode($result));
            exit(0);
        }
        $pids[] = $pid;
    }
    $deadline = microtime(true) + 15;
    while (count(glob($dir.'/ready-*')) < 2) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Barrier timeout');
        }usleep(10000);
    }
    touch($dir.'/go');
    foreach ($pids as $pid) {
        pcntl_waitpid($pid, $status);
        if (! pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
            throw new RuntimeException('Child failed');
        }
    }
    DB::purge();
    $results = array_map(fn ($f) => json_decode(file_get_contents($f), true), glob($dir.'/result-*'));
    foreach (glob($dir.'/*') as $f) {
        unlink($f);
    }rmdir($dir);

    return $results;
}
function resetSchema(): void
{
    Artisan::call('migrate:fresh', ['--force' => true]);
}
function cartFor(?ProductVariant $variant = null): Cart
{
    $variant ??= ProductVariant::factory()->create();
    $variant->inventory()->firstOrCreate([], ['stock' => 1]);
    $cart = Cart::factory()->create();
    $cart->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1]);

    return $cart;
}
function checkout(Cart $cart, string $method = 'cod', ?string $code = null): Order
{
    return app(CheckoutService::class)->place($cart, $cart->user_id, ['name' => 'Race', 'phone' => '0901234567', 'address' => '12 Test', 'city' => 'Hà Nội'], $method, $code, (string) Str::uuid());
}
function signedPayload(Order $order): array
{
    $data = ['vnp_TmnCode' => 'TEST', 'vnp_TxnRef' => $order->payment->reference, 'vnp_Amount' => (string) ($order->total * 100), 'vnp_ResponseCode' => '00', 'vnp_TransactionStatus' => '00', 'vnp_TransactionNo' => '123456'];
    $data['vnp_SecureHash'] = app(VnpayService::class)->signature($data);

    return $data;
}
$report = [];
resetSchema();
$variant = ProductVariant::factory()->create();
$ids = [cartFor($variant)->id, cartFor($variant)->id];
$r = race(fn ($i) => checkout(Cart::findOrFail($ids[$i]))->id);
$counts = array_count_values(array_column($r, 'result'));
$inventory = Inventory::firstOrFail();
$report['last_item'] = ['passed' => ($counts['success'] ?? 0) === 1 && ($counts['unavailable'] ?? 0) === 1 && Order::count() === 1 && $inventory->stock === 1 && $inventory->reserved === 1, 'results' => $r, 'orders' => Order::count(), 'stock' => $inventory->stock, 'reserved' => $inventory->reserved];

resetSchema();
Voucher::factory()->create(['code' => 'LAST', 'usage_limit' => 1, 'used' => 0]);
$ids = [cartFor()->id, cartFor()->id];
$r = race(fn ($i) => checkout(Cart::findOrFail($ids[$i]), 'cod', 'LAST')->id);
$counts = array_count_values(array_column($r, 'result'));
$report['last_voucher'] = ['passed' => ($counts['success'] ?? 0) === 1 && ($counts['unavailable'] ?? 0) === 1 && Order::count() === 1 && Voucher::first()->used === 1 && (int) Inventory::sum('reserved') === 1, 'results' => $r, 'used' => Voucher::first()->used];

resetSchema();
$order = checkout(cartFor(), 'vnpay');
$payload = signedPayload($order);
$r = race(fn ($i) => app(VnpayService::class)->ipn($payload));
$codes = array_column(array_column($r, 'value'), 'RspCode');
sort($codes);
$report['duplicate_ipn'] = ['passed' => $codes === ['00', '02'] && PaymentTransaction::where('type', 'capture')->count() === 1 && $order->payment->fresh()->status === 'paid' && PaymentCallback::first()->attempts === 2, 'results' => $r, 'captures' => PaymentTransaction::where('type', 'capture')->count(), 'callback_attempts' => PaymentCallback::first()->attempts];

resetSchema();
$order = checkout(cartFor(), 'vnpay');
$id = $order->id;
$payload = signedPayload($order);
$r = race(function ($i) use ($id, $payload) {
    if ($i === 0) {
        app(OrderService::class)->cancelCustomer(Order::findOrFail($id));

        return 'cancelled';
    }

return app(VnpayService::class)->ipn($payload);
});
$order->refresh();
$captured = (int) PaymentTransaction::where('type', 'capture')->sum('amount');
$inventory = Inventory::firstOrFail();
$report['cancel_vs_ipn'] = ['passed' => count(array_filter($r, fn ($x) => $x['result'] === 'success')) === 2 && $order->status === 'cancelled' && $inventory->reserved === 0 && $inventory->stock === 1 && in_array($captured, [0, $order->total]) && DB::table('inventory_movements')->where('event_key', 'like', 'release:%')->count() === 1 && ($captured === 0 ? $order->payment->status === 'cancelled' : $order->payment->status === 'paid'), 'results' => $r, 'captures_amount' => $captured, 'payment_status' => $order->payment->status, 'reserved' => $inventory->reserved];

resetSchema();
$order = checkout(cartFor());
$sub = $order->shopOrders->first();
foreach (['confirmed', 'packing', 'shipping', 'delivered'] as $state) {
    app(OrderService::class)->transition($sub, $state);
}
$request = app(ReturnService::class)->request($order->user, $sub->items->first(), ['type' => 'return', 'reason' => 'Không vừa']);
app(ReturnService::class)->transition($request, 'approved', 'Đồng ý');
app(ReturnService::class)->transition($request, 'received', 'Đã kiểm tra');
$id = $request->id;
$r = race(function ($i) use ($id) {
    app(ReturnService::class)->restock(ReturnRequest::findOrFail($id));

    return 'restocked';
});
$inventory = Inventory::firstOrFail();
$report['duplicate_return_restock'] = ['passed' => count(array_filter($r, fn ($x) => $x['result'] === 'success')) === 2 && $inventory->stock === 1 && $inventory->reserved === 0 && DB::table('inventory_movements')->where('event_key', 'return:'.$id)->count() === 1, 'results' => $r, 'stock' => $inventory->stock, 'reserved' => $inventory->reserved];
$passed = ! in_array(false, array_column($report,'passed'), true);
echo json_encode(['passed' => $passed, 'scenarios' => $report],JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
exit($passed ? 0 : 1);
