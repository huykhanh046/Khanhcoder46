<x-layout title="Yêu thích">
    <div class="container">
        <h1 class="page-title mb-8">Những món đồ bạn yêu thích.</h1><x-product-grid :products="$products" /><x-pagination
            :items="$products" />
    </div>
</x-layout>
