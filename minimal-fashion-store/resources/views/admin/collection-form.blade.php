<x-layout title="Biên tập bộ sưu tập" :manage="true">
    <h1 class="page-title">{{ $collection->exists ? 'Sửa' : 'Thêm' }} bộ sưu tập.</h1><x-form :action="$collection->exists ? '/admin/collections/' . $collection->id : '/admin/collections'"
        :method="$collection->exists ? 'PATCH' : 'POST'" class="panel mt-6" enctype="multipart/form-data">
        <div class="two-col">
            <div><x-input name="name" label="Tên bộ sưu tập" :value="$collection->name" required /><x-input name="slug"
                    label="Slug cho đường dẫn" :value="$collection->slug" required />
                <div class="field"><label for="description">Mô tả</label>
                    <textarea name="description" id="description">{{ old('description', $collection->description) }}</textarea>
                </div><x-input name="position" label="Thứ tự hiển thị" type="number" :value="$collection->position ?? 0"
                    required /><label class="small"><input type="checkbox" name="active" value="1"
                        @checked(old('active', $collection->active ?? true))> Hiển thị bộ sưu tập</label>
            </div>
            <div>
                @if ($collection->image_path)
                    <img src="{{ $collection->image_path }}" alt="{{ $collection->name }}" class="max-h-72 mb-5">
                @endif
                <div class="field"><label for="image">Ảnh bìa (JPG/PNG/WebP, tối đa 4 MB)</label><input
                        id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp"></div>
                <p class="form-note">Gán sản phẩm vào bộ sưu tập trong form sửa sản phẩm.</p>
            </div>
        </div><button type="submit" class="btn mt-5">Lưu bộ sưu tập</button>
    </x-form>
</x-layout>
