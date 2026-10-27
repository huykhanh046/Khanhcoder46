<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCallback extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['signature_valid' => 'boolean'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
