<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    /**
     * GET/POST /shop/track — Cek status pesanan berdasarkan kode order, nomor HP, atau email.
     */
    public function show(Request $request)
    {
        $order          = null;
        $orderCode      = null;
        $error          = null;
        $multipleOrders = null;

        // Ambil kata kunci dari form POST, query URL ?order_code=... atau ?q=...
        $query = trim($request->input('order_code', $request->input('q', '')));

        if (!empty($query)) {
            $orderCode   = $query;
            $searchUpper = strtoupper($query);

            // 1. Cari berdasarkan kode pesanan eksak (misal PK-ORD-20260727-A1B2)
            $order = Order::with(['orderItems.product'])
                ->where('order_code', $searchUpper)
                ->first();

            // 2. Jika tidak ketemu kode pesanan, cari via nomor telepon atau email pelanggan
            if (!$order) {
                // Normalisasi nomor telepon (hilangkan strip/spasi)
                $cleanPhone = preg_replace('/[^0-9]/', '', $query);

                $ordersQuery = Order::with(['orderItems.product'])
                    ->where(function ($q) use ($query, $cleanPhone) {
                        $q->where('customer_email', strtolower($query));
                        if (!empty($cleanPhone) && strlen($cleanPhone) >= 8) {
                            $q->orWhere('customer_phone', 'like', '%' . $cleanPhone . '%');
                        }
                    })
                    ->latest();

                $foundOrders = $ordersQuery->get();

                if ($foundOrders->count() === 1) {
                    $order = $foundOrders->first();
                } elseif ($foundOrders->count() > 1) {
                    $multipleOrders = $foundOrders;
                } else {
                    $error = 'Pesanan dengan kode/kontak "' . $query . '" tidak ditemukan. Silakan periksa kembali kode pesanan, nomor telepon, atau email Anda.';
                }
            }
        }

        return view('shop.track', compact('order', 'orderCode', 'error', 'multipleOrders'));
    }
}
