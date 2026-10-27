<x-layout title="Quản lý sản phẩm" :manage="true">
    <div class="section-head">
        <h1 class="page-title">Sản phẩm.</h1><a href="/manage/products/create" class="btn">+ Thêm sản phẩm</a>
    </div>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Sản phẩm</th>
                <th>Shop / Danh mục</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
            @forelse($products as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->shop->name }} / {{ $p->category->name }}</td>
                    <td>{{ config('labels.' . $p->status) }} · {{ $p->active ? 'Đang bật' : 'Đang ẩn' }}</td>
                    <td>
                        <div class="inline"><a class="text-link" href="/manage/products/{{ $p->id }}/edit">Chỉnh
                                sửa</a><x-form :action="'/manage/products/' . $p->id" method="DELETE"
                                confirm="Xóa mềm sản phẩm này? Lịch sử đơn hàng vẫn được giữ."><button type="submit"
                                    class="text-link">Xóa</button></x-form></div>
                        @can('admin')
                            <x-form :action="'/admin/products/' . $p->id . '/moderate'" method="PATCH" class="inline mt-3"><select name="status"
                                    aria-label="Kiểm duyệt {{ $p->name }}" class="control">
                                    <option value="approved">Duyệt</option>
                                    <option value="rejected">Từ chối</option>
                                    <option value="pending">Chờ duyệt</option>
                                </select><button type="submit" class="btn small">Lưu</button></x-form>
                        @endcan
                    </td>
            </tr>@empty<tr>
                    <td colspan="4">Chưa có sản phẩm.</td>
                </tr>
            @endforelse
        </table>
    </div><x-pagination :items="$products" />
</x-layout>
