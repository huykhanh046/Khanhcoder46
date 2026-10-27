<x-layout :title="$product->name">
    <div class="container">
        <p class="breadcrumbs"><a href="/products">Sản phẩm</a> / {{ $product->category->name }} / {{ $product->name }}
        </p>
        @php($variants = $product->variants->where('active', true)->map(fn($v) => ['id' => $v->id, 'size' => $v->size, 'color' => $v->color, 'price' => $v->price, 'compare' => $v->compare_price, 'stock' => $v->inventory->stock - $v->inventory->reserved])->values())
        <div class="detail" x-data="{ variants: {{ Js::from($variants) }}, size: '', color: '', quantity: 1, get selected() { return this.variants.find(v => v.size === this.size && v.color === this.color) }, image: {{ Js::from($product->images->first()?->path ?? config('store.images.product')) }} }">
            <div><img :src="image" alt="{{ $product->name }}" class="detail-main">
                <div class="thumbnails">
                    @foreach ($product->images as $image)
                        <button type="button" @click="image={{ Js::from($image->path) }}"
                            aria-label="Xem ảnh {{ $loop->iteration }}"><img src="{{ $image->path }}"
                                alt="{{ $image->alt }}"></button>
                    @endforeach
                </div>
            </div>
            <div class="detail-copy">
                <p class="eyebrow">{{ $product->shop->name }} / {{ $product->collection?->name }}</p>
                <h1>{{ $product->name }}</h1>
                <p class="price"
                    x-text="selected ? new Intl.NumberFormat('vi-VN').format(selected.price)+' ₫' : 'Từ {{ number_format($product->variants->where('active', true)->min('price')) }} ₫'">
                </p>
                <p class="muted" x-show="selected && selected.compare>selected.price"
                    x-text="selected ? 'Giá gốc: '+new Intl.NumberFormat('vi-VN').format(selected.compare)+' ₫' : ''">
                </p>
                <div class="divider"></div><x-form action="/cart/items"><input type="hidden" name="product_variant_id"
                        :value="selected?.id || ''">
                    <div class="inline">
                        <div class="field"><label for="size">Kích cỡ</label><select id="size" x-model="size"
                                required @change="quantity=1">
                                <option value="">Chọn size</option>
                                @foreach ($product->variants->where('active', true)->pluck('size')->unique() as $size)
                                    <option>{{ $size }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label for="color">Màu sắc</label><select id="color" x-model="color"
                                required @change="quantity=1">
                                <option value="">Chọn màu</option>
                                @foreach ($product->variants->where('active', true)->pluck('color')->unique() as $color)
                                    <option>{{ $color }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <p class="muted mb-4" aria-live="polite"
                        x-text="selected ? 'Còn '+selected.stock+' sản phẩm' : (size && color ? 'Không có biến thể này' : 'Chọn size và màu để xem tồn kho')">
                    </p>
                    <div class="inline">
                        <div class="field"><label for="quantity">Số lượng</label><input id="quantity" name="quantity"
                                type="number" min="1" :max="Math.min(selected?.stock || 1, 99)"
                                x-model="quantity" required></div><button type="submit" class="btn"
                            :disabled="!selected || selected.stock < 1">Thêm vào giỏ hàng ↗</button>
                    </div>
                </x-form><x-form :action="'/wishlist/' . $product->id"><button type="submit" class="text-link mt-3">♡ Lưu / bỏ lưu yêu
                        thích</button></x-form>
                <div class="divider"></div><x-modal title="Hướng dẫn chọn size">
                    <p class="muted">Số đo tham khảo (cm), có thể chênh lệch theo thiết kế.</p>
                    <table>
                        <tr>
                            <th>Size</th>
                            <th>Ngực</th>
                            <th>Eo</th>
                        </tr>
                        <tr>
                            <td>S</td>
                            <td>82–88</td>
                            <td>64–70</td>
                        </tr>
                        <tr>
                            <td>M</td>
                            <td>88–94</td>
                            <td>70–76</td>
                        </tr>
                        <tr>
                            <td>L</td>
                            <td>94–100</td>
                            <td>76–82</td>
                        </tr>
                    </table>
                </x-modal>
                <details open>
                    <summary>Chi tiết sản phẩm</summary>
                    <p class="small mt-4 whitespace-pre-line">{{ $product->description }}</p>
                </details>
                <details>
                    <summary>Giao hàng & đổi trả</summary>
                    <p class="small">Phí
                        {{ number_format(app(\App\Services\ShippingService::class)->resolve('standard')['fee']) }} ₫ /
                        shop. Gửi yêu cầu
                        đổi/trả trong {{ config('store.return_days') }} ngày sau giao hàng.</p>
                </details>
            </div>
        </div>
        <section class="section">
            <h2 class="text-2xl mb-5">Đánh giá từ khách hàng</h2>
            @forelse($reviews as $review)
                <div class="review"><strong>{{ $review->user->name }}</strong> · {{ $review->rating }}/5<p>
                        {{ $review->comment }}</p>
            </div>@empty<p class="muted">Chưa có đánh giá. Hãy chia sẻ trải nghiệm sau khi nhận hàng.</p>
            @endforelse <x-pagination :items="$reviews" /> @auth<x-form :action="'/products/' . $product->id . '/reviews'"
                class="panel mt-5">
                <div class="field"><label for="rating">Số sao</label><select id="rating" name="rating">
                        @foreach ([5, 4, 3, 2, 1] as $rating)
                            <option>{{ $rating }}</option>
                        @endforeach
                    </select>
                </div><x-input name="comment" label="Nhận xét" required maxlength="2000" /><button type="submit"
                    class="btn">Gửi đánh giá</button>
                <p class="form-note">Chỉ khách đã mua và nhận sản phẩm được đánh giá.</p>
        </x-form>@endauth
    </section>
    <section class="section">
        <div class="section-head">
            <h2>Có thể bạn cũng thích</h2>
        </div><x-product-grid :products="$related" />
    </section>
</div>
</x-layout>
