<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function home()
    {
        $new = Product::published()->with(['images', 'variants.inventory'])->latest()->limit(8)->get();
        // Sales ranking uses fulfilled quantities; collected revenue is reported separately from the payment ledger.
        $ids = DB::table('order_items')->join('shop_orders', 'shop_orders.id', '=', 'order_items.shop_order_id')->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')->whereIn('shop_orders.status', ['delivered', 'completed'])->groupBy('product_variants.product_id')->orderByRaw('SUM(order_items.quantity) DESC')->limit(4)->pluck('product_variants.product_id');
        $best = Product::published()->whereIn('id', $ids)->with(['images', 'variants.inventory'])->get()->sortBy(fn ($p) => $ids->search($p->id));

        return view('home', ['new' => $new, 'best' => $best, 'categories' => Category::all(), 'collection' => Collection::where('active', true)->orderBy('position')->first(), 'banners' => Banner::where('active', true)->orderBy('position')->get()]);
    }

    public function index(Request $r)
    {
        $r->validate(['category_id' => 'nullable|integer|exists:categories,id', 'shop_id' => 'nullable|integer|exists:shops,id', 'collection' => 'nullable|string|max:120', 'min' => 'nullable|integer|min:0|max:1000000000', 'max' => 'nullable|integer|min:0|max:1000000000', 'q' => 'nullable|string|max:100', 'size' => 'nullable|string|max:20', 'color' => 'nullable|string|max:50', 'sort' => 'nullable|in:new,price_asc,price_desc,bestselling']);
        $q = Product::published()->with(['images', 'variants.inventory', 'shop'])->withMin(['variants' => fn ($q) => $q->where('active', true)], 'price');
        if ($r->filled('q')) {
            $q->where(fn ($q) => $q->where('name', 'like', '%'.$r->q.'%')->orWhere('description', 'like', '%'.$r->q.'%'));
        }
        foreach (['category_id', 'shop_id'] as $filter) {
            if ($r->filled($filter)) {
                $q->where($filter, $r->input($filter));
            }
        }
        if ($r->filled('collection')) {
            $q->whereHas('collection', fn ($q) => $q->where('active', true)->where(fn ($q) => $q->where('slug', $r->collection)->orWhere('name', $r->collection)));
        }
        if ($r->filled('size') || $r->filled('color') || $r->filled('min') || $r->filled('max')) {
            $q->whereHas('variants', function ($q) use ($r) {
                $q->where('active', true);
                foreach (['size', 'color'] as $f) {
                    if ($r->filled($f)) {
                        $q->where($f, $r->input($f));
                    }
                }if ($r->filled('min')) {
                    $q->where('price', '>=', $r->min);
                }if ($r->filled('max')) {
                    $q->where('price', '<=', $r->max);
                }
            });
        }
        if ($r->sort === 'price_asc') {
            $q->orderBy('variants_min_price');
        } elseif ($r->sort === 'price_desc') {
            $q->orderByDesc('variants_min_price');
        } elseif ($r->sort === 'bestselling') {
            $q->orderByDesc(DB::table('order_items')->selectRaw('COALESCE(SUM(order_items.quantity),0)')->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')->join('shop_orders', 'shop_orders.id', '=', 'order_items.shop_order_id')->whereColumn('product_variants.product_id', 'products.id')->whereIn('shop_orders.status', ['delivered', 'completed']));
        } else {
            $q->latest();
        }

        return view('catalog.index', ['products' => $q->paginate(12)->withQueryString(), 'categories' => Category::all(), 'shops' => Shop::where('status', 'approved')->get()]);
    }

    public function show(Product $product)
    {
        abort_unless(Product::published()->whereKey($product->id)->exists(), 404);
        $product->load(['images', 'variants.inventory', 'shop', 'category', 'collection']);

        return view('catalog.show', ['reviews' => $product->reviews()->where('visible', true)->with('user')->latest()->paginate(10, ['*'], 'reviews_page'), 'product' => $product, 'related' => Product::published()->where('category_id', $product->category_id)->whereKeyNot($product->id)->with(['images', 'variants.inventory'])->limit(4)->get()]);
    }
}
