<?php

namespace App\Policies;

use App\Models\ShopOrder;
use App\Models\User;

class ShopOrderPolicy
{
    public function update(User $user, ShopOrder $shopOrder): bool
    {
        return $user->role === 'admin' || ($user->role === 'seller' && $shopOrder->shop->owner_id === $user->id && $shopOrder->shop->status === 'approved');
    }
}
