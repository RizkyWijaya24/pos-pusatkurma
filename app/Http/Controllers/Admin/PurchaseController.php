<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockLocation;
use App\Models\StockAdjustmentLog;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchases, accounts payable, and stock receipt form.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Query Purchases
        $query = Purchase::with(['supplier', 'location', 'creator', 'items.product', 'payments']);

        // Filter by Date
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('purchase_date', [$request->start_date, $request->end_date]);
        }

        // Filter by Supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by Payment Status
        if ($request->filled('status')) {
            if ($request->status === 'debt') {
                $query->whereIn('payment_status', ['unpaid', 'partial']);
            } else {
                $query->where('payment_status', $request->status);
            }
        }

        // Filter by Location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        $purchases = $query->latest('purchase_date')->latest('id')->paginate(15)->withQueryString();

        // Outstanding Payables (Buku Utang Supplier)
        $payables = Purchase::with(['supplier', 'location', 'payments'])
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->whereRaw('total_amount > paid_amount')
            ->orderBy('due_date', 'asc')
            ->get();

        // Summary Stats
        $monthlyPurchases = Purchase::whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->get();
        $monthlyTotal = $monthlyPurchases->sum('total_amount');
        $monthlyCount = $monthlyPurchases->count();

        $totalOutstandingDebt = $payables->sum(fn($p) => $p->remaining_debt);
        $overdueCount = $payables->filter(fn($p) => $p->is_overdue)->count();

        // Customer Receivables Stats (Piutang Pelanggan dari Penjualan Tempo)
        $activeReceivables = Transaction::where('transaction_type', 'wholesale')
            ->where('payment_method', 'Piutang / Tempo')
            ->where('payment_status', 'unpaid')
            ->with('installments')
            ->get();

        $totalReceivablesAmount = $activeReceivables->sum(function ($trx) {
            return max(0, $trx->total_price - $trx->installments->sum('amount'));
        });
        $receivablesCustomerCount = $activeReceivables->groupBy(function ($item) {
            return strtolower(trim($item->customer_name));
        })->count();
        $receivablesInvoicesCount = $activeReceivables->count();

        // Master Data for Form
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $locations = StockLocation::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)
            ->select('id', 'sku', 'name', 'category', 'cost_price', 'selling_price', 'price_unit', 'stock')
            ->orderBy('name')
            ->get();
        $categories = \App\Models\Category::pluck('name')
            ->merge(Product::distinct()->whereNotNull('category')->pluck('category'))
            ->filter(fn($c) => !empty(trim($c)))
            ->map(fn($c) => trim($c))
            ->unique()
            ->sort()
            ->values();

        return view('admin.purchases.index', compact(
            'purchases',
            'payables',
            'suppliers',
            'locations',
            'products',
            'categories',
            'monthlyTotal',
            'monthlyCount',
            'totalOutstandingDebt',
            'overdueCount',
            'totalReceivablesAmount',
            'receivablesCustomerCount',
            'receivablesInvoicesCount'
        ));
    }

    /**
     * Store a newly created incoming purchase order & increase stock.
     */
    public function store(Request $request)
    {
        if ($request->has('is_tempo')) {
            $request->merge([
                'is_tempo' => filter_var($request->is_tempo, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validated = $request->validate([
            'supplier_name'   => 'required|string|max:255',
            'supplier_phone'  => 'nullable|string|max:50',
            'invoice_number'  => 'nullable|string|max:100',
            'location_id'     => 'required|exists:stock_locations,id',
            'purchase_date'   => 'required|date',
            'payment_method'  => 'required|string|max:50',
            'is_tempo'        => 'nullable|boolean',
            'skip_stock_increment' => 'nullable|boolean',
            'due_date'        => 'nullable|required_if:is_tempo,true,1|date',
            'initial_deposit' => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.product_id'          => 'required|exists:products,id',
            'items.*.quantity'            => 'required|numeric|min:0.001',
            'items.*.unit'                => 'required|string|max:50',
            'items.*.unit_cost'           => 'required|numeric|min:0',
            'items.*.update_product_cost' => 'nullable|boolean',
        ]);

        $skipStock = $request->boolean('skip_stock_increment');

        DB::beginTransaction();
        try {
            // 1. Find or create supplier
            $supplier = null;
            if (!empty($validated['supplier_name'])) {
                $supplier = Supplier::firstOrCreate(
                    ['name' => trim($validated['supplier_name'])],
                    ['phone' => $validated['supplier_phone'] ?? null]
                );
            }

            // 2. Generate unique purchase code
            $datePrefix = Carbon::parse($validated['purchase_date'])->format('Ymd');
            $randomSuffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $purchaseCode = "PO-{$datePrefix}-{$randomSuffix}";

            // 3. Calculate total amount
            $totalAmount = 0;
            $itemsSummaryArray = [];

            foreach ($validated['items'] as $itemData) {
                $subtotal = round((float) $itemData['quantity'] * (float) $itemData['unit_cost'], 2);
                $totalAmount += $subtotal;

                $prod = Product::find($itemData['product_id']);
                $pName = $prod ? $prod->name : 'Produk';
                $formattedQty = round($itemData['quantity'], 2) == round($itemData['quantity']) 
                    ? (int)$itemData['quantity'] 
                    : number_format($itemData['quantity'], 2, ',', '.');
                $itemsSummaryArray[] = "{$pName} ({$formattedQty} {$itemData['unit']} @ Rp " . number_format($itemData['unit_cost'], 0, ',', '.') . ")";
            }

            // 4. Determine payment amounts & status
            $isTempo = !empty($validated['is_tempo']);
            $initialDeposit = floatval($validated['initial_deposit'] ?? 0);

            if (!$isTempo) {
                $paidAmount = $totalAmount;
                $paymentStatus = 'paid';
                $dueDate = null;
            } else {
                $paidAmount = min($totalAmount, $initialDeposit);
                $paymentStatus = ($paidAmount >= $totalAmount) ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');
                $dueDate = $validated['due_date'];
            }

            $notes = $validated['notes'] ?? null;
            if ($skipStock) {
                $notes = ($notes ? $notes . ' ' : '') . '[Stok Sudah Didata / Masuk Sebelumnya]';
            }

            // 5. Create Purchase record
            $purchase = Purchase::create([
                'purchase_code'  => $purchaseCode,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'supplier_id'    => $supplier ? $supplier->id : null,
                'supplier_name'  => trim($validated['supplier_name']),
                'supplier_phone' => $validated['supplier_phone'] ?? ($supplier ? $supplier->phone : null),
                'location_id'    => $validated['location_id'],
                'created_by'     => auth()->id(),
                'purchase_date'  => $validated['purchase_date'],
                'items_summary'  => implode(', ', $itemsSummaryArray),
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'payment_method' => $validated['payment_method'],
                'payment_status' => $paymentStatus,
                'due_date'       => $dueDate,
                'notes'          => $notes,
            ]);

            // 6. Create Purchase Items & update stocks
            foreach ($validated['items'] as $itemData) {
                $prod = Product::find($itemData['product_id']);
                $subtotal = round((float) $itemData['quantity'] * (float) $itemData['unit_cost'], 2);
                $shouldUpdateCost = !isset($itemData['update_product_cost']) || (bool) $itemData['update_product_cost'];

                PurchaseItem::create([
                    'purchase_id'         => $purchase->id,
                    'product_id'          => $prod->id,
                    'product_name'        => $prod->name,
                    'quantity'            => $itemData['quantity'],
                    'unit'                => $itemData['unit'],
                    'unit_cost'           => $itemData['unit_cost'],
                    'subtotal'            => $subtotal,
                    'update_product_cost' => $shouldUpdateCost,
                ]);

                if (!$skipStock) {
                    // Update location stock
                    $productStock = ProductStock::firstOrCreate([
                        'product_id'  => $prod->id,
                        'location_id' => $validated['location_id'],
                    ], [
                        'stock' => 0,
                    ]);

                    $qtyBefore = (float) $productStock->stock;
                    $productStock->incrementStock((float) $itemData['quantity']);
                    $qtyAfter = (float) $productStock->stock;

                    // Update product total stock
                    $prod->stock = (float) $prod->stock + (float) $itemData['quantity'];
                }

                // Update product HPP if requested
                if ($shouldUpdateCost && (float) $itemData['unit_cost'] > 0) {
                    $prod->cost_price = (int) round($itemData['unit_cost']);
                    $prod->save();
                } elseif (!$skipStock) {
                    $prod->save();
                }

                if (!$skipStock) {
                    // Log into StockAdjustmentLog
                    StockAdjustmentLog::create([
                        'product_id'      => $prod->id,
                        'location_id'     => $validated['location_id'],
                        'type'            => 'adjustment',
                        'quantity_before' => $qtyBefore,
                        'quantity_change' => (float) $itemData['quantity'],
                        'quantity_after'  => $qtyAfter,
                        'reference_type'  => 'purchase',
                        'reference_id'    => $purchase->id,
                        'created_by'      => auth()->id(),
                        'notes'           => "Penerimaan Barang Masuk ({$purchase->purchase_code}) dari {$purchase->supplier_name}",
                        'created_at'      => now(),
                    ]);
                }
            }

            // 7. If initial payment made (either full or DP), create PurchasePayment
            if ($paidAmount > 0) {
                PurchasePayment::create([
                    'purchase_id'      => $purchase->id,
                    'payment_code'     => 'PAY-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                    'amount'           => $paidAmount,
                    'payment_date'     => $validated['purchase_date'],
                    'payment_method'   => $validated['payment_method'],
                    'reference_number' => $validated['invoice_number'] ?? null,
                    'created_by'       => auth()->id(),
                    'notes'            => $isTempo ? 'Pembayaran Uang Muka (DP) Pembelian' : 'Pembayaran Lunas Pembelian',
                ]);
            }

            DB::commit();

            $successMsg = $skipStock
                ? "Faktur pembelian & utang supplier berhasil dicatat ({$purchase->purchase_code}) tanpa menambah stok lagi!"
                : "Stok masuk & faktur pembelian berhasil dicatat dengan kode {$purchase->purchase_code}!";

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg,
                    'purchase' => $purchase,
                ]);
            }

            return redirect()->route('admin.purchases.index')
                ->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan faktur pembelian: ' . $e->getMessage(),
                ], 422);
            }
            return back()->withInput()->with('error', 'Gagal menyimpan faktur pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Record a debt payment / installment for a purchase order.
     */
    public function payDebt(Request $request, Purchase $purchase)
    {
        $remaining = $purchase->remaining_debt;

        $validated = $request->validate([
            'amount'           => "required|numeric|min:1|max:{$remaining}",
            'payment_date'     => 'required|date',
            'payment_method'   => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:255',
        ], [
            'amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa utang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
        ]);

        DB::beginTransaction();
        try {
            $paymentCode = 'PAY-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            PurchasePayment::create([
                'purchase_id'      => $purchase->id,
                'payment_code'     => $paymentCode,
                'amount'           => $validated['amount'],
                'payment_date'     => $validated['payment_date'],
                'payment_method'   => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'created_by'       => auth()->id(),
                'notes'            => $validated['notes'] ?? 'Cicilan Pelunasan Utang Supplier',
            ]);

            $purchase->paid_amount += (float) $validated['amount'];
            if ($purchase->paid_amount >= $purchase->total_amount) {
                $purchase->payment_status = 'paid';
            } else {
                $purchase->payment_status = 'partial';
            }
            $purchase->save();

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Pembayaran utang sebesar Rp " . number_format($validated['amount'], 0, ',', '.') . " berhasil dicatat!",
                    'purchase' => $purchase->load('payments'),
                ]);
            }

            return redirect()->route('admin.purchases.index')
                ->with('success', "Pembayaran utang berhasil dicatat! Sisa utang: Rp " . number_format($purchase->remaining_debt, 0, ',', '.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mencatat pembayaran: ' . $e->getMessage(),
                ], 422);
            }
            return back()->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * Get details of a purchase including items and payments history (JSON).
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['supplier', 'location', 'creator', 'items.product', 'payments.creator']);
        return response()->json([
            'success' => true,
            'purchase' => $purchase,
        ]);
    }

    /**
     * Quick-create a new SKU/product directly from the procurement screen.
     */
    public function quickCreateProduct(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100|unique:products,sku',
            'category'      => 'required|string|max:255',
            'price_unit'    => 'required|string|in:pcs,pack,dus,kg,gram',
            'cost_price'    => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ]);

        if ($validated['selling_price'] < $validated['cost_price']) {
            return response()->json([
                'success' => false,
                'message' => 'Harga jual (Rp ' . number_format($validated['selling_price'], 0, ',', '.') . ') tidak boleh lebih rendah dari harga modal (Rp ' . number_format($validated['cost_price'], 0, ',', '.') . '). Mohon periksa kembali input Anda.'
            ], 422);
        }

        if (empty($validated['sku'])) {
            $validated['sku'] = 'PK-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        }

        if (!empty($validated['category'])) {
            \App\Models\Category::firstOrCreate(['name' => trim($validated['category'])]);
        }

        $product = Product::create([
            'sku'           => trim($validated['sku']),
            'name'          => trim($validated['name']),
            'category'      => trim($validated['category']),
            'cost_price'    => (int) round($validated['cost_price']),
            'selling_price' => (int) round($validated['selling_price']),
            'price_unit'    => $validated['price_unit'],
            'stock'         => 0, // Stock will be incremented when the purchase order is saved
            'is_active'     => true,
            'is_bundle'     => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => "SKU baru \"{$product->name}\" berhasil didaftarkan!",
            'product' => [
                'id'            => $product->id,
                'sku'           => $product->sku,
                'name'          => $product->name,
                'category'      => $product->category,
                'cost_price'    => (int) $product->cost_price,
                'selling_price' => (int) $product->selling_price,
                'price_unit'    => $product->price_unit,
                'stock'         => 0,
            ],
        ]);
    }

    /**
     * Catat Utang Supplier Manual (Hanya Nominal & Catatan tanpa pilih produk).
     */
    public function storeManualDebt(Request $request)
    {
        $validated = $request->validate([
            'supplier_name'   => 'required|string|max:255',
            'supplier_phone'  => 'nullable|string|max:50',
            'invoice_number'  => 'nullable|string|max:100',
            'amount'          => 'required|numeric|min:1',
            'notes'           => 'required|string|max:1000',
            'initial_deposit' => 'nullable|numeric|min:0',
            'purchase_date'   => 'nullable|date',
            'due_date'        => 'nullable|date',
            'payment_method'  => 'nullable|string|max:50',
            'location_id'     => 'nullable|exists:stock_locations,id',
        ]);

        DB::beginTransaction();
        try {
            $supplier = null;
            if (!empty($validated['supplier_name'])) {
                $supplier = Supplier::firstOrCreate(
                    ['name' => trim($validated['supplier_name'])],
                    ['phone' => $validated['supplier_phone'] ?? null]
                );
            }

            $purchaseDate = $request->filled('purchase_date') ? $validated['purchase_date'] : Carbon::now()->toDateString();
            $datePrefix = Carbon::parse($purchaseDate)->format('Ymd');
            $randomSuffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
            $purchaseCode = "PO-M-{$datePrefix}-{$randomSuffix}";

            $totalAmount = floatval($validated['amount']);
            $initialDeposit = floatval($validated['initial_deposit'] ?? 0);
            $paidAmount = min($totalAmount, $initialDeposit);
            $paymentStatus = ($paidAmount >= $totalAmount) ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $locationId = $validated['location_id'] 
                ?? StockLocation::where('is_active', true)->first()?->id 
                ?? StockLocation::first()?->id;

            $itemsSummary = trim($validated['notes']) . ' (Rp ' . number_format($totalAmount, 0, ',', '.') . ')';
            $fullNotes = trim($validated['notes']) . ' [Utang Manual / Saldo Awal - Tanpa Tambah Stok]';

            $purchase = Purchase::create([
                'purchase_code'  => $purchaseCode,
                'invoice_number' => $validated['invoice_number'] ?? null,
                'supplier_id'    => $supplier ? $supplier->id : null,
                'supplier_name'  => trim($validated['supplier_name']),
                'supplier_phone' => $validated['supplier_phone'] ?? ($supplier ? $supplier->phone : null),
                'location_id'    => $locationId,
                'created_by'     => auth()->id(),
                'purchase_date'  => $purchaseDate,
                'items_summary'  => $itemsSummary,
                'total_amount'   => $totalAmount,
                'paid_amount'    => $paidAmount,
                'payment_method' => $validated['payment_method'] ?? 'Transfer Bank',
                'payment_status' => $paymentStatus,
                'due_date'       => $validated['due_date'] ?? null,
                'notes'          => $fullNotes,
            ]);

            if ($paidAmount > 0) {
                PurchasePayment::create([
                    'purchase_id'      => $purchase->id,
                    'payment_code'     => 'PAY-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                    'amount'           => $paidAmount,
                    'payment_date'     => $purchaseDate,
                    'payment_method'   => $validated['payment_method'] ?? 'Transfer Bank',
                    'reference_number' => $validated['invoice_number'] ?? null,
                    'created_by'       => auth()->id(),
                    'notes'            => 'Pembayaran Uang Muka (DP) Utang Supplier',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Utang supplier berhasil dicatat dengan kode {$purchase->purchase_code}!",
                'purchase' => $purchase,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat utang supplier: ' . $e->getMessage(),
            ], 422);
        }
    }
}
