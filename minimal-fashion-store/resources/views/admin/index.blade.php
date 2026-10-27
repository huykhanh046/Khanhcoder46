<x-layout title="Quản trị hệ thống" :manage="true">
    <h1 class="page-title">Quản trị hệ thống.</h1>
    <p class="muted">Cấu hình tập trung · Phân quyền · Kiểm duyệt · Nhật ký</p>
    <div class="stack mt-8">
        <details open>
            <summary>Cấu hình thương hiệu & trang chủ</summary>@php($brand = $site['brand'] ?? [])<x-form
                action="/admin/settings" method="PUT" enctype="multipart/form-data" class="panel mt-4">
                <div class="two-col">
                    <div>
                        @foreach (['name' => 'Tên cửa hàng', 'email' => 'Email liên hệ', 'phone' => 'Điện thoại', 'address' => 'Địa chỉ', 'slogan' => 'Slogan', 'collection_title' => 'Tiêu đề bộ sưu tập', 'collection_description' => 'Mô tả bộ sưu tập'] as $key => $label)
                            <x-input :name="$key" :label="$label" :value="$brand[$key] ?? ''" :required="!in_array($key, ['collection_title', 'collection_description'])" />
                        @endforeach
                        <div class="field">
                            <label for="logo">Logo (PNG/JPG/WebP)</label><input id="logo" name="logo"
                                type="file" accept="image/png,image/jpeg,image/webp">
                        </div>
                    </div>
                    <div>
                        <div class="inline"><x-input name="primary" label="Màu chủ đạo" type="color" :value="$brand['primary'] ?? '#191919'"
                                required /><x-input name="secondary" label="Màu phụ" type="color" :value="$brand['secondary'] ?? '#f3f2ef'"
                                required /></div>
                        <div class="field"><label for="font">Font</label><select id="font" name="font">
                                @foreach (['Arial', 'Georgia', 'Verdana'] as $font)
                                    <option @selected(($brand['font'] ?? '') === $font)>{{ $font }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="inline"><x-input name="font_size" label="Cỡ chữ (14–20)" type="number"
                                :value="$brand['font_size'] ?? 16" required /><x-input name="spacing" label="Khoảng cách (12–40)"
                                type="number" :value="$brand['spacing'] ?? 24" required /><x-input name="radius"
                                label="Bo góc (0–24)" type="number" :value="$brand['radius'] ?? 0" required /></div>
                        <p class="small mb-3">Thứ tự khối trang chủ (chọn ẩn nếu không cần)</p>
                        @foreach (array_pad($brand['sections'] ?? ['categories', 'new', 'bestsellers', 'collection', 'promotion'], 5, '') as $s)
                            <div class="field"><label for="section-{{ $loop->index }}">Khối
                                    {{ $loop->iteration }}</label><select name="sections[]"
                                    id="section-{{ $loop->index }}">
                                    <option value="" @selected($s === '')>Ẩn khối này</option>
                                    @foreach (['categories' => 'Danh mục', 'new' => 'Sản phẩm mới', 'bestsellers' => 'Bán chạy', 'collection' => 'Bộ sưu tập', 'promotion' => 'Khuyến mãi'] as $key => $label)
                                        <option value="{{ $key }}" @selected($key === $s)>
                                            {{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>
                <h2>Menu điều hướng</h2>
                <div x-data="{ links: {{ Js::from($brand['menu'] ?? [['label' => 'Sản phẩm', 'url' => '/products']]) }} }">
                    <template x-for="(link,index) in links" :key="index">
                        <div class="inline">
                            <div class="field"><label :for="'menu-label-' + index">Nhãn</label><input
                                    :id="'menu-label-' + index" :name="'menu[' + index + '][label]'" x-model="link.label"
                                    required maxlength="40"></div>
                            <div class="field"><label :for="'menu-url-' + index">Đường dẫn nội bộ</label><input
                                    :id="'menu-url-' + index" :name="'menu[' + index + '][url]'" x-model="link.url" required>
                            </div><button type="button" class="text-link" @click="links.splice(index,1)"
                                :disabled="links.length <= 1">Xóa mục</button>
                        </div>
                    </template>
                    <button type="button" class="btn small secondary mb-5"
                        @click="links.push({label:'',url:'/products'})" :disabled="links.length >= 8">Thêm mục
                        menu</button>
                </div>
                <button type="submit" class="btn">
                    Lưu cấu hình</button>
            </x-form>
        </details>
        <details>
            <summary>Banner & nội dung nổi bật</summary>
            <div class="panel mt-4">
                @foreach ($banners as $banner)
                    <div class="row">
                        <div class="flex-1"><x-form :action="'/admin/banners/' . $banner->id" method="PATCH"
                                enctype="multipart/form-data"><x-input name="title" :id="'banner-title-' . $banner->id" label="Tiêu đề"
                                    :value="$banner->title" required /><x-input name="subtitle" :id="'banner-subtitle-' . $banner->id"
                                    label="Nội dung" :value="$banner->subtitle" /><x-input name="link" :id="'banner-link-' . $banner->id"
                                    label="Đường dẫn" :value="$banner->link" required /><x-input name="position"
                                    :id="'banner-position-' . $banner->id" label="Thứ tự" type="number" :value="$banner->position" required />
                                <div class="field"><label for="banner-file-{{ $banner->id }}">Thay ảnh</label><input
                                        id="banner-file-{{ $banner->id }}" name="image" type="file"
                                        accept="image/jpeg,image/png,image/webp"></div><label class="small"><input
                                        name="active" type="checkbox" value="1" @checked($banner->active)> Hiển
                                    thị</label><button type="submit" class="btn small">Lưu banner</button>
                            </x-form></div><x-form :action="'/admin/banners/' . $banner->id" method="DELETE" confirm="Xóa banner này?"><button
                                type="submit" class="text-link">Xóa</button></x-form>
                    </div>
                @endforeach
                <x-form action="/admin/banners" enctype="multipart/form-data">
                    <x-input name="title" label="Tiêu đề" required /><x-input name="subtitle"
                        label="Nội dung" /><x-input name="link" label="Liên kết nội bộ" value="/products"
                        required /><x-input name="position" label="Thứ tự (banner đầu tiên là Hero)" type="number"
                        value="0" required />
                    <div class="field"><label for="banner-image">Ảnh banner</label><input type="file"
                            id="banner-image" name="image" accept="image/jpeg,image/png,image/webp"></div><button
                        type="submit" class="btn">Thêm banner</button>
                </x-form>
            </div>
        </details>
        <details>
            <summary>Tài khoản & phân quyền</summary>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Họ tên / Email</th>
                        <th>Vai trò</th>
                        <th>Cập nhật</th>
                    </tr>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}<br>{{ $user->email }}</td>
                            <td>{{ config('labels.' . $user->role) }}</td>
                            <td>
                                @if ($user->id !== auth()->id())
                                    <x-form :action="'/admin/users/' . $user->id" method="PATCH" class="inline"
                                        confirm="Xác nhận đổi quyền tài khoản?"><select name="role"
                                            aria-label="Vai trò của {{ $user->name }}" class="control">
                                            @foreach (['customer', 'seller', 'admin'] as $role)
                                                <option value="{{ $role }}" @selected($role === $user->role)>
                                                    {{ config('labels.' . $role) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn small">Lưu</button></x-form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            </div><x-pagination :items="$users" />
        </details>
        <details>
            <summary>Duyệt cửa hàng</summary>
            @foreach ($shops as $shop)
                <div class="panel mt-4">
                    <p>{{ $shop->name }} · {{ $shop->owner->email }} · {{ config('labels.' . $shop->status) }}</p>
                    @foreach ($shop->applications as $application)
                        <p class="muted">Đăng ký: {{ $application->description }}</p>
                    @endforeach
                    <x-form :action="'/admin/shops/' . $shop->id" method="PATCH" class="inline mt-3"><select name="status"
                            aria-label="Trạng thái shop {{ $shop->name }}" class="control">
                            <option value="approved">Duyệt</option>
                            <option value="rejected">Từ chối</option>
                            <option value="pending">Chờ duyệt</option>
                        </select><button type="submit" class="btn">Lưu trạng thái</button></x-form>
                </div>
            @endforeach
        </details>
        <details>
            <summary>Danh mục toàn hệ thống</summary>
            <div class="panel mt-4">
                @foreach ($categories as $c)
                    <div class="row"><x-form :action="'/admin/categories/' . $c->id" method="PATCH" class="inline"><x-input
                                name="name" :id="'category-name-' . $c->id" label="Tên" :value="$c->name"
                                required /><x-input name="slug" :id="'category-slug-' . $c->id" label="Slug"
                                :value="$c->slug" required /><button type="submit"
                                class="btn small">Lưu</button></x-form><x-form :action="'/admin/categories/' . $c->id" method="DELETE"
                            confirm="Xóa danh mục trống?"><button type="submit"
                                class="text-link">Xóa</button></x-form></div>
                @endforeach
                <x-form action="/admin/categories" class="inline">
                    <x-input name="name" label="Tên danh mục" required /><x-input name="slug" label="Slug"
                        required /><button type="submit" class="btn">Thêm danh mục</button></x-form>
            </div>
        </details>
        <div class="panel">
            <h2>Voucher & khuyến mãi</h2><a href="/admin/vouchers" class="btn secondary">Quản lý voucher</a>
        </div>
        <details>
            <summary>Kiểm duyệt đánh giá</summary>
            @foreach ($reviews as $review)
                <div class="panel mt-4">
                    <p>{{ $review->user->name }} · {{ $review->product->name }} · {{ $review->rating }}/5</p>
                    <p class="muted">{{ $review->comment }}</p><x-form :action="'/admin/reviews/' . $review->id" method="PATCH"><button
                            type="submit" class="text-link">{{ $review->visible ? 'Ẩn' : 'Hiện' }} đánh
                            giá</button></x-form>
                </div>
            @endforeach
            <x-pagination :items="$reviews" />
        </details>
        <details>
            <summary>Nhật ký quản trị gần nhất</summary>
            <div class="table-wrap">
                <table>
                    <tr>
                        <th>Thời gian</th>
                        <th>Người thực hiện</th>
                        <th>Thao tác</th>
                        <th>Đối tượng</th>
                    </tr>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->user?->email ?? 'Hệ thống' }}</td>
                            <td>{{ $log->action }}</td>
                            <td>{{ $log->subject_type }} #{{ $log->subject_id }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
            <x-pagination :items="$logs" />
        </details>
    </div>
</x-layout>
