<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DokuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * GET /admin/orders
     * Daftar semua pesanan online dengan filter status & pencarian.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = $request->input('search', '');

        $query = Order::with('orderItems.product')->latest();

        if ($status !== 'all') {
            $query->where('payment_status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', '%' . $search . '%')
                  ->orWhere('customer_name', 'like', '%' . $search . '%')
                  ->orWhere('customer_phone', 'like', '%' . $search . '%');
            });
        }

        $orders = $query->paginate(20)->withQueryString();

        $counts = [
            'all'     => Order::count(),
            'pending' => Order::where('payment_status', 'pending')->count(),
            'paid'    => Order::where('payment_status', 'paid')->count(),
            'failed'  => Order::where('payment_status', 'failed')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'counts', 'status', 'search'));
    }

    /**
     * GET /admin/orders/{order}
     * Detail satu pesanan lengkap dengan item produk.
     */
    public function show(Order $order)
    {
        $order->load('orderItems.product');
        return view('admin.orders.show', compact('order'));
    }

    /**
     * POST /admin/orders/{order}/status
     * AJAX: Update status pembayaran pesanan.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status'         => 'nullable|in:pending,paid,processing,shipped,completed,cancelled,failed',
            'payment_status' => 'nullable|in:pending,paid,failed',
        ]);

        $newStatus        = $request->status;
        $newPaymentStatus = $request->payment_status;

        $updates = [];

        if ($newStatus) {
            $updates['status'] = $newStatus;
            // Jika status pengiriman maju ke paid/processing/shipped/completed, otomatis set payment_status = paid
            if (in_array($newStatus, ['paid', 'processing', 'shipped', 'completed']) && $order->payment_status !== 'paid') {
                $updates['payment_status'] = 'paid';
            } elseif ($newStatus === 'cancelled' || $newStatus === 'failed') {
                $updates['payment_status'] = 'failed';
            }
        }

        if ($newPaymentStatus) {
            $updates['payment_status'] = $newPaymentStatus;
            if ($newPaymentStatus === 'paid' && in_array($order->status, ['pending', 'failed', 'cancelled'])) {
                $updates['status'] = 'paid';
            }
        }

        $order->update($updates);

        Log::info("Order #{$order->order_code} status updated to " . json_encode($updates) . " by admin " . auth()->id());

        $statusLabels = [
            'pending'    => 'Menunggu Pembayaran',
            'paid'       => 'Pembayaran Lunas',
            'processing' => 'Diproses & Dikemas',
            'shipped'    => 'Dalam Pengiriman',
            'completed'  => 'Pesanan Selesai',
            'cancelled'  => 'Dibatalkan',
            'failed'     => 'Gagal',
        ];

        $displayLabel = $statusLabels[$order->fresh()->status] ?? strtoupper($order->fresh()->status);

        return response()->json([
            'success'        => true,
            'message'        => 'Status pesanan berhasil diperbarui menjadi "' . $displayLabel . '".',
            'new_status'     => $order->fresh()->status,
            'payment_status' => $order->fresh()->payment_status,
            'step_number'    => $order->fresh()->step_number,
        ]);
    }

    /**
     * DELETE /admin/orders/{order}
     * Hapus pesanan (hanya jika belum lunas).
     */
    public function destroy(Order $order)
    {
        if ($order->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan yang sudah LUNAS tidak dapat dihapus.',
            ], 422);
        }

        $code = $order->order_code;
        $order->delete();

        Log::info("Order #{$code} deleted by admin " . auth()->id());

        return response()->json([
            'success' => true,
            'message' => "Pesanan #{$code} berhasil dihapus.",
        ]);
    }

    /**
     * GET /admin/orders/{order}/check-payment
     * AJAX: Cek status pembayaran langsung ke DOKU dan update order jika sudah lunas.
     * Dipanggil otomatis saat admin membuka detail pesanan dengan status pending.
     */
    public function checkPayment(Order $order)
    {
        // Hanya cek untuk pesanan yang masih pending
        if ($order->payment_status !== 'pending') {
            return response()->json([
                'updated'        => false,
                'payment_status' => $order->payment_status,
                'message'        => 'Status sudah final, tidak perlu dicek.',
            ]);
        }

        if (!config('doku.enabled', true)) {
            return response()->json([
                'updated'        => false,
                'payment_status' => $order->payment_status,
                'message'        => 'DOKU tidak diaktifkan.',
            ]);
        }

        $dokuStatus = DokuService::checkPaymentStatus(
            $order->order_code,
            (int) $order->total_amount
        );

        Log::info("DOKU CheckPayment: Order #{$order->order_code} → status DOKU: {$dokuStatus}");

        if ($dokuStatus === 'SUCCESS') {
            $order->update(['payment_status' => 'paid']);
            return response()->json([
                'updated'        => true,
                'payment_status' => 'paid',
                'message'        => 'Pembayaran dikonfirmasi LUNAS dari DOKU.',
            ]);
        }

        if ($dokuStatus === 'FAILED' || $dokuStatus === 'EXPIRED') {
            $order->update(['payment_status' => 'failed']);
            return response()->json([
                'updated'        => true,
                'payment_status' => 'failed',
                'message'        => 'Pembayaran dikonfirmasi GAGAL/KEDALUWARSA dari DOKU.',
            ]);
        }

        return response()->json([
            'updated'        => false,
            'payment_status' => $order->payment_status,
            'doku_status'    => $dokuStatus,
            'message'        => 'Status pembayaran masih menunggu atau tidak dapat dikonfirmasi.',
        ]);
    }
}
