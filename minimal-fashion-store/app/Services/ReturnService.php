<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public const NEXT = ['requested' => ['approved', 'rejected'], 'approved' => ['received'], 'received' => ['resolved'], 'rejected' => [], 'resolved' => []];

    public function request(User $user, OrderItem $item, array $data): ReturnRequest
    {
        return DB::transaction(function () use ($user, $item, $data) {
            $order = Order::whereKey($item->shopOrder->order_id)->lockForUpdate()->firstOrFail();
            abort_unless($order->user_id === $user->id, 403);
            $sub = $item->shopOrder->fresh();
            abort_unless(in_array($sub->status, ['delivered', 'completed']) && $sub->delivered_at?->gte(now()->subDays(config('store.return_days'))), 422);

            return ReturnRequest::firstOrCreate(['order_item_id' => $item->id], ['user_id' => $user->id, 'type' => $data['type'], 'reason' => $data['reason']]);
        }, 3);
    }

    public function transition(ReturnRequest $request, string $status, string $response): void
    {
        DB::transaction(function () use ($request, $status, $response) {
            Order::whereKey($request->item->shopOrder->order_id)->lockForUpdate()->firstOrFail();
            $request = ReturnRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if (! in_array($status, self::NEXT[$request->status])) {
                throw ValidationException::withMessages(['status' => 'Trạng thái đổi/trả không hợp lệ.']);
            }
            $request->update(['status' => $status, 'response' => $response]);
            AuditService::record('return.update', $request, ['status' => $status]);
        }, 3);
    }

    public function restock(ReturnRequest $request): void
    {
        DB::transaction(function () use ($request) {
            Order::whereKey($request->item->shopOrder->order_id)->lockForUpdate()->firstOrFail();
            $request = ReturnRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($request->restocked_at) {
                return;
            }
            if (! in_array($request->status, ['received', 'resolved'])) {
                throw ValidationException::withMessages(['return' => 'Chỉ nhập lại kho khi đã nhận và kiểm tra hàng.']);
            }
            app(InventoryService::class)->change($request->item->product_variant_id, $request->item->quantity, 0, 'return:'.$request->id, 'Nhập lại hàng đổi/trả đã kiểm tra');
            $request->update(['restocked_at' => now()]);
            AuditService::record('return.restock', $request, ['quantity' => $request->item->quantity]);
        }, 3);
    }
}
