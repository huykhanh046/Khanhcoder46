<div x-data="{ menu: false, search: false }">
    <div class="topbar">THỜI TRANG TỐI GIẢN · PHONG CÁCH CỦA RIÊNG BẠN</div>
    <header class="header">
        <div class="container nav"><button type="button" class="mobile-toggle" @click="menu=!menu" :aria-expanded="menu"
                aria-label="Mở menu">☰</button><a href="/" class="brand">
                @if (!empty($site['brand']['logo']))
                    <img src="{{ $site['brand']['logo'] }}" alt="{{ $site['brand']['name'] }}"
                        class="max-h-12">@else{{ $site['brand']['name'] ?? config('store.name') }}
                @endif
            </a>
            <nav class="navlinks" aria-label="Menu chính">
                @foreach ($site['brand']['menu'] ?? [['label' => 'Sản phẩm', 'url' => '/products'], ['label' => 'Mới nhất', 'url' => '/products?sort=new'], ['label' => 'Bộ sưu tập', 'url' => '/products?collection=Everyday']] as $link)
                    <a href="{{ $link['url'] }}">{{ $link['label'] }}</a>
                @endforeach
            </nav>
            <div class="navtools"><button type="button" @click="search=!search" :aria-expanded="search"
                    aria-label="Tìm kiếm">⌕ <span class="desktop">Tìm kiếm</span></button><a href="/account"
                    class="desktop">Tài khoản</a><a href="/wishlist" aria-label="Yêu thích">♡</a><a href="/cart">Giỏ
                    hàng ↗</a></div>
        </div>
        <div x-show="menu" x-cloak class="mobile-menu"><a href="/products">Sản phẩm</a><a href="/account">Tài
                khoản</a><a href="/orders">Đơn hàng</a>
            @can('manage')
                <a href="/manage">Quản lý</a>
            @endcan
        </div>
        <form x-show="search" x-cloak action="/products" class="searchbar container inline"><label for="header-search"
                class="small">Bạn đang tìm gì?</label><input id="header-search" name="q" type="search"
                placeholder="Tên sản phẩm, chất liệu…" class="control" style="flex:1"><button type="submit"
                class="btn small">Tìm kiếm</button></form>
    </header>
</div>
