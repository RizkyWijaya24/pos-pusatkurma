<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * GET /shop/product/{product}/reviews — Load ulasan (AJAX pagination)
     */
    public function index(Product $product, Request $request)
    {
        $reviews = ProductReview::where('product_id', $product->id)
            ->approved()
            ->latest()
            ->paginate(5);

        $avgRating = ProductReview::where('product_id', $product->id)
            ->approved()
            ->avg('rating');

        $ratingCounts = ProductReview::where('product_id', $product->id)
            ->approved()
            ->selectRaw('rating, count(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        return response()->json([
            'reviews'      => $reviews->items(),
            'avg_rating'   => round((float)($avgRating ?? 0), 1),
            'total'        => $reviews->total(),
            'rating_counts' => $ratingCounts,
            'next_page'    => $reviews->nextPageUrl(),
        ]);
    }

    /**
     * POST /shop/review — Simpan ulasan baru
     * order_code opsional — jika disertakan, divalidasi; jika tidak, ulasan tetap disimpan (unverified)
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id'    => 'required|exists:products,id',
            'order_code'    => 'nullable|string|max:40',
            'reviewer_name' => 'required|string|max:100',
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'nullable|string|max:1000',
        ]);

        $orderCode    = $request->order_code ? strtoupper(trim($request->order_code)) : null;
        $verifiedOrder = null;

        // Hanya validasi order_code jika disertakan
        if ($orderCode) {
            $order = Order::where('order_code', $orderCode)
                ->where('payment_status', 'paid')
                ->with('orderItems')
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode pesanan tidak valid atau pembayaran belum dikonfirmasi.',
                ], 422);
            }

            $productInOrder = $order->orderItems->where('product_id', $request->product_id)->isNotEmpty();
            if (!$productInOrder) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk ini tidak ada dalam pesanan dengan kode tersebut.',
                ], 422);
            }

            // Cegah duplikasi ulasan untuk produk + order yang sama
            $alreadyReviewed = ProductReview::where('order_code', $orderCode)
                ->where('product_id', $request->product_id)
                ->exists();

            if ($alreadyReviewed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda sudah memberikan ulasan untuk produk ini dengan kode pesanan yang sama.',
                ], 422);
            }

            $verifiedOrder = $order;
        }

        ProductReview::create([
            'product_id'    => $request->product_id,
            'order_code'    => $orderCode,
            'reviewer_name' => $request->reviewer_name,
            'rating'        => $request->rating,
            'comment'       => $request->comment,
            'is_approved'   => false, // harus disetujui admin dulu
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ulasan Anda telah dikirim dan sedang menunggu persetujuan admin. Terima kasih! 🌟',
        ]);
    }
}
