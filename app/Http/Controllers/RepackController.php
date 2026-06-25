<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductConversion;
use App\Models\RepackLog;
use App\Models\StockLocation;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepackController extends Controller
{
    public function __construct(private StockService $stockService) {}

    /**
     * Tampilkan riwayat repack/pecah stok (Admin only).
     */
    public function index(Request $request)
    {
        $query = RepackLog::with(['location', 'sourceProduct', 'creator', 'items.targetProduct'])
            ->latest();

        // Filter lokasi
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter tanggal
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $repacks   = $query->paginate(15)->withQueryString();
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('admin.repack.index', compact('repacks', 'locations'));
    }

    /**
     * Form buat repack baru.
     */
    public function create()
    {
        $locations = StockLocation::active()->orderBy('name')->get();
        // Hanya ambil produk yang memiliki data konversi terdaftar (opsi)
        // Atau ambil semua produk agar flexible
        $products = Product::orderBy('name')->get();

        return view('admin.repack.create', compact('locations', 'products'));
    }

    /**
     * Simpan proses repack baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id'               => 'required|exists:stock_locations,id',
            'source_product_id'         => 'required|exists:products,id',
            'source_quantity'           => 'required|numeric|min:0.01',
            'notes'                     => 'nullable|string|max:500',
            'items'                     => 'required|array|min:1',
            'items.*.target_product_id' => 'required|exists:products,id',
            'items.*.target_quantity'   => 'required|numeric|min:0.01',
            'items.*.additional_packaging_cost' => 'nullable|integer|min:0',
        ]);

        try {
            $repackLog = $this->stockService->repackProduct(
                intval($validated['location_id']),
                intval($validated['source_product_id']),
                floatval($validated['source_quantity']),
                $validated['items'],
                auth()->id(),
                $validated['notes'] ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Proses repack/pecah stok berhasil disimpan! Stok dan HPP telah disesuaikan.',
                'redirect' => route('admin.repack.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * API Endpoint: Ambil daftar konversi untuk produk tertentu (AJAX).
     */
    public function getConversions(Product $product)
    {
        $conversions = ProductConversion::with('targetProduct')
            ->where('source_product_id', $product->id)
            ->get()
            ->map(fn($conv) => [
                'target_product_id'   => $conv->target_product_id,
                'target_product_name' => $conv->targetProduct->name,
                'target_unit'         => $conv->targetProduct->price_unit,
                'conversion_rate'     => $conv->conversion_rate,
            ]);

        return response()->json($conversions);
    }
}
