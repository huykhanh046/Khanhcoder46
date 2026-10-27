<x-layout title="Quản lý đơn" :manage="true">
    <h1 class="page-title">Đơn hàng.</h1>
    <form class="inline my-5"><x-input name="q" label="Mã đơn / Email khách" :value="request('q')" /><x-input
            name="from" label="Từ ngày" type="date" :value="request('from')" /><x-input name="to" label="Đến ngày"
            type="date" :value="request('to')" />
        <div class="field"><label for="status">Trạng thái</label><select id="status" name="status">
                <option value="">Tất cả</option>
                @foreach (array_keys(\App\Services\OrderService::NEXT) as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ config('labels.' . $s) }}
                    </option>
                @endforeach
            </select>
        </div><button type="submit" class="btn">Lọc</button>
    </form>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Đơn con / Đơn tổng</th>
                <th>Khách hàng</th>
                <th>Shop</th>
                <th>Ngày đặt</th>
                <th>Trạng thái</th>
                <th>Tổng</th>
            </tr>
            @forelse($orders as $sub)
                <tr>
                    <td><a class="text-link" href="/manage/orders/{{ $sub->id }}">#{{ $sub->id }} ·
                            {{ substr($sub->order->number, 0, 8) }}</a></td>
                    <td>{{ $sub->order->user->email }}</td>
                    <td>{{ $sub->shop->name }}</td>
                    <td>{{ $sub->created_at->format('d/m/Y') }}</td>
                    <td>{{ config('labels.' . $sub->status) }}</td>
                    <td>{{ number_format($sub->total) }} ₫</td>
            </tr>@empty<tr>
                    <td colspan="6">Không có đơn phù hợp.</td>
                </tr>
            @endforelse
        </table>
    </div><x-pagination :items="$orders" />
</x-layout>
