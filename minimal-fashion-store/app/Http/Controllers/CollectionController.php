<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CollectionController extends Controller
{
    public function publicIndex()
    {
        return view('collections', ['collections' => Collection::where('active', true)->withCount(['products' => fn ($q) => $q->published()])->orderBy('position')->paginate(12)]);
    }

    public function index()
    {
        return view('admin.collections', ['collections' => Collection::withCount('products')->orderBy('position')->paginate(15)]);
    }

    public function create()
    {
        return view('admin.collection-form', ['collection' => new Collection]);
    }

    public function edit(Collection $collection)
    {
        return view('admin.collection-form', compact('collection'));
    }

    private function data(Request $r, ?Collection $collection = null): array
    {
        $data = $r->validate(['name' => 'required|string|max:100', 'slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('collections')->ignore($collection?->id)], 'description' => 'nullable|string|max:2000', 'position' => 'required|integer|between:0,999', 'active' => 'nullable|boolean', 'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096']);
        unset($data['image']);
        $data['active'] = $r->boolean('active');
        if ($r->hasFile('image')) {
            $data['image_path'] = '/storage/'.$r->file('image')->store('collections', 'public');
        }

return $data;
    }

    public function store(Request $r)
    {
        $collection = Collection::create($this->data($r));
        AuditService::record('collection.create', $collection);

        return redirect('/admin/collections')->with('success', 'Đã tạo bộ sưu tập.');
    }

    public function update(Request $r, Collection $collection)
    {
        $collection->update($this->data($r, $collection));
        AuditService::record('collection.update', $collection);

        return redirect('/admin/collections')->with('success', 'Đã cập nhật bộ sưu tập.');
    }

    public function destroy(Collection $collection)
    {
        AuditService::record('collection.delete', $collection);
        $collection->delete();

        return back()->with('success','Đã xóa nhóm bộ sưu tập. Các sản phẩm vẫn được giữ.');
    }
}
