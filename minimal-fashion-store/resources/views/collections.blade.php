<x-layout title="Bộ sưu tập">
    <div class="container">
        <p class="breadcrumbs"><a href="/">Trang chủ</a> / Bộ sưu tập</p>
        <h1 class="page-title">Phong cách cho mỗi ngày.</h1>
        <div class="two-col">
            @forelse($collections as $collection)
                <article class="panel"><a href="/products?{{ http_build_query(['collection' => $collection->slug]) }}"><img
                            src="{{ $collection->image_path ?? config('store.images.collection') }}" alt="{{ $collection->name }}"
                            class="w-full mb-5" loading="lazy">
                        <p class="eyebrow">{{ $collection->products_count }} sản phẩm</p>
                        <h2>{{ $collection->name }}</h2>
                        <p class="muted">{{ $collection->description }}</p><span
                            class="text-link inline-block mt-4">Khám phá bộ sưu tập →</span>
                </a></article>@empty<div class="empty">Chưa có bộ sưu tập đang mở bán.</div>
            @endforelse
        </div><x-pagination :items="$collections" />
    </div>
</x-layout>
