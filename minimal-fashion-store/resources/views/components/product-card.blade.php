@props(['product'])@php($variants = $product->variants->where('active', true))@php($variant = $variants->sortBy('price')->first())@php($available = $variants->sum(fn($v) => max(0, ($v->inventory?->stock ?? 0) - ($v->inventory?->reserved ?? 0))))
<article class="product-card">
    <a href="{{ route('products.show', $product) }}">
        <div class="product-image"><img src="{{ $product->images->first()?->path ?? config('store.images.product') }}"
                alt="{{ $product->images->first()?->alt ?? $product->name }}" loading="lazy">
            @if (!$available)
                <span class="badge">HẾT HÀNG</span>
            @elseif($variant && $variant->compare_price > $variant->price)
                <span class="badge">ƯU ĐÃI</span>
            @endif
        </div>
        <h3>{{ $product->name }}</h3>
        <p class="price">{{ number_format($variant?->price ?? 0) }} ₫ @if ($variant?->compare_price > $variant?->price)
                <del>{{ number_format($variant->compare_price) }} ₫</del>
            @endif
        </p>
        <p class="muted">{{ $variants->pluck('color')->unique()->take(3)->join(' · ') }}</p>
    </a>
</article>
