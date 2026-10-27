<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\ShopApplication;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\ReturnService;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(Request $r)
    {
        return view('account', ['user' => $r->user(), 'addresses' => $r->user()->addresses]);
    }

    public function update(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:users,email,'.$r->user()->id]);
        $r->user()->update($data);

        return back()->with('success', 'Đã lưu hồ sơ.');
    }

    public function password(Request $r)
    {
        $r->validate(['current_password' => 'required|current_password', 'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()]]);
        $r->user()->forceFill(['password' => Hash::make($r->password), 'remember_token' => Str::random(60)])->save();
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $r->user()->id)->where('id', '!=', $r->session()->getId())->delete();
        }

        return back()->with('success', 'Đã đổi mật khẩu.');
    }

    public function address(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'phone' => 'required|string|max:20', 'address' => 'required|string|max:255', 'city' => 'required|string|max:100']);
        $r->user()->addresses()->create($data);

        return back()->with('success', 'Đã lưu địa chỉ.');
    }

    public function updateAddress(Request $r, Address $address)
    {
        abort_unless($address->user_id === $r->user()->id, 403);
        $data = $r->validate(['name' => 'required|string|max:100', 'phone' => 'required|string|max:20', 'address' => 'required|string|max:255', 'city' => 'required|string|max:100']);
        $address->update($data);

        return back()->with('success', 'Đã cập nhật địa chỉ.');
    }

    public function deleteAddress(Request $r, Address $address)
    {
        abort_unless($address->user_id === $r->user()->id, 403);
        $address->delete();

        return back()->with('success', 'Đã xóa địa chỉ.');
    }

    public function wishlist(Request $r)
    {
        return view('wishlist', ['products' => Product::published()->whereIn('id', Wishlist::where('user_id', $r->user()->id)->select('product_id'))->with(['images', 'variants.inventory'])->paginate(12)]);
    }

    public function toggleWishlist(Request $r, Product $product)
    {
        $q = Wishlist::where('user_id', $r->user()->id)->where('product_id', $product->id);
        if ($q->exists()) {
            $q->delete();
        } else {
            Wishlist::create(['user_id' => $r->user()->id, 'product_id' => $product->id]);
        }

        return back()->with('success', 'Đã cập nhật danh sách yêu thích.');
    }

    public function review(Request $r, Product $product)
    {
        $data = $r->validate(['rating' => 'required|integer|between:1,5', 'comment' => 'required|string|max:2000']);
        app(ReviewService::class)->save($r->user(), $product, $data);

        return back()->with('success', 'Đã lưu đánh giá của bạn.');
    }

    public function returnItem(Request $r, OrderItem $item)
    {
        $data = $r->validate(['type' => 'required|in:return,exchange', 'reason' => 'required|string|max:2000']);
        app(ReturnService::class)->request($r->user(), $item, $data);

        return back()->with('success', 'Đã gửi yêu cầu. Chưa có khoản hoàn tiền nào được thực hiện.');
    }

    public function applyShop(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'description' => 'required|string|max:2000']);
        DB::transaction(function () use ($data, $r) {
            $user = User::whereKey($r->user()->id)->lockForUpdate()->first();
            abort_if($user->shop, 422);
            $shop = Shop::create(['owner_id' => $user->id, 'name' => $data['name'], 'slug' => Str::slug($data['name']).'-'.$user->id]);
            ShopApplication::create(['user_id' => $user->id, 'shop_id' => $shop->id, 'description' => $data['description']]);
        });

        return back()->with('success', 'Đã gửi đăng ký shop, đang chờ quản trị viên duyệt.');
    }
}
