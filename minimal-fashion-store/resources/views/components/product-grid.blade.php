@props(['products'])@if ($products->count())
    <div class="grid-products">
        @foreach ($products as $product)
            <x-product-card :product="$product" />
        @endforeach
</div>@else<div class="empty">
        <p>Chưa có sản phẩm phù hợp.</p><a href="/products" class="text-link">Xem tất cả sản phẩm</a>
    </div>
@endif
