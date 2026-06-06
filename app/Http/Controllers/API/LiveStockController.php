<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class LiveStockController extends Controller
{
    /**
     * Get real-time live stock of active products.
     * Active products are defined as products that have stock > 0.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Query to get active products (including stock = 0)
        // Selecting only required columns: name (nama produk), selling_price (harga), stock (stok), and category (kategori)
        $products = Product::select('name', 'selling_price', 'stock', 'category')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ], 200);
    }
}
