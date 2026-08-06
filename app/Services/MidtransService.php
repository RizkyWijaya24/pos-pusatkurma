<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    /**
     * Dapatkan Snap Token dari Midtrans untuk pembayaran.
     *
     * @param \App\Models\Order $order
     * @return string|null
     */
    public static function getSnapToken($order)
    {
        $serverKey = config('midtrans.server_key');
        $baseUrl   = config('midtrans.snap_api_url');

        if (empty($serverKey)) {
            Log::error('Midtrans: MIDTRANS_SERVER_KEY is not configured in .env');
            return null;
        }

        // Susun detail item belanjaan
        $itemDetails = [];
        foreach ($order->orderItems as $item) {
            $itemDetails[] = [
                'id'       => 'prod_' . $item->product_id,
                'price'    => (int) $item->price,
                'quantity' => (float) $item->qty,
                'name'     => substr($item->product->name, 0, 50), // Midtrans membatasi max 50 karakter untuk nama item
            ];
        }

        // Tambahkan ongkos kirim ke detail item agar jumlah total cocok dengan gross_amount
        if ($order->shipping_cost > 0) {
            $itemDetails[] = [
                'id'       => 'shipping_fee',
                'price'    => (int) $order->shipping_cost,
                'quantity' => 1,
                'name'     => substr('Ongkir: ' . $order->shipping_courier . ' ' . $order->shipping_service, 0, 50),
            ];
        }

        // Susun payload request
        $payload = [
            'transaction_details' => [
                'order_id'     => $order->order_code,
                'gross_amount' => (int) $order->total_amount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => [
                'first_name' => $order->customer_name,
                'email'      => $order->customer_email,
                'phone'      => $order->customer_phone,
                'billing_address' => [
                    'first_name' => $order->customer_name,
                    'email'      => $order->customer_email,
                    'phone'      => $order->customer_phone,
                    'address'    => $order->shipping_address,
                ],
                'shipping_address' => [
                    'first_name' => $order->customer_name,
                    'email'      => $order->customer_email,
                    'phone'      => $order->customer_phone,
                    'address'    => $order->shipping_address,
                ]
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit'       => 'hours',
                'duration'   => 24, // Masa berlaku pembayaran 24 jam
            ]
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $data['token'] ?? null;
            }

            Log::error('Midtrans API Request failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Exception during Midtrans API call: ' . $e->getMessage());
            return null;
        }
    }
}
