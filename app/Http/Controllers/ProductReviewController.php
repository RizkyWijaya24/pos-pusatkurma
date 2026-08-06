<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');
        $query = ProductReview::with('product')->latest();

        if ($status === 'pending') {
            $query->where('is_approved', false);
        } elseif ($status === 'approved') {
            $query->where('is_approved', true);
        }

        $reviews = $query->paginate(20)->withQueryString();
        $counts = [
            'all'      => ProductReview::count(),
            'pending'  => ProductReview::where('is_approved', false)->count(),
            'approved' => ProductReview::where('is_approved', true)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'counts', 'status'));
    }

    public function approve(ProductReview $review)
    {
        $review->update(['is_approved' => true]);
        return response()->json(['success' => true, 'message' => 'Ulasan berhasil disetujui.']);
    }

    public function destroy(ProductReview $review)
    {
        $review->delete();
        return response()->json(['success' => true, 'message' => 'Ulasan berhasil dihapus.']);
    }
}
