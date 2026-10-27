<x-layout title="Biên tập voucher" :manage="true">
    <h1 class="page-title">{{ $voucher->exists ? 'Sửa' : 'Thêm' }} voucher.</h1><x-form :action="$voucher->exists ? '/admin/vouchers/' . $voucher->id : '/admin/vouchers'" :method="$voucher->exists ? 'PATCH' : 'POST'"
        class="panel mt-6">
        <div class="two-col">
            <div><x-input name="code" label="Mã voucher" :value="$voucher->code" required />
                <div class="field"><label for="shop_id">Phạm vi áp dụng</label><select name="shop_id" id="shop_id">
                        <option value="">Toàn hệ thống</option>
                        @foreach ($shops as $shop)
                            <option value="{{ $shop->id }}" @selected(old('shop_id', $voucher->shop_id) == $shop->id)>{{ $shop->name }}
                            </option>
                        @endforeach
                    </select></div>
                <div class="field"><label for="type">Loại giảm giá</label><select name="type" id="type">
                        <option value="fixed" @selected(old('type', $voucher->type) === 'fixed')>Số tiền cố định</option>
                        <option value="percent" @selected(old('type', $voucher->type) === 'percent')>Phần trăm (100 = 1%)</option>
                    </select></div><x-input name="value" label="Giá trị giảm (VND hoặc basis points)" type="number"
                    :value="$voucher->value" required /><x-input name="max_discount"
                    label="Số tiền giảm tối đa (bỏ trống nếu không giới hạn)" type="number" :value="$voucher->max_discount" />
            </div>
            <div><x-input name="min_order" label="Giá trị đơn tối thiểu (₫)" type="number" :value="$voucher->min_order ?? 0"
                    required /><x-input name="usage_limit" label="Giới hạn số lượt" type="number" :value="$voucher->usage_limit ?? 100"
                    required /><x-input name="starts_at" label="Bắt đầu" type="datetime-local" :value="$voucher->starts_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')"
                    required /><x-input name="ends_at" label="Kết thúc" type="datetime-local" :value="$voucher->ends_at?->format('Y-m-d\TH:i') ?? now()->addMonth()->format('Y-m-d\TH:i')"
                    required />
                <p class="form-note">Đã dùng/giữ: {{ $voucher->used ?? 0 }} lượt. Không được hạ giới hạn dưới số này. Các
                    đơn đã đặt giữ nguyên giảm giá.</p>
            </div>
        </div><button class="btn" type="submit">Lưu voucher</button><a href="/admin/vouchers"
            class="text-link ml-4">Quay lại</a>
    </x-form>
</x-layout>
