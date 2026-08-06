<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DokuService
{
    /**
     * Buat sesi pembayaran DOKU dan dapatkan URL redirect.
     *
     * @param \App\Models\Order $order
     * @param string|null $channel  Channel pembayaran: QRIS | VIRTUAL_ACCOUNT | EMONEY | RETAIL
     * @return array|null  ['url' => string] atau null jika gagal
     */
    public static function createPayment($order, ?string $channel = null): ?array
    {
        $clientId  = config('doku.client_id');
        $secretKey = config('doku.secret_key');
        $apiUrl    = config('doku.api_url');

        if (empty($clientId) || empty($secretKey)) {
            Log::error('DOKU: DOKU_CLIENT_ID atau DOKU_SECRET_KEY belum dikonfigurasi di .env');
            return null;
        }

        // Susun body request sesuai DOKU Checkout v1
        $body = [
            'order' => [
                'invoice_number' => $order->order_code,
                'amount'         => (int) $order->total_amount,
                'language'       => 'ID',
            ],
            'payment' => [
                'payment_due_date' => 60, // Masa berlaku checkout 60 menit
            ],
            'customer' => [
                'name'  => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => self::formatPhone($order->customer_phone),
            ]
        ];

        // Batasi metode pembayaran yang ditampilkan di halaman DOKU
        // CATATAN: payment_method_types hanya bisa dipakai jika channel tersebut
        // sudah diaktifkan di dashboard DOKU merchant Anda. Jika belum aktif,
        // DOKU akan mengembalikan error HTTP 400 "PAYMENT CHANNEL IS INACTIVE".
        // Untuk sandbox/development, biarkan DOKU menampilkan semua channel aktif.
        // Uncomment kode di bawah jika channel sudah aktif di production:
        //
        // $channelMap = [
        //     'QRIS'            => ['QRIS'],
        //     'VIRTUAL_ACCOUNT' => ['VIRTUAL_ACCOUNT'],
        //     'EMONEY'          => ['EMONEY'],
        //     'RETAIL'          => ['RETAIL_OUTLET'],
        // ];
        // if ($channel && isset($channelMap[$channel])) {
        //     $body['payment']['payment_method_types'] = $channelMap[$channel];
        // }

        $requestId = uniqid('req_', true);
        // ISO 8601 UTC timestamp format: YYYY-MM-DDTHH:mm:ssZ
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $requestTarget = '/checkout/v1/payment';

        // 1. Generate Digest
        $jsonBody = json_encode($body);
        $digest = base64_encode(hash('sha256', $jsonBody, true));

        // 2. Generate String to Sign
        $stringToSign = "Client-Id:" . $clientId . "\n" .
                        "Request-Id:" . $requestId . "\n" .
                        "Request-Timestamp:" . $timestamp . "\n" .
                        "Request-Target:" . $requestTarget . "\n" .
                        "Digest:" . $digest;

        // 3. Generate HMAC-SHA256 Signature
        $signatureBinary = hash_hmac('sha256', $stringToSign, $secretKey, true);
        $signature = 'HMACSHA256=' . base64_encode($signatureBinary);

        try {
            $response = Http::withHeaders([
                'Client-Id'         => $clientId,
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => $signature,
                'Content-Type'      => 'application/json',
                'Accept'            => 'application/json',
            ])->post($apiUrl . $requestTarget, $body);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['response']['payment']['url'])) {
                    Log::info('DOKU: Sesi pembayaran berhasil dibuat', [
                        'order_code' => $order->order_code,
                        'url'        => $data['response']['payment']['url'],
                    ]);

                    return [
                        'url' => $data['response']['payment']['url'],
                    ];
                }

                Log::error('DOKU: Response tidak valid', [
                    'order_code' => $order->order_code,
                    'response'   => $data,
                ]);
                return null;
            }

            Log::error('DOKU: API Request gagal — HTTP ' . $response->status(), [
                'order_code' => $order->order_code,
                'body'       => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('DOKU: Exception saat memanggil API — ' . $e->getMessage(), [
                'order_code' => $order->order_code,
            ]);
            return null;
        }
    }

    /**
     * Verifikasi signature webhook notification dari DOKU.
     *
     * @param string $clientIdHeader
     * @param string $requestIdHeader
     * @param string $timestampHeader
     * @param string $signatureHeader
     * @param string $requestTarget
     * @param string $rawBody
     * @return bool
     */
    public static function verifyNotificationSignature($clientIdHeader, $requestIdHeader, $timestampHeader, $signatureHeader, $requestTarget, $rawBody): bool
    {
        $secretKey = config('doku.secret_key');
        $clientId  = config('doku.client_id');

        if ($clientIdHeader !== $clientId) {
            Log::warning('DOKU Webhook: Client ID header tidak cocok', [
                'header' => $clientIdHeader,
                'config' => $clientId
            ]);
            return false;
        }

        // 1. Generate Digest dari raw body
        $digest = base64_encode(hash('sha256', $rawBody, true));

        // 2. Generate String to Sign
        $stringToSign = "Client-Id:" . $clientIdHeader . "\n" .
                        "Request-Id:" . $requestIdHeader . "\n" .
                        "Request-Timestamp:" . $timestampHeader . "\n" .
                        "Request-Target:" . $requestTarget . "\n" .
                        "Digest:" . $digest;

        // 3. Generate HMAC-SHA256 Signature
        $signatureBinary = hash_hmac('sha256', $stringToSign, $secretKey, true);
        $expectedSignature = 'HMACSHA256=' . base64_encode($signatureBinary);

        // Gunakan hash_equals untuk proteksi timing attack
        if (!hash_equals($expectedSignature, $signatureHeader)) {
            Log::warning('DOKU Webhook: Signature tidak cocok', [
                'expected' => $expectedSignature,
                'received' => $signatureHeader,
                'string_to_sign' => $stringToSign
            ]);
            return false;
        }

        return true;
    }

    /**
     * Cek status pembayaran order langsung ke DOKU API.
     * Berguna ketika webhook tidak bisa diterima (localhost / dev environment).
     *
     * @param string $invoiceNumber  Kode pesanan (order_code)
     * @param int    $amount         Total pembayaran dalam rupiah
     * @return string|null  'SUCCESS', 'FAILED', 'PENDING', atau null jika error
     */
    public static function checkPaymentStatus(string $invoiceNumber, int $amount): ?string
    {
        $clientId  = config('doku.client_id');
        $secretKey = config('doku.secret_key');
        $apiUrl    = config('doku.api_url');

        if (empty($clientId) || empty($secretKey)) {
            Log::warning('DOKU CheckStatus: Client ID atau Secret Key belum dikonfigurasi.');
            return null;
        }

        $requestId     = uniqid('chk_', true);
        $timestamp     = gmdate('Y-m-d\\TH:i:s\\Z');
        $requestTarget = '/orders/v1/status/' . $invoiceNumber;

        // Buat String-to-Sign tanpa body (GET request — tidak ada Digest)
        $stringToSign = "Client-Id:" . $clientId . "\n" .
                        "Request-Id:" . $requestId . "\n" .
                        "Request-Timestamp:" . $timestamp . "\n" .
                        "Request-Target:" . $requestTarget;

        $signatureBinary = hash_hmac('sha256', $stringToSign, $secretKey, true);
        $signature       = 'HMACSHA256=' . base64_encode($signatureBinary);

        try {
            $response = Http::withHeaders([
                'Client-Id'         => $clientId,
                'Request-Id'        => $requestId,
                'Request-Timestamp' => $timestamp,
                'Signature'         => $signature,
                'Accept'            => 'application/json',
            ])->get($apiUrl . $requestTarget);

            Log::info('DOKU CheckStatus: Response', [
                'invoice'  => $invoiceNumber,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // DOKU mengembalikan transaction.status di check status API
                $txStatus = strtoupper($data['transaction']['status'] ?? '');
                return $txStatus ?: null;
            }

            Log::warning('DOKU CheckStatus: HTTP error', [
                'invoice' => $invoiceNumber,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('DOKU CheckStatus: Exception — ' . $e->getMessage(), [
                'invoice' => $invoiceNumber,
            ]);
            return null;
        }
    }

    /**
     * Format nomor HP agar sesuai dengan format DOKU (dimulai dengan 62).
     *
     * @param string|null $phone
     * @return string
     */
    private static function formatPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Bersihkan semua karakter selain angka
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Jika diawali dengan 0, ganti dengan 62
        if (str_starts_with($cleaned, '0')) {
            $cleaned = '62' . substr($cleaned, 1);
        }

        // Jika tidak diawali 62 dan panjangnya wajar, tambahkan 62 di depannya
        if (!str_starts_with($cleaned, '62') && strlen($cleaned) >= 9) {
            $cleaned = '62' . $cleaned;
        }

        return $cleaned;
    }
}
