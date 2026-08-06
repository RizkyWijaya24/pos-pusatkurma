<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponAdminController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->get();
        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => 'required|string|max:30|unique:coupons,code',
            'description'  => 'nullable|string|max:255',
            'type'         => 'required|in:percent,fixed,free_shipping',
            'value'        => 'required_unless:type,free_shipping|integer|min:0|max:100',
            'min_order'    => 'required|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'max_uses'     => 'required|integer|min:0',
            'expires_at'   => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        // Validasi percent max 100
        if ($data['type'] === 'percent' && ($data['value'] ?? 0) > 100) {
            return back()->withErrors(['value' => 'Persen diskon tidak boleh lebih dari 100.'])->withInput();
        }

        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['value']     = $data['type'] === 'free_shipping' ? 0 : (int)($data['value'] ?? 0);

        Coupon::create($data);

        return redirect()->back()->with('success', 'Kupon berhasil dibuat!');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'description'  => 'nullable|string|max:255',
            'type'         => 'required|in:percent,fixed,free_shipping',
            'value'        => 'required_unless:type,free_shipping|integer|min:0',
            'min_order'    => 'required|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'max_uses'     => 'required|integer|min:0',
            'expires_at'   => 'nullable|date',
            'is_active'    => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['value']     = $data['type'] === 'free_shipping' ? 0 : (int)($data['value'] ?? 0);

        $coupon->update($data);

        return redirect()->back()->with('success', 'Kupon berhasil diperbarui!');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->back()->with('success', 'Kupon berhasil dihapus!');
    }
}
