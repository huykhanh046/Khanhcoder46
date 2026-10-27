<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Collection;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\Shop;
use App\Models\ShopOrder;
use App\Services\AuditService;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\ReportService;
use App\Services\ReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ManageController extends Controller
{
    private function shopId(): ?int
    {
        if (auth()->user()->role === 'admin') {
            return null;
        }
        $shop = auth()->user()->shop;
        abort_unless($shop && $shop->status === 'approved', 403);

        return $shop->id;
    }

    public function dashboard(Request $r, ReportService $service)
    {
        $r->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from']);
        $from = $r->input('from', now()->startOfMonth()->toDateString());
        $to = $r->input('to', now()->toDateString());

        return view('manage.dashboard', ['report' => $service->data($this->shopId(), $from, $to), 'from' => $from, 'to' => $to]);
    }

    public function products()
    {
        $id = $this->shopId();

        return view('manage.products', ['products' => Product::when($id, fn ($q) => $q->where('shop_id', $id))->with('shop', 'category')->latest()->paginate(20)]);
    }

    public function create()
    {
        $this->shopId();

        return view('manage.product-form', ['product' => new Product, 'collections' => Collection::orderBy('name')->get(), 'categories' => Category::all(), 'shops' => Shop::where('status', 'approved')->get()]);
    }

    public function store(ProductRequest $r)
    {
        $data = $r->validated();
        $data['shop_id'] = $this->shopId() ?? $r->integer('shop_id');
        abort_unless(Shop::whereKey($data['shop_id'])->where('status', 'approved')->exists(), 422);
        $data['slug'] = Str::slug($data['name']).'-'.Str::lower(Str::random(8));
        $data['active'] = $r->boolean('active');
        $data['status'] = $r->user()->role === 'admin' ? 'approved' : 'pending';
        $product = Product::create($data);
        AuditService::record('product.create', $product);

        return redirect()->route('manage.products.edit', $product)->with('success', 'Đã tạo sản phẩm. Hãy thêm biến thể và ảnh.');
    }

    public function edit(Product $product)
    {
        Gate::authorize('update', $product);

        return view('manage.product-form', ['product' => $product->load('variants.inventory', 'images'), 'collections' => Collection::orderBy('name')->get(), 'categories' => Category::all(), 'shops' => Shop::all()]);
    }

    public function update(ProductRequest $r, Product $product)
    {
        Gate::authorize('update', $product);
        $data = $r->safe()->except('shop_id');
        $data['active'] = $r->boolean('active');
        if ($r->user()->role !== 'admin') {
            $data['status'] = 'pending';
        }$product->update($data);
        AuditService::record('product.update', $product);

        return back()->with('success', 'Đã lưu sản phẩm.');
    }

    public function destroy(Product $product)
    {
        Gate::authorize('delete', $product);
        $product->delete();
        AuditService::record('product.delete', $product);

        return redirect('/manage/products')->with('success', 'Đã xóa mềm sản phẩm.');
    }

    public function variant(Request $r, Product $product)
    {
        Gate::authorize('update', $product);
        $data = $r->validate(['sku' => 'required|string|max:100|unique:product_variants', 'size' => 'required|string|max:20', 'color' => 'required|string|max:50', 'price' => 'required|integer|min:1|max:1000000000', 'compare_price' => 'nullable|integer|gte:price|max:1000000000', 'stock' => 'required|integer|min:0|max:1000000']);
        if ($product->variants()->where('size', $data['size'])->where('color', $data['color'])->exists()) {
            throw ValidationException::withMessages(['size' => 'Biến thể size/màu đã tồn tại.']);
        }
        DB::transaction(function () use ($product, $data) {
            $stock = $data['stock'];
            unset($data['stock']);
            $v = $product->variants()->create($data);
            $v->inventory()->create([]);
            app(InventoryService::class)->change($v->id, $stock, 0, 'initial:'.$v->id, 'Tồn đầu');
            if (auth()->user()->role !== 'admin') {
                $product->update(['status' => 'pending']);
            }
        });

        return back()->with('success', 'Đã thêm biến thể.');
    }

    public function updateVariant(Request $r, ProductVariant $variant)
    {
        Gate::authorize('update', $variant->product);
        $data = $r->validate(['sku' => ['required', 'string', 'max:100', Rule::unique('product_variants')->ignore($variant->id)], 'size' => 'required|string|max:20', 'color' => 'required|string|max:50', 'price' => 'required|integer|min:1|max:1000000000', 'compare_price' => 'nullable|integer|gte:price|max:1000000000', 'active' => 'nullable|boolean']);
        $data['active'] = $r->boolean('active');
        if ($variant->product->variants()->whereKeyNot($variant->id)->where('size', $data['size'])->where('color', $data['color'])->exists()) {
            throw ValidationException::withMessages(['size' => 'Biến thể size/màu đã tồn tại.']);
        }
        DB::transaction(function () use ($variant, $data) {
            $v = ProductVariant::whereKey($variant->id)->lockForUpdate()->firstOrFail();
            $v->update($data);
            if (auth()->user()->role !== 'admin') {
                $v->product->update(['status' => 'pending']);
            }AuditService::record('variant.update', $v, $data);
        });

        return back()->with('success', 'Đã lưu biến thể.');
    }

    public function inventory(Request $r, ProductVariant $variant, InventoryService $service)
    {
        Gate::authorize('update', $variant->product);
        $r->validate(['delta' => 'required|integer|between:-1000000,1000000', 'reason' => 'required|string|max:255']);
        $service->adjust($variant->id, $r->integer('delta'), $r->reason);
        AuditService::record('inventory.adjust', $variant, ['delta' => $r->integer('delta')]);

        return back()->with('success', 'Đã điều chỉnh kho.');
    }

    public function movements()
    {
        $id = $this->shopId();
        $movements = InventoryMovement::with('variant')->whereHas('variant.product', fn ($q) => $q->when($id, fn ($q) => $q->where('shop_id', $id)))->latest()->paginate(30);

        return view('manage.movements', compact('movements'));
    }

    public function images(Request $r, Product $product)
    {
        Gate::authorize('update', $product);
        $r->validate(['images' => 'required|array|max:8', 'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096']);
        foreach ($r->file('images') as $file) {
            $path = $file->store('products', 'public');
            $product->images()->create(['path' => '/storage/'.$path, 'alt' => $product->name, 'position' => $product->images()->count()]);
        }
        if (auth()->user()->role !== 'admin') {
            $product->update(['status' => 'pending']);
        }

        return back()->with('success', 'Đã tải ảnh lên.');
    }

    public function deleteImage(ProductImage $image)
    {
        Gate::authorize('update', $image->product);
        if (str_starts_with($image->path, '/storage/products/')) {
            Storage::disk('public')->delete(substr($image->path, 9));
        }$image->delete();

        return back()->with('success', 'Đã xóa ảnh.');
    }

    public function orders(Request $r)
    {
        $id = $this->shopId();
        $r->validate(['status' => 'nullable|in:pending,confirmed,packing,shipping,delivered,completed,cancelled', 'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from', 'q' => 'nullable|string|max:100']);
        $q = ShopOrder::with('order.user', 'shop')->when($id, fn ($q) => $q->where('shop_id', $id));
        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }if ($r->filled('from')) {
            $q->whereDate('created_at', '>=', $r->from);
        }if ($r->filled('to')) {
            $q->whereDate('created_at', '<=', $r->to);
        }
        if ($r->filled('q')) {
            $q->whereHas('order', fn ($q) => $q->where('number', 'like', '%'.$r->q.'%')->orWhereHas('user', fn ($q) => $q->where('email', 'like', '%'.$r->q.'%')));
        }

        return view('manage.orders', ['orders' => $q->latest()->paginate(20)->withQueryString()]);
    }

    public function showOrder(ShopOrder $shopOrder)
    {
        Gate::authorize('update', $shopOrder);

        return view('manage.order', ['sub' => $shopOrder->load('items', 'order.payment', 'transactions', 'shop'), 'next' => OrderService::NEXT[$shopOrder->status]]);
    }

    public function transition(Request $r, ShopOrder $shopOrder, OrderService $service)
    {
        Gate::authorize('update', $shopOrder);
        $r->validate(['status' => 'required|string', 'tracking_number' => 'nullable|string|max:100']);
        $service->transition($shopOrder, $r->status, $r->tracking_number);

        return back()->with('success', 'Đã cập nhật trạng thái đơn.');
    }

    public function money(Request $r, ShopOrder $shopOrder, OrderService $service)
    {
        Gate::authorize('admin');
        $r->validate(['type' => 'required|in:capture,refund', 'amount' => 'required|integer|min:1', 'reference' => 'required|string|max:100', 'confirmed' => 'accepted']);
        $service->recordMoney($shopOrder, $r->type, $r->reference, $r->integer('amount'));

        return back()->with('success', 'Đã ghi nhận giao dịch thực tế vào sổ thu/hoàn.');
    }

    public function returns()
    {
        $id = $this->shopId();

        return view('manage.returns', ['returns' => ReturnRequest::with('item.shopOrder.shop', 'user')->whereHas('item.shopOrder', fn ($q) => $q->when($id, fn ($q) => $q->where('shop_id', $id)))->latest()->paginate(20)]);
    }

    public function updateReturn(Request $r, ReturnRequest $returnRequest)
    {
        Gate::authorize('update', $returnRequest->item->shopOrder);
        $data = $r->validate(['status' => 'required|in:approved,rejected,received,resolved', 'response' => 'required|string|max:2000']);
        app(ReturnService::class)->transition($returnRequest, $data['status'], $data['response']);

        return back()->with('success', 'Đã cập nhật yêu cầu. Hoàn tiền và nhập kho cần xác nhận riêng.');
    }

    public function restockReturn(Request $r, ReturnRequest $returnRequest)
    {
        Gate::authorize('update', $returnRequest->item->shopOrder);
        $r->validate(['confirmed' => 'accepted']);
        app(ReturnService::class)->restock($returnRequest);

        return back()->with('success', 'Đã nhập lại kho một lần cho toàn bộ số lượng của dòng hàng.');
    }
}
