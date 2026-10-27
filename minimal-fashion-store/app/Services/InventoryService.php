<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\ShopOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    // Caller must wrap multi-item operations in one database transaction.
    public function change(int $variantId, int $stockDelta, int $reservedDelta, string $key, string $reason): void
    {
        $inventory = Inventory::where('product_variant_id', $variantId)->lockForUpdate()->firstOrFail();
        if (InventoryMovement::where('event_key', $key)->exists()) {
            return;
        }
        $stock = $inventory->stock + $stockDelta;
        $reserved = $inventory->reserved + $reservedDelta;
        if ($stock < 0 || $reserved < 0 || $reserved > $stock) {
            throw ValidationException::withMessages(['stock' => 'Không đủ tồn kho khả dụng. Vui lòng cập nhật giỏ hàng.']);
        }
        $inventory->update(['stock' => $stock, 'reserved' => $reserved]);
        InventoryMovement::create(['product_variant_id' => $variantId, 'user_id' => auth()->id(), 'stock_delta' => $stockDelta, 'reserved_delta' => $reservedDelta, 'event_key' => $key, 'reason' => $reason]);
    }

    public function release(ShopOrder $shopOrder): void
    {
        if ($shopOrder->inventory_status !== 'reserved') {
            return;
        }
        foreach ($shopOrder->items()->orderBy('product_variant_id')->get() as $item) {
            $this->change($item->product_variant_id, 0, -$item->quantity, "release:{$item->id}", 'Hủy đơn / hết hạn');
        }
        $shopOrder->update(['inventory_status' => 'released']);
    }

    public function consume(ShopOrder $shopOrder): void
    {
        if ($shopOrder->inventory_status !== 'reserved') {
            return;
        }
        foreach ($shopOrder->items()->orderBy('product_variant_id')->get() as $item) {
            $this->change($item->product_variant_id, -$item->quantity, -$item->quantity, "consume:{$item->id}", 'Đã giao hàng');
        }
        $shopOrder->update(['inventory_status' => 'consumed']);
    }

    public function adjust(int $variantId, int $delta, string $reason): void
    {
        DB::transaction(fn () => $this->change($variantId, $delta, 0, 'adjust:'.Str::uuid(), $reason), 3);
    }
}
