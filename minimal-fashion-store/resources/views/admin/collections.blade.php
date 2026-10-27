<x-layout title="Bộ sưu tập" :manage="true">
    <div class="section-head">
        <h1 class="page-title">Bộ sưu tập.</h1><a href="/admin/collections/create" class="btn">Thêm bộ sưu tập</a>
    </div>
    <p class="muted">Bộ sưu tập đang bật có thứ tự nhỏ nhất được chọn cho khối nổi bật trên trang chủ.</p>
    <div class="table-wrap mt-6">
        <table>
            <tr>
                <th>Tên</th>
                <th>Sản phẩm</th>
                <th>Thứ tự / Hiển thị</th>
                <th>Thao tác</th>
            </tr>
            @forelse($collections as $collection)
                <tr>
                    <td>{{ $collection->name }}</td>
                    <td>{{ $collection->products_count }}</td>
                    <td>{{ $collection->position }} · {{ $collection->active ? 'Đang bật' : 'Đang ẩn' }}</td>
                    <td>
                        <div class="inline"><a href="/admin/collections/{{ $collection->id }}/edit"
                                class="text-link">Sửa</a><x-form :action="'/admin/collections/' . $collection->id" method="DELETE"
                                confirm="Xóa nhóm bộ sưu tập? Các sản phẩm vẫn được giữ."><button type="submit"
                                    class="text-link">Xóa</button></x-form></div>
                    </td>
            </tr>@empty<tr>
                    <td colspan="4">Chưa có bộ sưu tập.</td>
                </tr>
            @endforelse
        </table>
    </div><x-pagination :items="$collections" />
</x-layout>
