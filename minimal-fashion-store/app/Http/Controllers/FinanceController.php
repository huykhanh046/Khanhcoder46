<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\SiteSetting;
use App\Services\AuditService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function index(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:100', 'method' => 'nullable|in:cod,vnpay', 'status' => 'nullable|in:pending,partial,paid,failed,expired,cancelled,refunded,partially_refunded', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $q = Payment::with(['order.user', 'order.shopOrders.shop', 'order.shopOrders.transactions']);
        foreach (['status', 'method'] as $field) {
            if ($r->filled($field)) {
                $q->where($field, $r->input($field));
            }
        }
        if ($r->filled('q')) {
            $q->where(fn ($q) => $q->where('reference', 'like', '%'.$r->q.'%')->orWhereHas('order', fn ($q) => $q->where('number', 'like', '%'.$r->q.'%')));
        }
        if ($r->filled('from')) {
            $q->whereDate('created_at', '>=', $r->from);
        }if ($r->filled('to')) {
            $q->whereDate('created_at', '<=', $r->to);
        }

        return view('admin.payments', ['payments' => $q->latest()->paginate(15)->withQueryString(), 'callbacks' => PaymentCallback::with('payment')->latest('updated_at')->paginate(20, ['*'], 'callbacks_page')]);
    }

    public function cancel(Order $order)
    {
        app(OrderService::class)->cancelCustomer($order);
        AuditService::record('order.admin_cancel', $order);

        return back()->with('success', 'Đã hủy toàn bộ đơn đang chờ xác nhận. Khoản đã thu (nếu có) cần đối soát hoàn tiền riêng.');
    }

    public function shipping()
    {
        return view('admin.shipping', ['settings' => SiteSetting::where('key', 'shipping')->first()?->value ?? []]);
    }

    public function updateShipping(Request $r)
    {
        $data = $r->validate(['standard_fee' => 'required|integer|between:1,1000000', 'express_fee' => 'required|integer|between:1,1000000']);
        $setting = SiteSetting::updateOrCreate(['key' => 'shipping'], ['value' => $data]);
        AuditService::record('shipping.update', $setting, $data);

        return back()->with('success','Đã lưu phí vận chuyển. Các đơn cũ giữ nguyên số tiền.');
    }
}
