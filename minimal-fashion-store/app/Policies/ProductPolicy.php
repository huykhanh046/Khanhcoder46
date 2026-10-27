<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->role === 'admin' || ($user->role === 'seller' && $product->shop->owner_id === $user->id && $product->shop->status === 'approved');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
