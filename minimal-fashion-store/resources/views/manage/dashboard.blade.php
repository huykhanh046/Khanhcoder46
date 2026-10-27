<x-layout title="Tổng quan" :manage="true">
    <p class="eyebrow">Quản lý cửa hàng</p>
    <h1 class="page-title">Tổng quan kinh doanh.</h1>
    <form class="inline mt-6"><x-input name="from" label="Từ ngày" type="date" :value="$from" /><x-input
            name="to" label="Đến ngày" type="date" :value="$to" /><button type="submit" class="btn">Xem
            báo cáo</button></form>
    <div class="stats">
        @foreach (['revenue' => 'Doanh thu đã thu', 'count' => 'Tổng đơn con', 'completed' => 'Đã hoàn tất', 'cancelled' => 'Đã hủy'] as $key => $label)
            <div class="stat">
                <p class="muted">{{ $label }}</p>
                <strong>{{ number_format($report[$key]) }}{{ $key === 'revenue' ? ' ₫' : '' }}</strong>
            </div>
        @endforeach
    </div>
    <p class="form-note">Doanh thu = khoản đã thu − khoản đã hoàn, theo ngày giao dịch. Đơn COD chưa thu không được cộng
        vào doanh thu. Số đơn được đếm theo ngày đặt đơn con.</p>
    <div class="two-col">
        <div class="panel">
            <h2>Doanh thu theo ngày</h2>
            <div class="chart"><canvas id="revenue-chart" data-values="{{ $report['daily']->toJson() }}" role="img"
                    aria-label="Biểu đồ doanh thu"></canvas></div>
        </div>
        <div class="panel">
            <h2>Đơn theo trạng thái</h2>
            <div class="chart"><canvas id="states-chart"
                    data-values="{{ $report['states']->map(fn($s) => ['label' => config('labels.' . $s->status), 'count' => $s->count])->toJson() }}"
                    role="img" aria-label="Biểu đồ trạng thái đơn"></canvas></div>
        </div>
    </div>
    <div class="two-col">
        <div class="panel">
            <h2>Sản phẩm sắp hết hàng</h2>
            <table>
                <tr>
                    <th>SKU</th>
                    <th>Sản phẩm</th>
                    <th>Khả dụng</th>
                </tr>
                @forelse($report['low'] as $v)
                    <tr>
                        <td>{{ $v->sku }}</td>
                        <td><a href="/manage/products/{{ $v->product_id }}/edit">{{ $v->product->name }}</a></td>
                        <td>{{ $v->inventory->stock - $v->inventory->reserved }}</td>
                </tr>@empty<tr>
                        <td colspan="3">Không có sản phẩm sắp hết hàng.</td>
                    </tr>
                @endforelse
            </table>
        </div>
        <div class="stack">
            <div class="panel">
                <h2>Doanh thu theo tháng</h2>
                @forelse($report['monthly'] as $row)
                    <div class="row small"><span>{{ $row->month }}</span><span>{{ number_format($row->amount) }}
                        ₫</span></div>@empty<p class="muted">Chưa có giao dịch.</p>
                @endforelse
                <h2 class="mt-5">
                    Doanh thu theo shop</h2>
                @forelse($report['byShop'] as $row)
                    <div class="row small"><span>{{ $row->name }}</span><span>{{ number_format($row->amount) }}
                        ₫</span></div>@empty<p class="muted">Chưa có giao dịch trong kỳ.</p>
                @endforelse
            </div>
            <div class="panel">
                <h2>Sản phẩm bán chạy</h2>
                @forelse($report['best'] as $row)
                    <div class="row small">
                        <span>{{ $row->name }}<br>{{ $row->sku }}</span><span>{{ $row->quantity }} đã
                            giao</span>
                </div>@empty<p class="muted">Chưa có đơn đã giao trong kỳ.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layout>
