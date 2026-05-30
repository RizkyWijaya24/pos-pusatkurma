<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        // Mengambil semua produk. Kalau mau produk yang ada stoknya aja, 
        // tinggal ganti jadi: Product::where('stock', '>', 0)->get();
        $products = Product::all();

        // Mengembalikan data dalam format JSON bersih
        return response()->json([
            'status' => 'success',
            'message' => 'Data katalog produk Pusat Kurma',
            'data' => $products
        ], 200);
    }
}