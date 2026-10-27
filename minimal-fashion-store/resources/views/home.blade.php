<x-layout>
    @php($hero = $banners->first())<section class="hero">
        <div class="hero-copy"><span class="eyebrow">Bộ sưu tập thường ngày / 2026</span>
            <h1>{{ $hero?->title ?? 'Đơn giản. Là chính bạn.' }}</h1>
            <p>{{ $hero?->subtitle ?? ($site['brand']['slogan'] ?? 'Những thiết kế tinh giản, chất liệu thoải mái. Dành cho từng khoảnh khắc thường ngày.') }}
            </p><a href="{{ $hero?->link ?? '/products' }}" class="btn">Khám phá bộ sưu tập <span>↗</span></a><span
                class="eyebrow" style="margin-top:50px;color:#777">Chọn ít hơn. Mặc lâu hơn.</span>
        </div><img src="{{ $hero?->image_path ?? config('store.images.hero') }}" alt="Minh họa bộ sưu tập thời trang tối giản">
    </section>
    <div class="container">
        @foreach ($site['brand']['sections'] ?? ['categories', 'new', 'bestsellers', 'collection', 'promotion'] as $section)
            @if ($section === 'categories')
                <section class="section">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Tủ đồ của bạn</p>
                            <h2>Khám phá theo danh mục</h2>
                        </div>
                    </div>
                    <div class="category-grid">
                        @foreach ($categories as $category)
                            <a class="category-tile"
                                href="/products?category_id={{ $category->id }}">{{ $category->name }}
                                <span>↗</span></a>
                        @endforeach
                    </div>
                </section>
            @elseif($section === 'new')
                <section class="section">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Sản phẩm mới</p>
                            <h2>Mới trong tủ đồ</h2>
                        </div><a class="text-link" href="/products?sort=new">Xem tất cả ↗</a>
                    </div><x-product-grid :products="$new" />
                </section>
            @elseif($section === 'bestsellers')
                <section class="section">
                    <div class="section-head">
                        <div>
                            <p class="eyebrow">Được yêu thích</p>
                            <h2>Những lựa chọn nổi bật</h2>
                        </div><a class="text-link" href="/products?sort=bestselling">Khám phá ↗</a>
                    </div><x-product-grid :products="$best" />
                </section>
            @elseif($section === 'collection' && $collection)
                <section class="editorial"><img src="{{ $collection->image_path ?? config('store.images.collection') }}"
                        alt="{{ $collection->name }}" loading="lazy">
                    <div class="editorial-copy">
                        <p class="eyebrow">Bộ sưu tập / {{ $collection->name }}</p>
                        <h2>{{ $site['brand']['collection_title'] ?? $collection->name }}</h2>
                        <p class="muted">
                            {{ $site['brand']['collection_description'] ?? $collection->description }}
                        </p><a href="/products?{{ http_build_query(['collection' => $collection->slug]) }}"
                            class="text-link inline-block mt-6">Khám phá
                            {{ $collection->name }} ↗</a>
                    </div>
                </section>
            @elseif($section === 'promotion')
                @foreach ($banners->skip(1) as $banner)
                    <section class="promotion">
                        <p class="eyebrow">Dành cho bạn</p>
                        <h2>{{ $banner->title }}</h2>
                        <p class="small">{{ $banner->subtitle }}</p><a href="{{ $banner->link }}"
                            class="text-link inline-block mt-5">Khám phá ưu đãi ↗</a>
                    </section>
                @endforeach
            @endif
        @endforeach
    </div>
</x-layout>
