<x-layout title="Voucher" :manage="true">
    <div class="section-head">
        <h1 class="page-title">Voucher & ưu đãi.</h1><a href="/admin/vouchers/create" class="btn">Thêm voucher</a>
    </div>
    <form class="inline"><x-input name="q" label="Tìm mã voucher" :value="request('q')" />
        <div class="field"><label for="active">Hiển thị</label><select id="active" name="active">
                <option value="">Tất cả</option>
                <option value="1" @selected(request('active') === '1')>Đang bật</option>
                <option value="0" @selected(request('active') === '0')>Đang tắt</option>
            </select></div><button class="btn" type="submit">Lọc</button>
    </form>
    <div class="table-wrap">
        <table>
            <tr>
                <th>Mã / Phạm vi</th>
                <th>Giảm giá</th>
                <th>Thời hạn</th>
                <th>Lượt dùng</th>
                <th>Thao tác</th>
            </tr>
            @forelse($vouchers as $v)
                <tr>
                    <td><strong>{{ $v->code }}</strong><br>{{ $v->shop_id ? 'Shop #' . $v->shop_id : 'Toàn hệ thống' }}
                    </td>
                    <td>{{ $v->type === 'percent' ? $v->value / 100 . '%' : number_format($v->value) . ' ₫' }}<br>Đơn từ
                        {{ number_format($v->min_order) }} ₫</td>
                    <td>{{ $v->starts_at->format('d/m/Y H:i') }}<br>{{ $v->ends_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $v->used }}/{{ $v->usage_limit }}<br>{{ $v->active ? 'Đang bật' : 'Đang tắt' }}</td>
                    <td>
                        <div class="inline"><a href="/admin/vouchers/{{ $v->id }}/edit"
                                class="text-link">Sửa</a><x-form :action="'/admin/vouchers/' . $v->id . '/toggle'" method="PATCH"><button type="submit"
                                    class="text-link">Bật / tắt</button></x-form><x-form :action="'/admin/vouchers/' . $v->id"
                                method="DELETE"
                                confirm="Chỉ xóa được voucher chưa có lịch sử sử dụng. Tiếp tục?"><button type="submit"
                                    class="text-link">Xóa</button></x-form></div>
                    </td>
            </tr>@empty<tr>
                    <td colspan="5">Không có voucher phù hợp.</td>
                </tr>
            @endforelse
        </table>
    </div><x-pagination :items="$vouchers" />
</x-layout>
