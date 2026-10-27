<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = ['shipping_address' => 'array', 'expires_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shopOrders()
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function voucherUsage()
    {
        return $this->hasOne(VoucherUsage::class);
    }
}
