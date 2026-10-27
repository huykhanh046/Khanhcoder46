<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        ResetPassword::toMailUsing(function ($user, $token) {
            return (new MailMessage)->subject('Đặt lại mật khẩu')->greeting('Xin chào '.$user->name)->line('Bạn nhận được email này vì có yêu cầu đặt lại mật khẩu.')->action('Đặt lại mật khẩu', url(route('password.reset', ['token' => $token, 'email' => $user->email], false)))->line('Liên kết có hiệu lực 60 phút. Nếu không yêu cầu, bạn có thể bỏ qua email.');
        });
        Gate::define('manage', fn ($user) => in_array($user->role, ['admin', 'seller']));
        Gate::define('admin', fn ($user) => $user->role === 'admin');
        View::composer('*', function ($view) {
            $request = request();
            if (! $request->attributes->has('site_settings')) {
                $request->attributes->set('site_settings', SiteSetting::pluck('value', 'key')->all());
            }
            $view->with('site', $request->attributes->get('site_settings'));
        });
        Paginator::useTailwind();
    }
}
