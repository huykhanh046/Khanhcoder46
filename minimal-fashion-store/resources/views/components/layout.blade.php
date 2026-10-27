@props(['title' => null, 'manage' => false])
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="Thời trang tối giản cho mỗi ngày. Khám phá sản phẩm, chọn size và mua sắm tại cửa hàng.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' · ' : '' }}{{ $site['brand']['name'] ?? config('store.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php($brand = $site['brand'] ?? [])
    <style>
        :root {
            --primary: {{ $brand['primary'] ?? '#191919' }};
            --secondary: {{ $brand['secondary'] ?? '#f3f2ef' }};
            --font: {{ $brand['font'] ?? 'Arial' }}, sans-serif;
            --font-size: {{ (int) ($brand['font_size'] ?? 16) }}px;
            --space: {{ (int) ($brand['spacing'] ?? 24) }}px;
            --radius: {{ (int) ($brand['radius'] ?? 0) }}px
        }
    </style>
</head>

<body>
    <a class="sr-only focus:not-sr-only" href="#main">Chuyển đến nội dung chính</a><x-header />
    <div class="container" aria-live="polite">
        @if (session('success'))
            <div class="notice" role="status" x-data="{ visible: true }" x-show="visible">{{ session('success') }} <button
                    type="button" @click="visible=false" aria-label="Đóng thông báo" class="float-right">×</button>
            </div>
            @endif @if (session('notice'))
                <div class="notice">{{ session('notice') }}</div>
                @endif @if ($errors->any())
                    <div class="notice errors" role="alert"><strong>Vui lòng kiểm tra lại thông tin:</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
    </div>
    @if ($manage)
        <div class="admin-shell">
            <aside class="sidebar">
                <p class="eyebrow">Không gian quản lý</p>
                @foreach (['/manage' => 'Tổng quan', '/manage/products' => 'Sản phẩm', '/manage/orders' => 'Đơn hàng', '/manage/inventory' => 'Lịch sử kho', '/manage/returns' => 'Đổi / trả'] as $url => $label)
                    <a href="{{ $url }}"
                        class="{{ request()->is(ltrim($url, '/')) ? 'active' : '' }}">{{ $label }}</a>
                @endforeach @can('admin')
                <a href="/admin">Quản trị hệ thống</a><a href="/admin/payments">Thanh toán / đối soát</a><a
                    href="/admin/vouchers">Voucher</a><a href="/admin/collections">Bộ sưu tập</a><a
                    href="/admin/shipping">Vận chuyển</a>
            @endcan
        </aside>
        <main id="main" class="admin-main">{{ $slot }}</main>
</div>@else<main id="main">{{ $slot }}</main>
@endif
<x-footer />
</body>

</html>
