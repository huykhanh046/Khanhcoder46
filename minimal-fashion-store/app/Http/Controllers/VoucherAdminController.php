<?php

namespace App\Http\Controllers;

use App\Http\Requests\VoucherRequest;
use App\Models\Shop;
use App\Models\Voucher;
use App\Models\VoucherUsage;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherAdminController extends Controller
{
    public function index(Request $r)
    {
        $r->validate(['q' => 'nullable|string|max:50', 'active' => 'nullable|in:0,1']);
        $q = Voucher::query();
        if ($r->filled('q')) {
            $q->where('code', 'like', '%'.$r->q.'%');
        }if ($r->filled('active')) {
            $q->where('active', $r->boolean('active'));
        }

        return view('admin.vouchers', ['vouchers' => $q->latest()->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('admin.voucher-form', ['voucher' => new Voucher, 'shops' => Shop::all()]);
    }

    public function edit(Voucher $voucher)
    {
        return view('admin.voucher-form', ['voucher' => $voucher, 'shops' => Shop::all()]);
    }

    public function store(VoucherRequest $r)
    {
        $voucher = Voucher::create($r->validated());
        AuditService::record('voucher.create', $voucher);

        return redirect('/admin/vouchers')->with('success', 'Đã tạo voucher.');
    }

    public function update(VoucherRequest $r, Voucher $voucher)
    {
        DB::transaction(function () use ($r, $voucher) {
            $voucher = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            $data = $r->validated();
            if ($data['usage_limit'] < $voucher->used) {
                throw ValidationException::withMessages(['usage_limit' => 'Giới hạn không thể thấp hơn số lượt đã dùng/giữ.']);
            }$voucher->update($data);
            AuditService::record('voucher.update', $voucher, $voucher->getChanges());
        }, 3);

        return redirect('/admin/vouchers')->with('success', 'Đã sửa voucher. Các đơn đã đặt giữ nguyên giảm giá snapshot.');
    }

    public function destroy(Voucher $voucher)
    {
        DB::transaction(function () use ($voucher) {
            $voucher = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            if (VoucherUsage::where('voucher_id', $voucher->id)->exists()) {
                throw ValidationException::withMessages(['voucher' => 'Voucher có lịch sử sử dụng. Hãy tắt thay vì xóa.']);
            }AuditService::record('voucher.delete', $voucher);
            $voucher->delete();
        }, 3);

        return back()->with('success','Đã xóa voucher chưa từng sử dụng.');
    }
}
