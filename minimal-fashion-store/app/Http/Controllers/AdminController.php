<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\ShopApplication;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Voucher;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index()
    {
        return view('admin.index', ['users' => User::orderBy('id')->paginate(20, ['*'], 'users_page'), 'shops' => Shop::with('owner', 'applications')->get(), 'categories' => Category::all(), 'vouchers' => Voucher::all(), 'banners' => Banner::orderBy('position')->get(), 'reviews' => Review::with('product', 'user')->latest()->paginate(20, ['*'], 'reviews_page'), 'logs' => AuditLog::with('user')->latest()->paginate(30, ['*'], 'logs_page')]);
    }

    public function user(Request $r, User $user)
    {
        $r->validate(['role' => 'required|in:admin,seller,customer']);
        abort_if($user->id === $r->user()->id, 422);
        $user->forceFill(['role' => $r->role])->save();
        AuditService::record('user.role', $user, ['role' => $r->role]);

        return back()->with('success', 'Đã cập nhật quyền.');
    }

    public function shop(Request $r, Shop $shop)
    {
        $r->validate(['status' => 'required|in:approved,rejected,pending']);
        DB::transaction(function () use ($shop, $r) {
            $shop->update(['status' => $r->status]);
            ShopApplication::where('shop_id', $shop->id)->update(['status' => $r->status]);
            if ($r->status === 'approved' && $shop->owner->role === 'customer') {
                $shop->owner->forceFill(['role' => 'seller'])->save();
            }AuditService::record('shop.status', $shop, ['status' => $r->status]);
        });

        return back()->with('success', 'Đã cập nhật shop.');
    }

    public function moderate(Request $r, Product $product)
    {
        $r->validate(['status' => 'required|in:approved,rejected,pending']);
        $product->update(['status' => $r->status]);
        AuditService::record('product.moderate', $product, ['status' => $r->status]);

        return back()->with('success', 'Đã kiểm duyệt sản phẩm.');
    }

    public function category(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'slug' => 'required|alpha_dash|max:100|unique:categories']);
        $category = Category::create($data);
        AuditService::record('category.create', $category);

        return back()->with('success', 'Đã thêm danh mục.');
    }

    public function updateCategory(Request $r, Category $category)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'slug' => ['required', 'alpha_dash', 'max:100', Rule::unique('categories')->ignore($category->id)]]);
        $category->update($data);
        AuditService::record('category.update', $category);

        return back()->with('success', 'Đã sửa danh mục.');
    }

    public function deleteCategory(Category $category)
    {
        if ($category->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['category' => 'Danh mục còn sản phẩm nên không thể xóa.']);
        }$category->delete();

        return back()->with('success', 'Đã xóa danh mục.');
    }

    public function voucher(Request $r)
    {
        $data = $r->validate(['code' => 'required|alpha_dash|max:50|unique:vouchers', 'shop_id' => 'nullable|exists:shops,id', 'type' => 'required|in:fixed,percent', 'value' => 'required|integer|min:1|max:1000000000', 'max_discount' => 'nullable|integer|min:1|max:1000000000', 'min_order' => 'required|integer|min:0|max:1000000000', 'usage_limit' => 'required|integer|min:1|max:1000000', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at']);
        if ($data['type'] === 'percent' && $data['value'] > 10000) {
            throw ValidationException::withMessages(['value' => 'Phần trăm tối đa 10000 basis points (100%).']);
        }$data['code'] = strtoupper($data['code']);
        $v = Voucher::create($data);
        AuditService::record('voucher.create', $v);

        return back()->with('success', 'Đã tạo voucher.');
    }

    public function toggleVoucher(Voucher $voucher)
    {
        DB::transaction(function () use ($voucher) {
            $voucher = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $voucher->update(['active' => ! $voucher->active]);
            AuditService::record('voucher.toggle', $voucher);
        });

        return back()->with('success', 'Đã đổi trạng thái voucher.');
    }

    public function banner(Request $r)
    {
        $data = $r->validate(['title' => 'required|string|max:150', 'subtitle' => 'nullable|string|max:255', 'link' => ['required', 'regex:~^/(?!/)[a-zA-Z0-9/?=&_%.-]*$~'], 'position' => 'required|integer|min:0|max:99', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        unset($data['image']);
        if ($r->hasFile('image')) {
            $data['image_path'] = '/storage/'.$r->file('image')->store('banners', 'public');
        }$banner = Banner::create($data);
        AuditService::record('banner.create', $banner);

        return back()->with('success', 'Đã tạo banner.');
    }

    public function updateBanner(Request $r, Banner $banner)
    {
        $data = $r->validate(['title' => 'required|string|max:150', 'subtitle' => 'nullable|string|max:255', 'link' => ['required', 'regex:~^/(?!/)[a-zA-Z0-9/?=&_%.-]*$~'], 'position' => 'required|integer|min:0|max:99', 'active' => 'nullable|boolean', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        unset($data['image']);
        $data['active'] = $r->boolean('active');
        if ($r->hasFile('image')) {
            $data['image_path'] = '/storage/'.$r->file('image')->store('banners', 'public');
        }$banner->update($data);
        AuditService::record('banner.update', $banner);

        return back()->with('success', 'Đã cập nhật banner.');
    }

    public function deleteBanner(Banner $banner)
    {
        AuditService::record('banner.delete', $banner);
        $banner->delete();

        return back()->with('success', 'Đã xóa banner.');
    }

    public function settings(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email', 'phone' => 'required|string|max:30', 'address' => 'required|string|max:255', 'slogan' => 'required|string|max:255', 'collection_title' => 'nullable|string|max:150', 'collection_description' => 'nullable|string|max:500', 'primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'], 'font' => 'required|in:Arial,Georgia,Verdana', 'font_size' => 'required|integer|between:14,20', 'spacing' => 'required|integer|between:12,40', 'radius' => 'required|integer|between:0,24', 'sections' => 'required|array', 'sections.*' => 'nullable|in:categories,new,bestsellers,collection,promotion', 'menu' => 'required|array|max:8', 'menu.*.label' => 'required|string|max:40', 'menu.*.url' => ['required', 'regex:~^/(?!/)[a-zA-Z0-9/?=&_%.-]*$~'], 'logo' => 'nullable|image|mimes:png,jpg,webp|max:2048']);
        $data['sections'] = array_values(array_unique(array_filter($data['sections'])));
        if (! $data['sections']) {
            throw ValidationException::withMessages(['sections' => 'Cần ít nhất một khối nội dung.']);
        }
        unset($data['logo']);
        if ($r->hasFile('logo')) {
            $data['logo'] = '/storage/'.$r->file('logo')->store('branding', 'public');
        } else {
            $data['logo'] = SiteSetting::where('key', 'brand')->first()?->value['logo'] ?? null;
        }
        $setting = SiteSetting::updateOrCreate(['key' => 'brand'], ['value' => $data]);
        AuditService::record('site.settings', $setting);

        return back()->with('success', 'Đã lưu cấu hình giao diện.');
    }

    public function review(Review $review)
    {
        $review->update(['visible' => ! $review->visible]);
        AuditService::record('review.visibility', $review);

        return back()->with('success', 'Đã cập nhật hiển thị đánh giá.');
    }
}
