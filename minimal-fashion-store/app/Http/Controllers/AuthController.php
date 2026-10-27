<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function form(string $mode = 'login')
    {
        return view('auth.form', compact('mode'));
    }

    public function register(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $user = User::create($data);
        Auth::login($user);
        $r->session()->regenerate();
        app(CartService::class)->merge();

        return redirect('/account')->with('success', 'Đăng ký thành công.');
    }

    public function login(Request $r)
    {
        $data = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data, $r->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Thông tin đăng nhập không đúng.']);
        }
        $r->session()->regenerate();
        app(CartService::class)->merge();

        return redirect()->intended('/account');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/');
    }

    public function forgot(Request $r)
    {
        $r->validate(['email' => 'required|email']);
        Password::sendResetLink($r->only('email'));

        return back()->with('success', 'Nếu email đã đăng ký, hướng dẫn đặt lại mật khẩu sẽ được gửi.');
    }

    public function reset(Request $r)
    {
        $r->validate(['email' => 'required|email', 'token' => 'required', 'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->numbers()]]);
        $status = Password::reset($r->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            if (config('session.driver') === 'database') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'Liên kết không hợp lệ hoặc đã hết hạn.']);
        }

        return redirect('/login')->with('success', 'Đã đặt lại mật khẩu.');
    }
}
