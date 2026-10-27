@props(['categories', 'shops'])<form action="/products" class="filters">
    <x-input name="q" label="Tìm kiếm" :value="request('q')" />
    <div class="field"><label for="category_id">Danh mục</label><select id="category_id" name="category_id">
            <option value="">Tất cả</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="field"><label for="shop_id">Cửa hàng</label><select id="shop_id" name="shop_id">
            <option value="">Tất cả shop</option>
            @foreach ($shops as $s)
                <option value="{{ $s->id }}" @selected(request('shop_id') == $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div><x-input name="min" label="Giá từ (₫)" type="number" min="0" :value="request('min')" /><x-input
        name="max" label="Giá đến (₫)" type="number" min="0" :value="request('max')" /><x-input name="size"
        label="Size" :value="request('size')" placeholder="S, M, L…" /><x-input name="color" label="Màu sắc"
        :value="request('color')" placeholder="Đen, Trắng…" />
    <div class="field"><label for="sort">Sắp xếp</label><select id="sort" name="sort">
            @foreach (['new' => 'Mới nhất', 'price_asc' => 'Giá tăng dần', 'price_desc' => 'Giá giảm dần', 'bestselling' => 'Bán chạy'] as $v => $l)
                <option value="{{ $v }}" @selected(request('sort') === $v)>{{ $l }}</option>
            @endforeach
        </select></div><button type="submit" class="btn">Áp dụng bộ lọc</button><a href="/products"
        class="text-link">Xóa bộ lọc</a>
</form>
