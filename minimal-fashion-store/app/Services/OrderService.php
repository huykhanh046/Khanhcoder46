<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Models\ShopOrder;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const NEXT = ['pending' => ['confirmed', 'cancelled'], 'confirmed' => ['packing', 'cancelled'], 'packing' => ['shipping', 'cancelled'], 'shipping' => ['delivered'], 'delivered' => ['completed'], 'completed' => [], 'cancelled' => []];

    public function transition(ShopOrder $sub, string $next, ?string $tracking = null): void
    {
        DB::transaction(function () use ($sub, $next, $tracking) {
            $order = Order::whereKey($sub->order_id)->lockForUpdate()->firstOrFail();
            $sub = ShopOrder::whereKey($sub->id)->lockForUpdate()->firstOrFail();
            if (! in_array($next, self::NEXT[$sub->status] ?? [])) {
                throw ValidationException::withMessages(['status' => 'Không thể chuyển trạng thái theo yêu cầu.']);
            }
            if ($order->payment_method === 'vnpay' && $next !== 'cancelled' && (int) $sub->transactions()->where('type', 'capture')->sum('amount') !== $sub->total) {
                throw ValidationException::withMessages(['status' => 'Đơn chưa thanh toán thành công.']);
            }
            if ($next === 'cancelled' && $order->payment_method === 'vnpay' && $order->payment->status === 'pending') {
                throw ValidationException::withMessages(['status' => 'Đơn VNPay đang chờ thanh toán: khách hàng hoặc Admin có thể hủy toàn bộ đơn; đơn sẽ tự hết hạn.']);
            }
            if (in_array($next, ['confirmed', 'packing', 'shipping']) && $order->payment_method === 'vnpay' && (int) $sub->transactions()->where('type', 'refund')->sum('amount') > 0) {
                throw ValidationException::withMessages(['status' => 'Đơn đã hoàn tiền không thể tiếp tục xuất giao.']);
            }
            if ($next === 'cancelled') {
                app(InventoryService::class)->release($sub);
            }
            if ($next === 'delivered') {
                app(InventoryService::class)->consume($sub);
                $sub->delivered_at = now();
            }
            $sub->status = $next;
            if ($tracking !== null) {
                $sub->tracking_number = $tracking;
            } $sub->save();
            $this->aggregate($order);
            AuditService::record('order.transition', $sub, ['status' => $next]);
        }, 3);
    }

    public function cancelCustomer(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->shopOrders()->where('status', '!=', 'pending')->exists()) {
                throw ValidationException::withMessages(['order' => 'Chỉ được hủy khi tất cả đơn con đang chờ xác nhận.']);
            }
            $this->cancelAll($order);
        }, 3);
    }

    public function cancelAll(Order $order): void
    {
        foreach ($order->shopOrders()->orderBy('id')->lockForUpdate()->get() as $sub) {
            if ($sub->status === 'pending') {
                app(InventoryService::class)->release($sub);
                $sub->update(['status' => 'cancelled']);
            }
        }
        $this->aggregate($order);
    }

    public function aggregate(Order $order): void
    {
        $states = $order->shopOrders()->pluck('status');
        $status = $states->every(fn ($s) => $s === 'cancelled') ? 'cancelled' : ($states->every(fn ($s) => in_array($s, ['completed', 'cancelled'])) ? 'completed' : ($states->every(fn ($s) => in_array($s, ['delivered', 'completed', 'cancelled'])) ? 'delivered' : ($states->every(fn ($s) => $s === 'pending') ? 'pending' : 'processing')));
        $order->update(['status' => $status]);
        if ($status === 'cancelled' && $order->payment?->status === 'pending') {
            $order->payment->update(['status' => 'cancelled']);
        }
        if ($order->payment_method === 'cod' && $status !== 'cancelled' && $order->payment && ! $order->payment->transactions()->where('type', 'refund')->exists()) {
            $captured = (int) $order->payment->transactions()->where('type', 'capture')->sum('amount');
            $expected = (int) $order->shopOrders()->where('status', '!=', 'cancelled')->sum('total');
            $order->payment->update(['status' => $captured >= $expected ? 'paid' : ($captured > 0 ? 'partial' : 'pending')]);
        }
        if ($status === 'cancelled' && ($usage = $order->voucherUsage) && $usage->status === 'reserved') {
            $v = Voucher::whereKey($usage->voucher_id)->lockForUpdate()->first();
            $v->decrement('used');
            $usage->update(['status' => 'released']);
        }
    }

    public function expire(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = $order->payment;
            if ($order->expires_at?->isPast() && $payment->status === 'pending') {
                $payment->update(['status' => 'expired']);
                $this->cancelAll($order);
            }
        }, 3);
    }

    public function recordMoney(ShopOrder $sub, string $type, string $reference, int $amount): void
    {
        DB::transaction(function () use ($sub, $type, $reference, $amount) {
            $order = Order::whereKey($sub->order_id)->lockForUpdate()->firstOrFail();
            $sub->refresh();
            $payment = $order->payment;
            $eventKey = "manual:$type:$reference";
            if ($existing = PaymentTransaction::where('event_key', $eventKey)->first()) {
                if ($existing->payment_id === $payment->id && $existing->shop_order_id === $sub->id && $existing->type === $type && $existing->amount === $amount) {
                    return;
                }
                throw ValidationException::withMessages(['reference' => 'Mã đối soát đã dùng cho giao dịch khác.']);
            }
            $captured = (int) $sub->transactions()->where('type', 'capture')->sum('amount');
            $refunded = (int) $sub->transactions()->where('type', 'refund')->sum('amount');
            if ($type === 'capture') {
                if ($payment->method !== 'cod' || ! in_array($sub->status, ['delivered', 'completed']) || $captured || $amount !== $sub->total) {
                    throw ValidationException::withMessages(['amount' => 'Chỉ ghi thu COD một lần, đủ số tiền, sau giao hàng.']);
                }
            } elseif ($type === 'refund') {
                if ($amount < 1 || $amount > $captured - $refunded) {
                    throw ValidationException::withMessages(['amount' => 'Số tiền hoàn vượt số tiền đã thu còn lại.']);
                }
            } else {
                abort(422);
            }
            $transaction = PaymentTransaction::firstOrCreate(['event_key' => $eventKey], ['payment_id' => $payment->id, 'shop_order_id' => $sub->id, 'type' => $type, 'amount' => $amount, 'provider_reference' => $reference]);
            if (! $transaction->wasRecentlyCreated) {
                if ($transaction->payment_id !== $payment->id || $transaction->shop_order_id !== $sub->id || $transaction->amount !== $amount) {
                    throw ValidationException::withMessages(['reference' => 'Mã đối soát đã dùng cho giao dịch khác.']);
                }

                return;
            }
            $totalCaptured = (int) $payment->transactions()->where('type', 'capture')->sum('amount');
            $totalRefund = (int) $payment->transactions()->where('type', 'refund')->sum('amount');
            $payment->update(['status' => $totalRefund > 0 ? ($totalRefund === $totalCaptured ? 'refunded' : 'partially_refunded') : ($totalCaptured >= ($payment->method === 'cod' ? (int) $order->shopOrders()->where('status', '!=', 'cancelled')->sum('total') : $payment->amount) ? 'paid' : 'partial')]);
            AuditService::record('payment.'.$type, $sub, ['amount' => $amount, 'reference' => $reference]);
        }, 3);
    }
}
