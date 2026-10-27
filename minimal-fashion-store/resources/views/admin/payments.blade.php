<x-layout title="Đối soát thanh toán" :manage="true">
    <h1 class="page-title">Thanh toán & đối soát.</h1>
    <p class="muted">Tách rõ tiền đã thu, tiền đã hoàn và khoản cần hoàn do hủy. Thao tác ghi thu/hoàn thực tế ở chi
        tiết đơn con.</p>
    <form class="inline mt-6"><x-input name="q" label="Mã giao dịch / đơn tổng" :value="request('q')" />
        <div class="field"><label for="method">Phương thức</label><select id="method" name="method">
                <option value="">Tất cả</option>
                @foreach (['cod', 'vnpay'] as $method)
                    <option value="{{ $method }}" @selected(request('method') === $method)>{{ config('labels.' . $method) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field"><label for="status">Trạng thái</label><select id="status" name="status">
                <option value="">Tất cả</option>
                @foreach (['pending', 'partial', 'paid', 'failed', 'expired', 'cancelled', 'refunded', 'partially_refunded'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ config('labels.' . $status) }}
                    </option>
                @endforeach
            </select>
        </div><x-input name="from" label="Từ ngày" type="date" :value="request('from')" /><x-input name="to"
            label="Đến ngày" type="date" :value="request('to')" /><button type="submit" class="btn">Lọc</button>
    </form>
    <div class="stack mt-6">
        @forelse($payments as $payment)
            @php($captured = $payment->order->shopOrders->sum(fn($s) => $s->transactions->where('type', 'capture')->sum('amount')))
            @php($refunded = $payment->order->shopOrders->sum(fn($s) => $s->transactions->where('type', 'refund')->sum('amount')))
            @php($refundDue = $payment->order->shopOrders->where('status', 'cancelled')->sum(fn($s) => max(0, $s->transactions->where('type', 'capture')->sum('amount') - $s->transactions->where('type', 'refund')->sum('amount'))))
            <section class="panel">
                <div class="row">
                    <div><a href="/orders/{{ $payment->order_id }}" class="text-link">Đơn
                            {{ substr($payment->order->number, 0, 8) }}</a>
                        <p class="muted">{{ $payment->order->user->email }} · {{ config('labels.' . $payment->method) }}
                        </p>
                    </div><span
                        class="status">{{ $payment->status === 'pending' ? 'Chờ thanh toán' : config('labels.' . $payment->status) }}</span>
                </div>
                <p class="small break-all">Mã giao dịch: {{ $payment->reference }}</p>
                <div class="stats">
                    <div class="stat">
                        <p class="muted">Yêu cầu ban đầu</p><strong>{{ number_format($payment->amount) }} ₫</strong>
                    </div>
                    <div class="stat">
                        <p class="muted">Đã thu</p><strong>{{ number_format($captured) }} ₫</strong>
                    </div>
                    <div class="stat">
                        <p class="muted">Đã hoàn thực tế</p><strong>{{ number_format($refunded) }} ₫</strong>
                    </div>
                    <div class="stat">
                        <p class="muted">Cần hoàn do hủy</p><strong>{{ number_format($refundDue) }} ₫</strong>
                    </div>
                </div>
                <div class="inline">
                    @foreach ($payment->order->shopOrders as $sub)
                        <a class="text-link" href="/manage/orders/{{ $sub->id }}">#{{ $sub->id }} ·
                            {{ $sub->shop->name }} · {{ config('labels.' . $sub->status) }}</a>
                    @endforeach
                </div>
                @if ($payment->order->shopOrders->every(fn($s) => $s->status === 'pending'))
                    <x-form :action="'/admin/orders/' . $payment->order_id . '/cancel'" class="mt-5"
                        confirm="Hủy toàn bộ đơn đang chờ xác nhận? Khoản đã thu không tự hoàn."><button type="submit"
                            class="btn small secondary">Hủy toàn bộ đơn tổng</button></x-form>
                @endif
            </section>
        @empty<div class="empty">Chưa có giao dịch phù hợp.</div>
        @endforelse
    </div>
    <x-pagination :items="$payments" />
    <h2 class="text-xl my-6">Lịch sử IPN VNPay</h2>
    <p class="form-note">Gộp callback có cùng dấu vân tay, đếm số lần nhận. Không lưu secret hoặc toàn bộ URL đã ký.</p>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Lần nhận gần nhất</th>
                <th>Mã tham chiếu</th>
                <th>Chữ ký</th>
                <th>Mã phản hồi</th>
                <th>Số lần</th>
            </tr>
            @forelse($callbacks as $callback)
                <tr>
                    <td>{{ $callback->updated_at }}</td>
                    <td class="break-all">{{ $callback->transaction_reference ?? 'Không có' }}</td>
                    <td>{{ $callback->signature_valid ? 'Đã xác minh' : 'Không hợp lệ' }}</td>
                    <td>{{ $callback->response_code }} ·
                        {{ ['00' => 'Đã xử lý', '01' => 'Không tìm thấy đơn', '02' => 'Đã xử lý hoặc đơn hết hạn/hủy', '04' => 'Sai số tiền', '97' => 'Chữ ký/merchant/dữ liệu không hợp lệ', '99' => 'Lỗi xử lý'][$callback->response_code] ?? 'Cần đối soát' }}
                    </td>
                    <td>{{ $callback->attempts }}</td>
            </tr>@empty<tr>
                    <td colspan="5">Chưa nhận IPN. Chưa cấu hình VNPay không có nghĩa là đã thanh toán thành công.
                    </td>
                </tr>
            @endforelse
        </table>
    </div><x-pagination :items="$callbacks" />
</x-layout>
