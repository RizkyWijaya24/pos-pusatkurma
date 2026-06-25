<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductConversion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductConversionController extends Controller
{
    /**
     * Display a listing of the product conversions.
     */
    public function index()
    {
        $conversions = ProductConversion::with(['sourceProduct', 'targetProduct'])
            ->latest()
            ->paginate(20);

        // Fetch all products to populate the dropdowns
        $products = Product::orderBy('name')->get();

        return view('admin.conversions.index', compact('conversions', 'products'));
    }

    /**
     * Store a newly created product conversion in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_product_id' => 'required|exists:products,id',
            'target_product_id' => [
                'required',
                'exists:products,id',
                'different:source_product_id',
            ],
            'conversion_rate' => 'required|numeric|min:0.0001',
        ], [
            'target_product_id.different' => 'Produk target hasil harus berbeda dengan produk asal.',
            'conversion_rate.min' => 'Rasio konversi harus lebih besar dari 0.',
        ]);

        // Check if unique constraint is already met
        $exists = ProductConversion::where('source_product_id', $validated['source_product_id'])
            ->where('target_product_id', $validated['target_product_id'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['target_product_id' => 'Aturan konversi untuk pasangan produk ini sudah ada.']);
        }

        ProductConversion::create($validated);

        return redirect()->route('admin.conversions.index')
            ->with('success', 'Aturan konversi produk berhasil ditambahkan!');
    }

    /**
     * Remove the specified product conversion from storage.
     */
    public function destroy(ProductConversion $conversion)
    {
        $conversion->delete();

        return redirect()->route('admin.conversions.index')
            ->with('success', 'Aturan konversi produk berhasil dihapus!');
    }
}
