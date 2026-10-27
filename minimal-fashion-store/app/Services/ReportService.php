<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\ProductVariant;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function data(?int $shopId, string $from, string $to): array
    {
        $orders = ShopOrder::when($shopId, fn ($q) => $q->where('shop_id', $shopId))->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59']);
        $ledger = PaymentTransaction::whereIn('type', ['capture', 'refund'])->whereBetween('payment_transactions.created_at', [$from.' 00:00:00', $to.' 23:59:59'])->when($shopId, fn ($q) => $q->whereHas('shopOrder', fn ($q) => $q->where('shop_id', $shopId)));
        $revenue = (int) (clone $ledger)->selectRaw("COALESCE(SUM(CASE WHEN type='capture' THEN amount ELSE -CAST(amount AS SIGNED) END),0) AS value")->value('value');
        $daily = (clone $ledger)->selectRaw("DATE(created_at) AS day, SUM(CASE WHEN type='capture' THEN amount ELSE -CAST(amount AS SIGNED) END) AS amount")->groupBy('day')->orderBy('day')->get();
        $monthly = (clone $ledger)->selectRaw("DATE_FORMAT(payment_transactions.created_at, '%Y-%m') AS month, SUM(CASE WHEN type='capture' THEN amount ELSE -CAST(amount AS SIGNED) END) AS amount")->groupBy('month')->orderBy('month')->get();
        $byShop = (clone $ledger)->join('shop_orders', 'shop_orders.id', '=', 'payment_transactions.shop_order_id')->join('shops', 'shops.id', '=', 'shop_orders.shop_id')->selectRaw("shops.name, SUM(CASE WHEN type='capture' THEN amount ELSE -CAST(amount AS SIGNED) END) AS amount")->groupBy('shops.id', 'shops.name')->get();
        $low = ProductVariant::with('product', 'inventory')->whereHas('product', fn ($q) => $q->when($shopId, fn ($q) => $q->where('shop_id', $shopId)))->whereHas('inventory', fn ($q) => $q->whereRaw('stock - reserved <= 5'))->limit(20)->get();
        $best = DB::table('order_items')->join('shop_orders', 'shop_orders.id', '=', 'order_items.shop_order_id')->whereIn('shop_orders.status', ['delivered', 'completed'])->whereBetween('shop_orders.delivered_at', [$from.' 00:00:00', $to.' 23:59:59'])->when($shopId, fn ($q) => $q->where('shop_orders.shop_id', $shopId))->selectRaw('order_items.sku, MAX(order_items.product_name) as name, SUM(quantity) as quantity')->groupBy('order_items.sku')->orderByDesc('quantity')->limit(10)->get();

        return ['revenue' => $revenue, 'daily' => $daily, 'monthly' => $monthly, 'byShop' => $byShop, 'count' => (clone $orders)->count(), 'completed' => (clone $orders)->where('status', 'completed')->count(), 'cancelled' => (clone $orders)->where('status', 'cancelled')->count(), 'states' => (clone $orders)->selectRaw('status, COUNT(*) AS count')->groupBy('status')->get(), 'low' => $low, 'best' => $best];
    }
}
