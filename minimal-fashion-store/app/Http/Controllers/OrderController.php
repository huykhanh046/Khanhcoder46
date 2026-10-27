<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\VnpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        return view('orders.index', ['orders' => $r->user()->orders()->latest()->paginate(10)]);
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        return view('orders.show', ['order' => $order->load('shopOrders.items.returnRequest', 'shopOrders.shop', 'payment.transactions')]);
    }

    public function cancel(Order $order, OrderService $service)
    {
        Gate::authorize('cancel', $order);
        $service->cancelCustomer($order);

        return back()->with('success', 'Đã hủy đơn và giải phóng tồn kho. Khoản đã thu (nếu có) chờ đối soát hoàn tiền.');
    }

    public function pay(Request $r, Order $order, VnpayService $service)
    {
        Gate::authorize('view', $order);

        return redirect()->away($service->url($order, $r->ip()));
    }

    public function ipn(Request $r, VnpayService $service)
    {
        return response()->json($service->ipn($r->query()));
    }

    public function result(Request $r)
    {
        $payment = Payment::where('reference', $r->query('vnp_TxnRef'))->whereHas('order', fn ($q) => $q->where('user_id', auth()->id()))->first();

        return view('payments.result', compact('payment'));
    }
}
