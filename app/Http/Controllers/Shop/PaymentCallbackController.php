<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DokuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Handle webhook notifications from DOKU.
     * DOKU akan POST ke URL ini setelah transaksi selesai.
     * Endpoint: POST /api/doku/notify
     */
    public function handleDokuNotify(Request $request)
    {
        $rawBody = $request->getContent();
        Log::info('DOKU Webhook: Notification received', [
            'headers' => $request->headers->all(),
            'body'    => $rawBody,
        ]);

        $clientIdHeader  = $request->header('Client-Id') ?? $request->header('client-id');
        $requestIdHeader = $request->header('Request-Id') ?? $request->header('request-id');
        $timestampHeader = $request->header('Request-Timestamp') ?? $request->header('request-timestamp');
        $signatureHeader = $request->header('Signature') ?? $request->header('signature') ?? $request->header('X-Signature') ?? $request->header('x-signature');

        // Target path sesuai route kita
        $requestTarget = '/api/doku/notify';

        // Verifikasi Signature DOKU
        $isValid = DokuService::verifyNotificationSignature(
            $clientIdHeader,
            $requestIdHeader,
            $timestampHeader,
            $signatureHeader,
            $requestTarget,
            $rawBody
        );

        if (!$isValid) {
            Log::warning('DOKU Webhook: Verifikasi signature gagal');
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        // Decode payload JSON
        $payload = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('DOKU Webhook: Gagal decode JSON payload');
            return response()->json(['message' => 'Invalid JSON payload'], 400);
        }

        $invoiceNumber    = $payload['order']['invoice_number'] ?? null;
        $transactionStatus = strtoupper($payload['transaction']['status'] ?? '');

        if (empty($invoiceNumber)) {
            Log::warning('DOKU Webhook: order.invoice_number kosong', $payload);
            return response()->json(['message' => 'order.invoice_number missing'], 400);
        }

        $order = Order::where('order_code', $invoiceNumber)->first();
        if (!$order) {
            Log::warning('DOKU Webhook: Order tidak ditemukan', ['invoice_number' => $invoiceNumber]);
            return response()->json(['message' => 'Order not found'], 404);
        }

        Log::info('DOKU Webhook: Processing order', [
            'order_code' => $invoiceNumber,
            'status'     => $transactionStatus,
        ]);

        // Mapping status transaksi DOKU: SUCCESS, FAILED
        if ($transactionStatus === 'SUCCESS') {
            if ($order->payment_status !== 'paid') {
                // Update status → paid, ini akan memicu event di model Order untuk potong stok
                $order->update(['payment_status' => 'paid']);
                Log::info('DOKU Webhook: Order ' . $order->order_code . ' ditandai LUNAS.');
            }
        } elseif ($transactionStatus === 'FAILED') {
            $order->update(['payment_status' => 'failed']);
            Log::info('DOKU Webhook: Order ' . $order->order_code . ' ditandai GAGAL.');
        } else {
            Log::info('DOKU Webhook: Status "' . $transactionStatus . '" diabaikan untuk order ' . $order->order_code);
        }

        return response()->json(['message' => 'Notification handled successfully']);
    }
}
