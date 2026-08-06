<?php

namespace App\Http\Controllers;

use App\Models\ReferralCode;
use Illuminate\Http\Request;

class ReferralCodeController extends Controller
{
    public function index()
    {
        $referrals = ReferralCode::latest()->get();
        return view('admin.referrals.index', compact('referrals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'           => 'required|string|max:30|unique:referral_codes,code',
            'owner_name'     => 'required|string|max:100',
            'notes'          => 'nullable|string|max:255',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order'      => 'required|integer|min:0',
            'is_active'      => 'boolean',
        ]);

        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'Persen diskon tidak boleh lebih dari 100.'])->withInput();
        }

        $data['code']      = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active', true);

        ReferralCode::create($data);

        return redirect()->back()->with('success', 'Kode referral berhasil dibuat!');
    }

    public function update(Request $request, ReferralCode $referralCode)
    {
        $data = $request->validate([
            'owner_name'     => 'required|string|max:100',
            'notes'          => 'nullable|string|max:255',
            'discount_type'  => 'required|in:percent,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_order'      => 'required|integer|min:0',
            'is_active'      => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $referralCode->update($data);

        return redirect()->back()->with('success', 'Kode referral berhasil diperbarui!');
    }

    public function destroy(ReferralCode $referralCode)
    {
        $referralCode->delete();
        return redirect()->back()->with('success', 'Kode referral berhasil dihapus!');
    }
}
