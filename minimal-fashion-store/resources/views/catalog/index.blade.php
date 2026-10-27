<x-layout title="Sản phẩm">
    <div class="container">
        <p class="breadcrumbs"><a href="/">Trang chủ</a> / Sản phẩm</p>
        <h1 class="page-title">Tủ đồ tối giản.</h1>
        <p class="muted">{{ $products->total() }} sản phẩm · Những thiết kế cho mỗi ngày</p>
        <div class="catalog">
            <aside><x-filter-sidebar :categories="$categories" :shops="$shops" /></aside>
            <div><x-product-grid :products="$products" /><x-pagination :items="$products" /></div>
        </div>
    </div>
</x-layout>
