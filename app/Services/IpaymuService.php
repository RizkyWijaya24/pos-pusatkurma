<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IpaymuService
{
    /**
     * Buat sesi pembayaran iPaymu dan dapatkan URL redirect.
     *
     * @param \App\Models\Order $order
     * @return array|null  ['url' => string, 'session_id' => string] atau null jika gagal
     */
    public static function createPayment($order): ?array
    {
        $apiKey = config('ipaymu.api_key');
        $va     = config('ipaymu.va');
        $apiUrl = config('ipaymu.api_url');

        if (empty($apiKey) || empty($va)) {
            Log::error('iPaymu: IPAYMU_API_KEY atau IPAYMU_VA belum dikonfigurasi di .env');
            return null;
        }

        // Susun array produk dari item pesanan
        $productNames = [];
        $productQtys  = [];
        $productPrices = [];

        foreach ($order->orderItems as $item) {
            $productNames[]  = substr($item->product->name, 0, 255);
            $productQtys[]   = (float) $item->qty;
            $productPrices[] = (int) $item->price;
        }

        // Tambah ongkir sebagai item terpisah agar total cocok
        if ($order->shipping_cost > 0) {
            $productNames[]  = 'Ongkir: ' . $order->shipping_courier . ' ' . $order->shipping_service;
            $productQtys[]   = 1;
            $productPrices[] = (int) $order->shipping_cost;
        }

        // URL-URL callback
        $returnUrl = route('shop.order.success', $order->id);
        $notifyUrl = url('/api/ipaymu/notify');
        $cancelUrl = route('shop.index');

        // Susun body request
        $body = [
            'product'       => $productNames,
            'qty'           => $productQtys,
            'price'         => $productPrices,
            'amount'        => (int) $order->total_amount,
            'returnUrl'     => $returnUrl,
            'notifyUrl'     => $notifyUrl,
            'cancelUrl'     => $cancelUrl,
            'referenceId'   => $order->order_code,
            'buyerName'     => $order->customer_name,
            'buyerEmail'    => $order->customer_email,
            'buyerPhone'    => $order->customer_phone,
            'paymentMethod' => 'redirect',
        ];

        // Generate signature iPaymu v2: HMAC-SHA256
        // StringToSign = "POST:{va}:{sha256(requestBody)}:{apiKey}"
        $bodyJson      = json_encode($body);
        $bodyHash      = hash('sha256', $bodyJson);
        $stringToSign  = 'POST:' . $va . ':' . $bodyHash . ':' . $apiKey;
        $signature     = hash_hmac('sha256', $stringToSign, $apiKey);
        $timestamp     = date('YmdHis');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'va'           => $va,
                'signature'    => $signature,
                'timestamp'    => $timestamp,
            ])->post($apiUrl, $body);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Status'] ?? null) == 200 && isset($data['Data']['Url'])) {
                    Log::info('iPaymu: Sesi pembayaran berhasil dibuat', [
                        'order_code' => $order->order_code,
                        'session_id' => $data['Data']['SessionID'] ?? '-',
                        'url'        => $data['Data']['Url'],
                    ]);

                    return [
                        'url'        => $data['Data']['Url'],
                        'session_id' => $data['Data']['SessionID'] ?? null,
                    ];
                }

                Log::error('iPaymu: Response tidak valid', [
                    'order_code' => $order->order_code,
                    'response'   => $data,
                ]);
                return null;
            }

            Log::error('iPaymu: API Request gagal — HTTP ' . $response->status(), [
                'order_code' => $order->order_code,
                'body'       => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('iPaymu: Exception saat memanggil API — ' . $e->getMessage(), [
                'order_code' => $order->order_code,
            ]);
            return null;
        }
    }
}
