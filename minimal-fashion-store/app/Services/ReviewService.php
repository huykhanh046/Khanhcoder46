<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

class ReviewService
{
    public function save(User $user, Product $product, array $data): Review
    {
        $eligible = OrderItem::whereHas('variant', fn ($q) => $q->where('product_id', $product->id))->whereHas('shopOrder', fn ($q) => $q->whereIn('status', ['delivered', 'completed'])->whereHas('order', fn ($q) => $q->where('user_id', $user->id)))->exists();
        abort_unless($eligible, 403);

        // Customer edits do not undo an administrator's moderation decision.
        return Review::updateOrCreate(['user_id' => $user->id, 'product_id' => $product->id], ['rating' => $data['rating'], 'comment' => $data['comment']]);
    }
}
