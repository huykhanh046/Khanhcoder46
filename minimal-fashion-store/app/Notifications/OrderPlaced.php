<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Đã nhận đơn hàng '.$this->order->number)->greeting('Xin chào '.$notifiable->name)->line('Đơn hàng của bạn đã được ghi nhận.')->line('Tổng thanh toán: '.number_format($this->order->total).' ₫')->action('Xem đơn hàng', route('orders.show', $this->order));
    }
}
