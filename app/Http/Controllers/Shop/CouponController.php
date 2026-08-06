<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * POST /shop/coupon/apply — Validasi dan hitung diskon kupon (AJAX)
     */
    public function apply(Request $request)
    {
        $request->validate([
            'code'          => 'required|string|max:30',
            'subtotal'      => 'required|integer|min:0',
            'shipping_cost' => 'required|integer|min:0',
        ]);

        $code = strtoupper(trim($request->code));
        $subtotal = (int) $request->subtotal;
        $shippingCost = (int) $request->shipping_cost;

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Kode promo tidak ditemukan.',
            ], 422);
        }

        if (!$coupon->isValid($subtotal)) {
            $reason = 'Kode promo tidak valid atau sudah kedaluwarsa.';
            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                $reason = 'Kode promo sudah kedaluwarsa.';
            } elseif ($coupon->max_uses > 0 && $coupon->used_count >= $coupon->max_uses) {
                $reason = 'Kode promo sudah mencapai batas penggunaan.';
            } elseif ($subtotal < $coupon->min_order) {
                $reason = 'Minimum belanja Rp ' . number_format($coupon->min_order, 0, ',', '.') . ' untuk menggunakan kode promo ini.';
            } elseif (!$coupon->is_active) {
                $reason = 'Kode promo tidak aktif.';
            }
            return response()->json([
                'success' => false,
                'message' => $reason,
            ], 422);
        }

        $discount = $coupon->calculateDiscount($subtotal, $shippingCost);

        return response()->json([
            'success'      => true,
            'message'      => 'Kode promo berhasil diterapkan! 🎉',
            'code'         => $coupon->code,
            'description'  => $coupon->description,
            'type'         => $coupon->type,
            'discount'     => $discount,
        ]);
    }
}
