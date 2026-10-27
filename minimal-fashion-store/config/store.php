<?php

return [
    'name' => 'MINIMAL FASHION STORE',
    'images' => ['product' => '/images/product-1.svg', 'hero' => '/images/hero.svg', 'collection' => '/images/collection.svg'],
    'shipping_per_shop' => 30000,
    'reservation_minutes' => 15,
    'return_days' => 14,
    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    ],
    'tokens' => ['primary' => '#191919', 'secondary' => '#f3f2ef', 'font' => 'Arial, sans-serif', 'font_size' => 16, 'spacing' => 24, 'radius' => 0],
];
