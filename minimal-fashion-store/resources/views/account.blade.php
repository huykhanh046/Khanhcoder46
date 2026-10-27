<x-layout title="Tài khoản">
    <div class="container">
        <div class="section-head">
            <div>
                <p class="eyebrow mt-8">Tài khoản của bạn</p>
                <h1 class="page-title">Xin chào, {{ $user->name }}.</h1>
            </div><x-form action="/logout"><button type="submit" class="text-link">Đăng xuất</button></x-form>
        </div>
        <nav class="inline mb-8"><a href="/orders" class="btn">Đơn hàng</a><a href="/wishlist"
                class="btn secondary">Yêu thích</a>
            @can('manage')
                <a href="/manage" class="btn secondary">Quản lý cửa hàng</a>
            @endcan
        </nav>
        <div class="two-col">
            <div class="stack"><x-form action="/account" method="PATCH" class="panel">
                    <h2>Thông tin cá nhân</h2><x-input name="name" label="Họ và tên" :value="$user->name"
                        required /><x-input name="email" label="Email" type="email" :value="$user->email"
                        required /><button type="submit" class="btn">Lưu thông tin</button>
                </x-form><x-form action="/account/password" method="PUT" class="panel">
                    <h2>Đổi mật khẩu</h2><x-input name="current_password" label="Mật khẩu hiện tại" type="password"
                        required /><x-input name="password" label="Mật khẩu mới (tối thiểu 10 ký tự, gồm chữ và số)"
                        type="password" required /><x-input name="password_confirmation" label="Nhập lại mật khẩu mới"
                        type="password" required /><button type="submit" class="btn">Đổi mật khẩu</button>
                </x-form>
                <div class="panel">
                    <h2>Đăng ký bán hàng</h2>
                    @if ($user->shop)
                    <p>{{ $user->shop->name }} · {{ config('labels.' . $user->shop->status) }}</p>@else<x-form
                            action="/shop-applications"><x-input name="name" label="Tên cửa hàng" required /><x-input
                                name="description" label="Giới thiệu cửa hàng" required /><button type="submit"
                                class="btn">Gửi đăng ký</button></x-form>
                    @endif
                </div>
            </div>
            <div class="stack">
                <div class="panel">
                    <h2>Sổ địa chỉ</h2>
                    @forelse($addresses as $address)
                        <div class="row">
                            <div><strong class="small">{{ $address->name }}</strong>
                                <p class="muted">{{ $address->phone }}<br>{{ $address->address }}, {{ $address->city }}
                                </p>
                            </div>
                            <div><x-modal title="Sửa địa chỉ"><x-form :action="'/addresses/' . $address->id" method="PATCH">
                                        @foreach (['name' => 'Họ tên', 'phone' => 'Điện thoại', 'address' => 'Địa chỉ', 'city' => 'Tỉnh / thành phố'] as $field => $label)
                                            <x-input :name="$field" :id="'address-' . $address->id . '-' . $field" :label="$label"
                                                :value="$address->$field" required />
                                        @endforeach
                                        <button type="submit" class="btn">
                                            Lưu địa chỉ</button>
                                    </x-form></x-modal><x-form :action="'/addresses/' . $address->id" method="DELETE"
                                    confirm="Xóa địa chỉ này?"><button type="submit"
                                        class="text-link">Xóa</button></x-form></div>
                    </div>@empty<p class="muted">Chưa có địa chỉ đã lưu.</p>
                    @endforelse
                </div>
                <x-form action="/addresses" class="panel">
                    <h2>Thêm địa chỉ</h2>
                    @foreach (['name' => 'Họ và tên', 'phone' => 'Điện thoại', 'address' => 'Địa chỉ', 'city' => 'Tỉnh / thành phố'] as $key => $label)
                        <x-input :name="$key" :label="$label" required />
                    @endforeach
                    <button type="submit" class="btn">Lưu địa chỉ</button>
                </x-form>
            </div>
        </div>
    </div>
</x-layout>
