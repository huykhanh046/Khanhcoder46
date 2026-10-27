<?php

use App\Models\Order;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\Rules\Password;

Artisan::command('orders:expire', function () {
    Order::where('payment_method', 'vnpay')->where('expires_at', '<=', now())->whereHas('payment', fn ($q) => $q->where('status', 'pending'))->chunkById(100, function ($orders) {
        foreach ($orders as $order) {
            app(OrderService::class)->expire($order);
        }
    });
    $this->info('Đã xử lý các đơn hết hạn.');
});
Schedule::command('orders:expire')->everyMinute()->withoutOverlapping();

Artisan::command('store:create-admin', function () {
    $name = $this->ask('Họ và tên');
    $email = $this->ask('Email');
    $password = $this->secret('Mật khẩu (ít nhất 12 ký tự, gồm chữ và số)');
    $data = validator(compact('name', 'email', 'password'), ['name' => 'required|string|max:100', 'email' => 'required|email|unique:users', 'password' => ['required', Password::min(12)->letters()->numbers()]])->validate();
    $user = User::create($data);
    $user->forceFill(['role' => 'admin'])->save();
    $this->info('Đã tạo quản trị viên.');
})->purpose('Tạo tài khoản quản trị bằng mật khẩu nhập ẩn');
