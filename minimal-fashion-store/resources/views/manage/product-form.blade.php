<x-layout title="Biên tập sản phẩm" :manage="true">
    <h1 class="page-title">{{ $product->exists ? 'Chỉnh sửa' : 'Thêm' }} sản phẩm.</h1>
    <div class="two-col"><x-form :action="$product->exists ? '/manage/products/' . $product->id : '/manage/products'" :method="$product->exists ? 'PATCH' : 'POST'" class="panel self-start"><x-input name="name"
                label="Tên sản phẩm" :value="$product->name" required />
            <div class="field"><label for="category_id">Danh mục</label><select id="category_id" name="category_id"
                    required>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @can('admin')@unless ($product->exists)
            <div class="field"><label for="shop_id">Shop</label><select name="shop_id" id="shop_id">
                    @foreach ($shops as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            @endunless @endcan
            <div class="field"><label for="collection_id">Bộ sưu tập</label><select id="collection_id"
                    name="collection_id">
                    <option value="">Không thuộc bộ sưu tập</option>
                    @foreach ($collections as $collection)
                        <option value="{{ $collection->id }}" @selected(old('collection_id', $product->collection_id) == $collection->id)>{{ $collection->name }}
                        </option>
                    @endforeach
                </select></div>
            <div class="field"><label for="description">Mô tả</label>
                <textarea name="description" id="description" required>{{ old('description', $product->description) }}</textarea>
            </div><label class="small"><input type="checkbox" name="active" value="1"
                    @checked(old('active', $product->active ?? true))> Bật bán sản phẩm</label><button type="submit"
                class="btn mt-5 w-full">Lưu sản phẩm</button>
            <p class="form-note">Thay đổi từ Seller sẽ chuyển sản phẩm về chờ duyệt.</p>
        </x-form>
        @if ($product->exists)
            <div class="stack"><x-form :action="'/manage/products/' . $product->id . '/images'" enctype="multipart/form-data" class="panel">
                    <h2>Ảnh sản phẩm</h2>
                    <div class="field"><label for="images">Chọn tối đa 8 ảnh (JPG/PNG/WebP, 4 MB/ảnh)</label><input
                            id="images" type="file" name="images[]" multiple
                            accept="image/jpeg,image/png,image/webp" required></div><button type="submit"
                        class="btn">Tải ảnh lên</button>
                </x-form>
                <div class="inline">
                    @foreach ($product->images as $image)
                        <div><img src="{{ $image->path }}" alt="{{ $image->alt }}" class="w-24"><x-form
                                :action="'/manage/images/' . $image->id" method="DELETE" confirm="Xóa ảnh này?"><button type="submit"
                                    class="text-link">Xóa ảnh</button></x-form></div>
                    @endforeach
                </div>
                <x-form :action="'/manage/products/' . $product->id . '/variants'" class="panel">
                    <h2>Thêm biến thể</h2>
                    <div class="inline"><x-input name="sku" label="SKU duy nhất" required /><x-input name="size"
                            label="Size" required /><x-input name="color" label="Màu" required /></div>
                    <div class="inline"><x-input name="price" label="Giá bán (₫)" type="number" min="1"
                            required /><x-input name="compare_price" label="Giá gốc (₫)" type="number"
                            min="1" /><x-input name="stock" label="Tồn đầu" type="number" value="0"
                            min="0" required /></div><button type="submit" class="btn">Thêm biến thể</button>
                </x-form>
            </div>
        @endif
    </div>
    @if ($product->exists)
        <h2 class="text-xl my-8">Biến thể & tồn kho</h2>
        <div class="table-wrap">
            <table>
                <tr>
                    <th>SKU / Biến thể</th>
                    <th>Giá & trạng thái</th>
                    <th>Kho thực / Giữ / Khả dụng</th>
                    <th>Điều chỉnh tồn</th>
                </tr>
                @foreach ($product->variants as $v)
                    <tr>
                        <td>{{ $v->sku }}<br>{{ $v->size }} / {{ $v->color }}</td>
                        <td><x-form :action="'/manage/variants/' . $v->id" method="PATCH"><x-input name="sku" :id="'sku-' . $v->id"
                                    label="SKU" :value="$v->sku" required /><x-input name="size"
                                    :id="'size-' . $v->id" label="Size" :value="$v->size" required /><x-input
                                    name="color" :id="'color-' . $v->id" label="Màu" :value="$v->color"
                                    required /><x-input name="price" :id="'price-' . $v->id" label="Giá bán" type="number"
                                    :value="$v->price" required /><x-input name="compare_price" :id="'compare-' . $v->id"
                                    label="Giá gốc" type="number" :value="$v->compare_price" /><label><input type="checkbox"
                                        name="active" value="1" @checked($v->active)> Bật
                                    bán</label><button type="submit" class="btn small">Lưu</button></x-form></td>
                        <td>{{ $v->inventory->stock }} / {{ $v->inventory->reserved }} /
                            {{ $v->inventory->stock - $v->inventory->reserved }}</td>
                        <td><x-form :action="'/manage/variants/' . $v->id . '/inventory'"><x-input name="delta" :id="'delta-' . $v->id"
                                    label="Số lượng tăng (+) / giảm (−)" type="number" required /><x-input
                                    name="reason" :id="'reason-' . $v->id" label="Lý do" required /><button type="submit"
                                    class="btn small">Điều chỉnh</button></x-form></td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif
</x-layout>
