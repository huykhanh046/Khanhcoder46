<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentCallback;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VnpayService
{
    public function configured(): bool
    {
        return filled(config('store.vnpay.tmn_code')) && filled(config('store.vnpay.hash_secret'));
    }

    public function signature(array $data): string
    {
        unset($data['vnp_SecureHash'],$data['vnp_SecureHashType']);
        $data = array_filter($data, fn ($value, $key) => str_starts_with($key, 'vnp_') && is_scalar($value) && $value !== '', ARRAY_FILTER_USE_BOTH);
        ksort($data);

        return hash_hmac('sha512', http_build_query($data, '', '&', PHP_QUERY_RFC1738), (string) config('store.vnpay.hash_secret'));
    }

    public function url(Order $order, string $ip): string
    {
        abort_unless($this->configured(), 503);
        abort_unless($order->payment->status === 'pending' && $order->status !== 'cancelled' && $order->expires_at?->isFuture(), 422);
        $data = ['vnp_Version' => '2.1.0', 'vnp_Command' => 'pay', 'vnp_TmnCode' => config('store.vnpay.tmn_code'), 'vnp_Amount' => $order->total * 100, 'vnp_CurrCode' => 'VND', 'vnp_TxnRef' => $order->payment->reference, 'vnp_OrderInfo' => 'Thanh toan don '.$order->number, 'vnp_OrderType' => 'other', 'vnp_Locale' => 'vn', 'vnp_ReturnUrl' => route('payments.return'), 'vnp_IpAddr' => $ip, 'vnp_CreateDate' => $order->created_at->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'), 'vnp_ExpireDate' => $order->expires_at->timezone('Asia/Ho_Chi_Minh')->format('YmdHis')];
        $data['vnp_SecureHash'] = $this->signature($data);

        return config('store.vnpay.url').'?'.http_build_query($data, '', '&', PHP_QUERY_RFC1738);
    }

    public function ipn(array $data): array
    {
        try {
            $result = $this->processIpn($data);
        } catch (\Throwable $e) {
            Log::error('VNPay IPN processing failed', ['exception' => get_class($e)]);

            return ['RspCode' => '99', 'Message' => 'Processing error; retry later'];
        }
        try {
            $reference = is_string($data['vnp_TxnRef'] ?? null) ? mb_substr($data['vnp_TxnRef'], 0, 100) : null;
            $provider = is_string($data['vnp_TransactionNo'] ?? null) ? mb_substr($data['vnp_TransactionNo'], 0, 100) : null;
            $sorted = $data;
            ksort($sorted);
            $fingerprint = hash('sha256', json_encode($sorted, JSON_INVALID_UTF8_SUBSTITUTE));
            $callback = PaymentCallback::firstOrCreate(['fingerprint' => $fingerprint], ['attempts' => 0, 'payment_id' => $reference ? Payment::where('reference', $reference)->value('id') : null, 'transaction_reference' => $reference, 'provider_reference' => $provider, 'response_code' => $result['RspCode'], 'signature_valid' => $this->configured() && is_string($data['vnp_SecureHash'] ?? null) && hash_equals($this->signature($data), $data['vnp_SecureHash']), 'verdict' => $result['Message']]);
            $callback->increment('attempts');
            $callback->update(['response_code' => $result['RspCode'], 'verdict' => $result['Message']]);
        } catch (\Throwable $e) {
            Log::warning('VNPay callback audit unavailable', ['exception' => get_class($e)]);
        }

        return $result;
    }

    private function processIpn(array $data): array
    {
        $reply = fn ($code, $message) => ['RspCode' => $code, 'Message' => $message];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'vnp_') && ! is_scalar($value)) {
                return $reply('97', 'Malformed payload');
            }
        }
        if (! $this->configured() || ! is_string($data['vnp_SecureHash'] ?? null) || ! hash_equals($this->signature($data), $data['vnp_SecureHash'])) {
            return $reply('97', 'Invalid signature');
        }
        if (($data['vnp_TmnCode'] ?? '') !== config('store.vnpay.tmn_code') || (isset($data['vnp_CurrCode']) && $data['vnp_CurrCode'] !== 'VND')) {
            return $reply('97', 'Invalid merchant or currency');
        }
        foreach (['vnp_TxnRef', 'vnp_Amount', 'vnp_ResponseCode', 'vnp_TransactionStatus'] as $required) {
            if (! isset($data[$required]) || ! is_scalar($data[$required]) || (string) $data[$required] === '') {
                return $reply('97', 'Missing required fields');
            }
        }
        if (! preg_match('/^[0-9]+$/', (string) $data['vnp_Amount']) || ! preg_match('/^[0-9]{2}$/', (string) $data['vnp_ResponseCode']) || ! preg_match('/^[0-9]{2}$/', (string) $data['vnp_TransactionStatus']) || strlen((string) $data['vnp_TxnRef']) > 100) {
            return $reply('97', 'Malformed fields');
        }
        if ($data['vnp_ResponseCode'] === '00' && $data['vnp_TransactionStatus'] === '00' && (! isset($data['vnp_TransactionNo']) || ! preg_match('/^[0-9]{1,30}$/', (string) $data['vnp_TransactionNo']) || (string) $data['vnp_TransactionNo'] === '0')) {
            return $reply('97', 'Missing provider transaction');
        }
        $payment = Payment::where('reference', $data['vnp_TxnRef'] ?? '')->where('method', 'vnpay')->first();
        if (! $payment) {
            return $reply('01', 'Order not found');
        }

        return DB::transaction(function () use ($data, $payment, $reply) {
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
            $payment->refresh();
            if ((string) ($data['vnp_Amount'] ?? '') !== (string) ($payment->amount * 100)) {
                return $reply('04', 'Invalid amount');
            }
            if ($payment->status !== 'pending') {
                if (in_array($payment->status, ['expired', 'cancelled'])) {
                    PaymentTransaction::firstOrCreate(['event_key' => 'late:'.$payment->id], ['payment_id' => $payment->id, 'type' => 'rejected', 'amount' => 0, 'provider_reference' => $data['vnp_TransactionNo'] ?? null, 'result_code' => 'late']);
                }

                return $reply('02', 'Order already confirmed');
            }
            if ($order->status === 'cancelled' || $order->expires_at->isPast()) {
                $payment->update(['status' => 'expired']);
                app(OrderService::class)->cancelAll($order);
                PaymentTransaction::firstOrCreate(['event_key' => 'late:'.$payment->id], ['payment_id' => $payment->id, 'type' => 'rejected', 'amount' => 0, 'provider_reference' => $data['vnp_TransactionNo'] ?? null, 'result_code' => 'late']);

                return $reply('02', 'Expired; reconciliation required');
            }
            $success = ($data['vnp_ResponseCode'] ?? '') === '00' && ($data['vnp_TransactionStatus'] ?? '') === '00';
            if ($success) {
                foreach ($order->shopOrders as $sub) {
                    PaymentTransaction::create(['payment_id' => $payment->id, 'shop_order_id' => $sub->id, 'type' => 'capture', 'amount' => $sub->total, 'event_key' => "vnpay:{$payment->id}:{$sub->id}", 'provider_reference' => $data['vnp_TransactionNo'] ?? null, 'result_code' => '00']);
                }
                $payment->update(['status' => 'paid']);
            } else {
                PaymentTransaction::create(['payment_id' => $payment->id, 'type' => 'failed', 'amount' => 0, 'event_key' => 'failed:'.$payment->id, 'provider_reference' => $data['vnp_TransactionNo'] ?? null, 'result_code' => $data['vnp_ResponseCode'] ?? 'unknown']);
                $payment->update(['status' => 'failed']);
                app(OrderService::class)->cancelAll($order);
            }

            return $reply('00', 'Confirm success');
        }, 3);
    }
}
