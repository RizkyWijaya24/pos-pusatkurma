<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function __construct(private StockService $stockService) {}

    // ═══════════════════════════════════════════════════════
    // ADMIN & OWNER: DAFTAR SEMUA TRANSFER
    // ═══════════════════════════════════════════════════════

    /**
     * Tampilkan daftar transfer stok (admin & owner).
     */
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromLocation', 'toLocation', 'requester', 'approver', 'items.product'])
                              ->latest();

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter lokasi asal
        if ($request->filled('from')) {
            $query->where('from_location_id', $request->from);
        }

        // Filter lokasi tujuan
        if ($request->filled('to')) {
            $query->where('to_location_id', $request->to);
        }

        // Filter tanggal
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $transfers  = $query->paginate(20)->withQueryString();
        $locations  = StockLocation::active()->orderBy('name')->get();
        $pendingCount = StockTransfer::pending()->count();

        return view('admin.stock-transfers.index', compact('transfers', 'locations', 'pendingCount'));
    }

    // ═══════════════════════════════════════════════════════
    // ADMIN: BUAT TRANSFER BARU
    // ═══════════════════════════════════════════════════════

    /**
     * Form buat transfer stok baru (admin).
     */
    public function create()
    {
        $locations = StockLocation::active()->orderBy('name')->get();
        $products  = Product::orderBy('name')->get();

        return view('admin.stock-transfers.create', compact('locations', 'products'));
    }

    /**
     * Simpan transfer stok baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_location_id' => 'nullable|exists:stock_locations,id',
            'to_location_id'   => 'required|exists:stock_locations,id|different:from_location_id',
            'notes'            => 'nullable|string|max:500',
            'items'            => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        try {
            $transfer = $this->stockService->createTransfer($validated, auth()->user());

            return response()->json([
                'success'   => true,
                'message'   => 'Transfer stok berhasil dibuat!',
                'transfer'  => [
                    'id'   => $transfer->id,
                    'code' => $transfer->transfer_code,
                ],
                'redirect'  => route('admin.stock-transfers.show', $transfer->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Detail transfer stok.
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load(['fromLocation', 'toLocation', 'requester', 'approver', 'items.product']);
        return view('admin.stock-transfers.show', compact('stockTransfer'));
    }

    // ═══════════════════════════════════════════════════════
    // ADMIN & OWNER: APPROVE / REJECT / CANCEL
    // ═══════════════════════════════════════════════════════

    /**
     * Approve transfer — stok berpindah antar lokasi.
     */
    public function approve(Request $request, StockTransfer $stockTransfer)
    {
        try {
            $this->stockService->approveTransfer($stockTransfer, auth()->user());

            // Kirim notifikasi ke kasir pembuat request
            if ($stockTransfer->requester) {
                $stockTransfer->requester->notify(new \App\Notifications\StockTransferNotification($stockTransfer, 'approved', auth()->user()));
            }

            return response()->json([
                'success' => true,
                'message' => "Transfer {$stockTransfer->transfer_code} berhasil disetujui! Stok telah dipindahkan.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject transfer.
     */
    public function reject(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
        ]);

        try {
            $this->stockService->rejectTransfer(
                $stockTransfer,
                auth()->user(),
                $request->input('rejection_reason', '')
            );

            // Kirim notifikasi ke kasir pembuat request
            if ($stockTransfer->requester) {
                $stockTransfer->requester->notify(new \App\Notifications\StockTransferNotification($stockTransfer, 'rejected', auth()->user()));
            }

            return response()->json([
                'success' => true,
                'message' => "Transfer {$stockTransfer->transfer_code} ditolak.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Batalkan transfer.
     */
    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        // Kasir hanya bisa batalkan request miliknya sendiri
        if (auth()->user()->isKasir() && $stockTransfer->requested_by !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak membatalkan request ini.',
            ], 403);
        }

        try {
            $this->stockService->cancelTransfer($stockTransfer);

            // Jika dibatalkan oleh kasir, kirim notifikasi ke Admin & Owner
            if (auth()->user()->isKasir()) {
                $adminOwners = \App\Models\User::whereIn('role', ['admin', 'owner'])->get();
                foreach ($adminOwners as $recipient) {
                    $recipient->notify(new \App\Notifications\StockTransferNotification($stockTransfer, 'cancelled', auth()->user()));
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Transfer {$stockTransfer->transfer_code} dibatalkan.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ═══════════════════════════════════════════════════════
    // AJAX HELPER: Stok produk di lokasi tertentu
    // ═══════════════════════════════════════════════════════

    /**
     * GET stok semua produk di lokasi tertentu (AJAX untuk form create).
     */
    public function getStockByLocation(Request $request)
    {
        $locationId = $request->input('location_id');
        if (!$locationId) {
            return response()->json([]);
        }

        $stocks = ProductStock::with('product')
            ->where('location_id', $locationId)
            ->get()
            ->map(fn($ps) => [
                'product_id'   => $ps->product_id,
                'product_name' => $ps->product->name,
                'stock'        => $ps->stock,
                'unit'         => $ps->product->price_unit,
                'sku'          => $ps->product->sku,
            ]);

        return response()->json($stocks);
    }

    // ═══════════════════════════════════════════════════════
    // KASIR: REQUEST STOK BARANG
    // ═══════════════════════════════════════════════════════

    /**
     * Form request stok untuk kasir (permintaan barang dari cabang ke pusat).
     */
    public function kasirRequestPage()
    {
        $kasir         = auth()->user();
        $myLocation    = StockLocation::findByBranchName($kasir->branch);
        $gudang        = StockLocation::gudang()->first();
        $products      = Product::orderBy('name')->get();

        // Ambil stok cabang kasir
        $myStocks = [];
        if ($myLocation) {
            $myStocks = ProductStock::with('product')
                ->where('location_id', $myLocation->id)
                ->get()
                ->keyBy('product_id');
        }

        // Request yang sudah dibuat kasir ini (10 terbaru)
        $myRequests = StockTransfer::with(['toLocation', 'fromLocation', 'items.product'])
            ->where('requested_by', $kasir->id)
            ->latest()
            ->take(10)
            ->get();

        return view('kasir.stock-request', compact('kasir', 'myLocation', 'gudang', 'products', 'myStocks', 'myRequests'));
    }

    /**
     * Simpan request stok dari kasir.
     */
    public function kasirRequestStore(Request $request)
    {
        $kasir = auth()->user();

        $validated = $request->validate([
            'notes'              => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        // Kasir hanya bisa request ke cabangnya sendiri
        $myLocation = StockLocation::findByBranchName($kasir->branch);
        if (!$myLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi cabang Anda tidak ditemukan. Hubungi Admin.',
            ], 422);
        }

        // Sumber selalu dari Gudang Pusat
        $gudang = StockLocation::gudang()->first();

        $data = [
            'from_location_id' => $gudang?->id,
            'to_location_id'   => $myLocation->id,
            'notes'            => $validated['notes'] ?? null,
            'items'            => $validated['items'],
        ];

        try {
            $transfer = $this->stockService->createTransfer($data, $kasir);

            // Kirim notifikasi ke Admin & Owner
            $adminOwners = \App\Models\User::whereIn('role', ['admin', 'owner'])->get();
            foreach ($adminOwners as $recipient) {
                $recipient->notify(new \App\Notifications\StockTransferNotification($transfer, 'created', $kasir));
            }

            return response()->json([
                'success'   => true,
                'message'   => 'Permintaan stok berhasil dikirim! Menunggu persetujuan admin.',
                'transfer'  => [
                    'id'   => $transfer->id,
                    'code' => $transfer->transfer_code,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ═══════════════════════════════════════════════════════
    // KOREKSI STOK MANUAL (ADMIN)
    // ═══════════════════════════════════════════════════════

    /**
     * Koreksi stok manual via AJAX (admin only).
     */
    public function adjustStock(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'location_id' => 'required|exists:stock_locations,id',
            'new_stock'   => 'required|numeric|min:0',
            'reason'      => 'nullable|string|max:255',
        ]);

        $product  = Product::findOrFail($validated['product_id']);
        $location = StockLocation::findOrFail($validated['location_id']);

        try {
            $this->stockService->adjustStock(
                $product,
                $location,
                (float) $validated['new_stock'],
                auth()->user(),
                $validated['reason'] ?? 'Koreksi stok manual'
            );

            return response()->json([
                'success'    => true,
                'message'    => "Stok {$product->name} di {$location->name} berhasil dikoreksi.",
                'new_stock'  => $validated['new_stock'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
