<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopIsEnabled
{
    /**
     * Handle an incoming request for shop routes.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $shopEnabled = Setting::get('shop_enabled', '1');
        $envEnabled = env('ENABLE_SHOP', true);

        $isClosed = ($shopEnabled === '0' || $shopEnabled === 'false' || $shopEnabled === false || $envEnabled === false);

        if ($isClosed) {
            // Kelonggaran preview jika admin/owner sedang login dan menyertakan ?preview=1
            if ($request->has('preview') && auth()->check() && in_array(auth()->user()->role, ['admin', 'owner'])) {
                return $next($request);
            }

            // Jika AJAX / API request
            if ($request->expectsJson() || $request->is('shop/shipping/*') || $request->is('shop/chatbot/*') || $request->is('shop/coupon/*') || $request->is('shop/referral/*')) {
                return response()->json([
                    'success' => false,
                    'status' => 'maintenance',
                    'message' => 'Toko online sedang dalam pemeliharaan sementara.'
                ], 503);
            }

            // Tampilkan halaman pemeliharaan ringan tanpa query berat
            $notice = Setting::get('shop_maintenance_notice', 'Kami sedang melakukan pemeliharaan sistem & pembaruan stok berkala. Untuk pemesanan produk dan informasi kurma, silakan hubungi layanan WhatsApp kami.');
            $whatsapp = Setting::get('shop_whatsapp', '6281234567890');
            $shopName = Setting::get('shop_name', 'Pusat Kurma');
            $shopPhone = Setting::get('shop_phone', $whatsapp);

            return response()->view('shop.maintenance', compact('notice', 'whatsapp', 'shopName', 'shopPhone'), 503);
        }

        return $next($request);
    }
}

