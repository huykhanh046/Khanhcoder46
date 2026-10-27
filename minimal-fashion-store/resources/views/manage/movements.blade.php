<x-layout title="Lịch sử kho" :manage="true">
    <h1 class="page-title">Lịch sử tồn kho.</h1>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Thời gian</th>
                <th>SKU</th>
                <th>Thay đổi thực</th>
                <th>Thay đổi giữ chỗ</th>
                <th>Lý do</th>
            </tr>
            @forelse($movements as $m)
                <tr>
                    <td>{{ $m->created_at }}</td>
                    <td>{{ $m->variant->sku }}</td>
                    <td>{{ $m->stock_delta }}</td>
                    <td>{{ $m->reserved_delta }}</td>
                    <td>{{ $m->reason }}</td>
            </tr>@empty<tr>
                    <td colspan="5">Chưa có thay đổi tồn kho.</td>
                </tr>
            @endforelse
        </table>
    </div><x-pagination :items="$movements" />
</x-layout>
