<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\ReferralCode;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    /**
     * POST /shop/referral/apply — Validasi dan hitung diskon kode referral (AJAX)
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code'     => 'required|string|max:30',
            'subtotal' => 'required|integer|min:0',
        ]);

        $code = strtoupper(trim($request->code));
        $subtotal = (int) $request->subtotal;

        $referral = ReferralCode::where('code', $code)->first();

        if (!$referral) {
            return response()->json([
                'success' => false,
                'message' => 'Kode referral tidak ditemukan.',
            ], 422);
        }

        if (!$referral->isValid($subtotal)) {
            $reason = 'Kode referral tidak aktif.';
            if ($subtotal < $referral->min_order) {
                $reason = 'Minimum belanja Rp ' . number_format($referral->min_order, 0, ',', '.') . ' untuk menggunakan kode referral ini.';
            }
            return response()->json([
                'success' => false,
                'message' => $reason,
            ], 422);
        }

        $discount = $referral->calculateDiscount($subtotal);

        $discountLabel = $referral->discount_type === 'percent'
            ? $referral->discount_value . '%'
            : 'Rp ' . number_format($referral->discount_value, 0, ',', '.');

        return response()->json([
            'success'       => true,
            'message'       => "Kode referral dari \"{$referral->owner_name}\" berhasil diterapkan! Diskon {$discountLabel} 🎉",
            'code'          => $referral->code,
            'owner_name'    => $referral->owner_name,
            'discount_type' => $referral->discount_type,
            'discount'      => $discount,
        ]);
    }
}
