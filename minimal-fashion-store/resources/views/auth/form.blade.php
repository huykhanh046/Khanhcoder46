@php($titles = ['login' => 'Chào mừng trở lại.', 'register' => 'Tạo tài khoản của bạn.', 'forgot' => 'Quên mật khẩu?', 'reset' => 'Đặt lại mật khẩu.'])<x-layout :title="$titles[$mode]">
    <div class="auth-box">
        <p class="eyebrow">Không gian của bạn</p>
        <h1 class="page-title">{{ $titles[$mode] }}</h1>
        <p class="muted mb-8">Quản lý đơn hàng và những món đồ bạn yêu thích.</p><x-form :action="[
            'login' => '/login',
            'register' => '/register',
            'forgot' => '/forgot-password',
            'reset' => '/reset-password',
        ][$mode]">
            @if ($mode === 'register')
                <x-input name="name" label="Họ và tên" required autocomplete="name" />
            @endif
            <x-input name="email" label="Email" type="email" :value="request('email')" required autocomplete="email" />
            @if (in_array($mode, ['login', 'register', 'reset']))
                <x-input name="password" label="Mật khẩu" type="password" required
                    autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}" />
                @endif @if (in_array($mode, ['register', 'reset']))
                    <p class="form-note mb-4">Ít nhất 10 ký tự, gồm chữ và số.</p><x-input name="password_confirmation"
                        label="Nhập lại mật khẩu" type="password" required />
                    @endif @if ($mode === 'reset')
                        <input type="hidden" name="token" value="{{ $token }}">
                        @endif @if ($mode === 'login')
                            <label class="small"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng
                                nhập</label>
                        @endif
                        <button type="submit"
                            class="btn w-full mt-5">{{ ['login' => 'Đăng nhập', 'register' => 'Đăng ký', 'forgot' => 'Gửi hướng dẫn', 'reset' => 'Lưu mật khẩu'][$mode] }}</button>
        </x-form>
        <div class="row small"><a href="/login">Đăng nhập</a><a href="/register">Đăng ký</a><a
                href="/forgot-password">Quên mật khẩu</a></div>
    </div>
</x-layout>
