<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockService
{
    // ═══════════════════════════════════════════════════════
    // INISIALISASI STOK
    // ═══════════════════════════════════════════════════════

    /**
     * Inisialisasi stok produk di lokasi tertentu.
     * Dipakai oleh seeder saat setup pertama kali.
     *
     * @param Product       $product
     * @param StockLocation $location
     * @param float         $qty
     * @param User          $createdBy
     */
    public function initializeStock(Product $product, StockLocation $location, float $qty, User $createdBy): void
    {
        DB::transaction(function () use ($product, $location, $qty, $createdBy) {
            $ps = ProductStock::getOrCreate($product->id, $location->id);
            $before = $ps->stock;
            $ps->stock = $qty;
            $ps->save();

            // Catat di log
            StockAdjustmentLog::create([
                'product_id'      => $product->id,
                'location_id'     => $location->id,
                'type'            => 'initial',
                'quantity_before' => $before,
                'quantity_change' => $qty - $before,
                'quantity_after'  => $qty,
                'created_by'      => $createdBy->id,
                'notes'           => 'Stok awal sistem',
                'created_at'      => now(),
            ]);

            // Sync stok global produk
            $this->syncGlobalStock($product);
        });
    }

    // ═══════════════════════════════════════════════════════
    // TRANSFER STOK
    // ═══════════════════════════════════════════════════════

    /**
     * Buat transfer/request stok baru.
     * - Admin/Owner: status = 'pending' (langsung siap diproses)
     * - Kasir: status = 'requested' (harus diproses admin dulu)
     *
     * @param array $data  { from_location_id, to_location_id, items[], notes }
     * @param User  $requestedBy
     * @return StockTransfer
     * @throws \Exception
     */
    public function createTransfer(array $data, User $requestedBy): StockTransfer
    {
        // Tentukan status awal berdasarkan role
        $status = $requestedBy->isKasir() ? 'requested' : 'pending';

        return DB::transaction(function () use ($data, $requestedBy, $status) {
            // Validasi stok tersedia (hanya jika bukan kasir request dan ada from_location)
            if (!$requestedBy->isKasir() && !empty($data['from_location_id'])) {
                foreach ($data['items'] as $item) {
                    $ps = ProductStock::getOrCreate($item['product_id'], $data['from_location_id']);
                    if ($ps->stock < $item['quantity']) {
                        $product = Product::find($item['product_id']);
                        throw new \Exception(
                            "Stok {$product->name} tidak mencukupi di lokasi asal. " .
                            "Tersedia: {$ps->stock}, diminta: {$item['quantity']}"
                        );
                    }
                }
            }

            // Buat header transfer
            $transfer = StockTransfer::create([
                'transfer_code'    => StockTransfer::generateCode(),
                'from_location_id' => $data['from_location_id'] ?? null,
                'to_location_id'   => $data['to_location_id'],
                'requested_by'     => $requestedBy->id,
                'status'           => $status,
                'notes'            => $data['notes'] ?? null,
            ]);

            // Buat detail item
            foreach ($data['items'] as $item) {
                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit'        => $item['unit'] ?? 'pcs',
                    'notes'       => $item['notes'] ?? null,
                ]);
            }

            return $transfer;
        });
    }

    /**
     * Approve transfer: stok benar-benar dipindahkan antar lokasi.
     *
     * @param StockTransfer $transfer
     * @param User          $approvedBy
     * @throws \Exception
     */
    public function approveTransfer(StockTransfer $transfer, User $approvedBy): void
    {
        if (!in_array($transfer->status, ['requested', 'pending'])) {
            throw new \Exception('Transfer tidak bisa di-approve. Status saat ini: ' . $transfer->status);
        }

        DB::transaction(function () use ($transfer, $approvedBy) {
            // Muat relasi
            $transfer->load('items.product', 'fromLocation', 'toLocation');

            foreach ($transfer->items as $item) {
                $product = $item->product;
                $qty     = $item->quantity;

                // 1. Kurangi stok di lokasi ASAL (jika ada)
                if ($transfer->from_location_id) {
                    $fromPs = ProductStock::getOrCreate($product->id, $transfer->from_location_id);
                    if ($fromPs->stock < $qty) {
                        throw new \Exception(
                            "Stok {$product->name} tidak mencukupi di {$transfer->fromLocation->name}. " .
                            "Tersedia: {$fromPs->stock}, dipindahkan: {$qty}"
                        );
                    }
                    $beforeFrom = $fromPs->stock;
                    $fromPs->decrementStock($qty);

                    StockAdjustmentLog::create([
                        'product_id'      => $product->id,
                        'location_id'     => $transfer->from_location_id,
                        'type'            => 'transfer_out',
                        'quantity_before' => $beforeFrom,
                        'quantity_change' => -$qty,
                        'quantity_after'  => $fromPs->stock,
                        'reference_type'  => StockTransfer::class,
                        'reference_id'    => $transfer->id,
                        'created_by'      => $approvedBy->id,
                        'notes'           => "Transfer ke {$transfer->toLocation->name}",
                        'created_at'      => now(),
                    ]);
                }

                // 2. Tambah stok di lokasi TUJUAN
                $toPs = ProductStock::getOrCreate($product->id, $transfer->to_location_id);
                $beforeTo = $toPs->stock;
                $toPs->incrementStock($qty);

                StockAdjustmentLog::create([
                    'product_id'      => $product->id,
                    'location_id'     => $transfer->to_location_id,
                    'type'            => 'transfer_in',
                    'quantity_before' => $beforeTo,
                    'quantity_change' => $qty,
                    'quantity_after'  => $toPs->stock,
                    'reference_type'  => StockTransfer::class,
                    'reference_id'    => $transfer->id,
                    'created_by'      => $approvedBy->id,
                    'notes'           => $transfer->from_location_id
                        ? "Transfer dari {$transfer->fromLocation->name}"
                        : "Pengadaan baru / stok masuk",
                    'created_at'      => now(),
                ]);

                // 3. Sync stok global produk
                $this->syncGlobalStock($product);
            }

            // Update status transfer
            $transfer->update([
                'status'      => 'approved',
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Reject transfer.
     *
     * @param StockTransfer $transfer
     * @param User          $rejectedBy
     * @param string        $reason
     * @throws \Exception
     */
    public function rejectTransfer(StockTransfer $transfer, User $rejectedBy, string $reason = ''): void
    {
        if (!in_array($transfer->status, ['requested', 'pending'])) {
            throw new \Exception('Transfer tidak bisa ditolak. Status saat ini: ' . $transfer->status);
        }

        $transfer->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'approved_by'      => $rejectedBy->id,
            'approved_at'      => now(),
        ]);
    }

    /**
     * Batalkan transfer (oleh requester atau admin).
     *
     * @param StockTransfer $transfer
     * @throws \Exception
     */
    public function cancelTransfer(StockTransfer $transfer): void
    {
        if (!in_array($transfer->status, ['requested', 'pending'])) {
            throw new \Exception('Transfer tidak bisa dibatalkan. Status saat ini: ' . $transfer->status);
        }

        $transfer->update(['status' => 'cancelled']);
    }

    /**
     * Approve transfer dengan penyesuaian jumlah oleh admin.
     * Admin bisa mengubah jumlah tiap item (misal: kasir minta 5, gudang hanya ada 4).
     *
     * @param StockTransfer $transfer
     * @param User          $approvedBy
     * @param array         $approvedQuantities  [ transfer_item_id => float approved_qty ]
     * @throws \Exception
     */
    public function approveTransferWithAdjustment(StockTransfer $transfer, User $approvedBy, array $approvedQuantities): void
    {
        if (!in_array($transfer->status, ['requested', 'pending'])) {
            throw new \Exception('Transfer tidak bisa di-approve. Status saat ini: ' . $transfer->status);
        }

        DB::transaction(function () use ($transfer, $approvedBy, $approvedQuantities) {
            // Muat relasi
            $transfer->load('items.product', 'fromLocation', 'toLocation');

            // Validasi dan update approved_quantity tiap item
            foreach ($transfer->items as $item) {
                $approvedQty = isset($approvedQuantities[$item->id])
                    ? (float) $approvedQuantities[$item->id]
                    : $item->quantity; // default = jumlah asli jika tidak diubah

                // Validasi tidak boleh negatif
                if ($approvedQty < 0) {
                    throw new \Exception("Jumlah yang disetujui tidak boleh negatif untuk produk {$item->product->name}.");
                }

                // Validasi stok gudang cukup untuk jumlah yang disetujui
                if ($transfer->from_location_id && $approvedQty > 0) {
                    $ps = ProductStock::getOrCreate($item->product_id, $transfer->from_location_id);
                    if ($ps->stock < $approvedQty) {
                        throw new \Exception(
                            "Stok {$item->product->name} tidak mencukupi di gudang. " .
                            "Tersedia: {$ps->stock}, Jumlah disetujui: {$approvedQty}"
                        );
                    }
                }

                // Simpan jumlah yang disetujui ke kolom approved_quantity
                $item->update(['approved_quantity' => $approvedQty]);
            }

            // Refresh items setelah update
            $transfer->load('items.product');

            // Proses transfer stok untuk setiap item
            foreach ($transfer->items as $item) {
                $product     = $item->product;
                $qty         = $item->approved_quantity ?? $item->quantity;

                // Skip item yang disetujui 0 (admin tidak mengirimkan produk ini)
                if ($qty <= 0) {
                    continue;
                }

                // 1. Kurangi stok di lokasi ASAL (jika ada)
                if ($transfer->from_location_id) {
                    $fromPs = ProductStock::getOrCreate($product->id, $transfer->from_location_id);
                    $beforeFrom = $fromPs->stock;
                    $fromPs->decrementStock($qty);

                    StockAdjustmentLog::create([
                        'product_id'      => $product->id,
                        'location_id'     => $transfer->from_location_id,
                        'type'            => 'transfer_out',
                        'quantity_before' => $beforeFrom,
                        'quantity_change' => -$qty,
                        'quantity_after'  => $fromPs->stock,
                        'reference_type'  => StockTransfer::class,
                        'reference_id'    => $transfer->id,
                        'created_by'      => $approvedBy->id,
                        'notes'           => "Transfer ke {$transfer->toLocation->name}" .
                                            ($item->quantity != $qty ? " (disesuaikan dari {$item->quantity} → {$qty})" : ""),
                        'created_at'      => now(),
                    ]);
                }

                // 2. Tambah stok di lokasi TUJUAN
                $toPs = ProductStock::getOrCreate($product->id, $transfer->to_location_id);
                $beforeTo = $toPs->stock;
                $toPs->incrementStock($qty);

                StockAdjustmentLog::create([
                    'product_id'      => $product->id,
                    'location_id'     => $transfer->to_location_id,
                    'type'            => 'transfer_in',
                    'quantity_before' => $beforeTo,
                    'quantity_change' => $qty,
                    'quantity_after'  => $toPs->stock,
                    'reference_type'  => StockTransfer::class,
                    'reference_id'    => $transfer->id,
                    'created_by'      => $approvedBy->id,
                    'notes'           => $transfer->from_location_id
                        ? "Transfer dari {$transfer->fromLocation->name}" .
                          ($item->quantity != $qty ? " (disesuaikan dari {$item->quantity} → {$qty})" : "")
                        : "Pengadaan baru / stok masuk",
                    'created_at'      => now(),
                ]);

                // 3. Sync stok global produk
                $this->syncGlobalStock($product);
            }

            // Update status transfer menjadi approved
            $transfer->update([
                'status'      => 'approved',
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
            ]);
        });
    }


    // ═══════════════════════════════════════════════════════
    // PENJUALAN (DEDUCT STOCK)
    // ═══════════════════════════════════════════════════════

    /**
     * Kurangi stok saat penjualan di cabang kasir.
     * Dipanggil dari TransactionController::store().
     *
     * @param Product       $product
     * @param StockLocation $location   Lokasi cabang kasir
     * @param float         $qty
     * @param int           $transactionId
     * @param User          $soldBy
     * @return bool  true jika stok berhasil dikurangi, false jika stok tidak tersedia di cabang
     */
    public function deductSaleStock(
        Product $product,
        StockLocation $location,
        float $qty,
        int $transactionId,
        User $soldBy,
        ?string $customNote = null
    ): bool {
        if ($product->is_bundle) {
            $product->loadMissing('bundleItems.product');
            foreach ($product->bundleItems as $item) {
                $requiredQty = $item->quantity * $qty;
                $this->deductSaleStock(
                    $item->product,
                    $location,
                    $requiredQty,
                    $transactionId,
                    $soldBy,
                    $customNote ?? "Penjualan Paket Bundling: {$product->name} (x{$qty}) di {$location->name}"
                );
            }
            $this->syncGlobalStock($product);
            return true;
        }

        $ps = ProductStock::getOrCreate($product->id, $location->id);

        // Jika stok tidak cukup di lokasi ini, masih bisa jual tapi catat selisih
        $actualDeduct = min($qty, $ps->stock);
        $before = $ps->stock;

        $ps->stock = max(0, $ps->stock - $qty);
        $ps->save();

        StockAdjustmentLog::create([
            'product_id'      => $product->id,
            'location_id'     => $location->id,
            'type'            => 'sale',
            'quantity_before' => $before,
            'quantity_change' => -$qty,
            'quantity_after'  => $ps->stock,
            'reference_type'  => \App\Models\Transaction::class,
            'reference_id'    => $transactionId,
            'created_by'      => $soldBy->id,
            'notes'           => $customNote ?? "Penjualan di {$location->name}",
            'created_at'      => now(),
        ]);

        $this->syncGlobalStock($product);

        return true;
    }

    /**
     * Kurangi stok saat pesanan online berhasil dibayar (LUNAS).
     *
     * @param Order $order
     */
    public function deductOrderStock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Cari "Cabang Rumah" sebagai lokasi pemotongan stok utama untuk pesanan online
            $onlineLocation = StockLocation::where('name', 'Cabang Rumah')
                ->where('is_active', true)
                ->first();

            if (!$onlineLocation) {
                // Fallback ke lokasi gudang atau lokasi aktif pertama
                $onlineLocation = StockLocation::gudang()->first() 
                    ?? StockLocation::active()->first() 
                    ?? StockLocation::first();
            }

            if ($onlineLocation) {
                $order->loadMissing('orderItems.product');
                foreach ($order->orderItems as $item) {
                    $this->deductSingleProductOrderStock(
                        $item->product,
                        $onlineLocation,
                        (float)$item->qty,
                        $order
                    );
                }

                // Catat transaksi penjualan online ke tabel transactions untuk riwayat penjualan
                $trxCode = 'TRX-ONL-' . $order->order_code;
                if (!Transaction::where('transaction_code', $trxCode)->exists()) {
                    $itemsSummary = $order->orderItems->map(function ($item) {
                        return $item->product->name . ' (' . $item->qty . ' ' . $item->product->price_unit . ' x ' . (int)$item->price . ')';
                    })->join(', ');

                    $totalCost = $order->orderItems->sum(function ($item) {
                        return round(($item->product->cost_price ?? 0) * (float)$item->qty);
                    });

                    Transaction::create([
                        'cashier_id'       => auth()->id() ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id,
                        'transaction_code' => $trxCode,
                        'items_summary'    => $itemsSummary,
                        'total_price'      => (int) $order->subtotal_amount,
                        'discount'         => 0,
                        'total_cost'       => (int) $totalCost,
                        'payment_method'   => 'DOKU', // default untuk pembayaran online
                        'branch'           => 'Cabang Rumah',
                    ]);
                }
            }
        });
    }

    /**
     * Helper untuk memotong stok satu produk (atau komponen bundlenya) untuk pesanan online.
     */
    private function deductSingleProductOrderStock(
        Product $product,
        StockLocation $location,
        float $qty,
        Order $order,
        ?string $customNote = null
    ): void {
        if ($product->is_bundle) {
            $product->loadMissing('bundleItems.product');
            foreach ($product->bundleItems as $item) {
                $requiredQty = $item->quantity * $qty;
                $this->deductSingleProductOrderStock(
                    $item->product,
                    $location,
                    $requiredQty,
                    $order,
                    $customNote ?? "Penjualan Online Paket Bundling: {$product->name} (x{$qty}) - {$order->order_code}"
                );
            }
            $this->syncGlobalStock($product);
            return;
        }

        $productStock = ProductStock::getOrCreate($product->id, $location->id);
        $qtyBefore = (float) $productStock->stock;

        // Kurangi stok (memastikan tidak negatif)
        $qtyAfter = max(0.00, $qtyBefore - $qty);
        $productStock->update(['stock' => $qtyAfter]);

        // Catat log mutasi stok POS untuk audit history
        StockAdjustmentLog::create([
            'product_id'      => $product->id,
            'location_id'     => $location->id,
            'type'            => 'sale',
            'quantity_before' => $qtyBefore,
            'quantity_change' => -$qty,
            'quantity_after'  => $qtyAfter,
            'reference_type'  => Order::class,
            'reference_id'    => $order->id,
            'created_by'      => auth()->id() ?? User::where('role', 'admin')->first()?->id ?? User::first()?->id,
            'notes'           => $customNote ?? 'Penjualan Online: ' . $order->order_code,
            'created_at'      => now(),
        ]);

        // Sinkronisasi stok global produk
        $this->syncGlobalStock($product);
    }

    // ═══════════════════════════════════════════════════════
    // KOREKSI STOK MANUAL
    // ═══════════════════════════════════════════════════════

    /**
     * Koreksi stok manual oleh admin/owner.
     *
     * @param Product       $product
     * @param StockLocation $location
     * @param float         $newQty    Jumlah stok yang benar
     * @param User          $by
     * @param string        $reason
     */
    public function adjustStock(
        Product $product,
        StockLocation $location,
        float $newQty,
        User $by,
        string $reason = ''
    ): void {
        DB::transaction(function () use ($product, $location, $newQty, $by, $reason) {
            $ps = ProductStock::getOrCreate($product->id, $location->id);
            $before = $ps->stock;
            $change = $newQty - $before;

            $ps->stock = $newQty;
            $ps->save();

            StockAdjustmentLog::create([
                'product_id'      => $product->id,
                'location_id'     => $location->id,
                'type'            => 'adjustment',
                'quantity_before' => $before,
                'quantity_change' => $change,
                'quantity_after'  => $newQty,
                'created_by'      => $by->id,
                'notes'           => $reason ?: 'Koreksi stok manual',
                'created_at'      => now(),
            ]);

            $this->syncGlobalStock($product);
        });
    }

    // ═══════════════════════════════════════════════════════
    // SINKRONISASI & REPORTING
    // ═══════════════════════════════════════════════════════

    /**
     * Sinkronisasi kolom 'stock' di tabel products (agregat total semua lokasi).
     * Kolom ini dipertahankan untuk backward compatibility.
     *
     * @param Product $product
     */
    public function syncGlobalStock(Product $product): void
    {
        if ($product->is_bundle) {
            $totalVirtualStock = 0.0;
            $locations = StockLocation::where('is_active', true)->get();
            foreach ($locations as $loc) {
                $totalVirtualStock += $product->getStockAtLocation($loc->id);
            }
            $product->update(['stock' => $totalVirtualStock]);
            return;
        }

        // Jalankan migrasi stock dari kolom legacy ke tabel product_stocks
        // jika data product_stocks untuk produk ini masih benar-benar kosong.
        $hasAnyStockRecord = ProductStock::where('product_id', $product->id)->exists();
        if (!$hasAnyStockRecord) {
            $mainLocation = StockLocation::gudang()->first() ?? StockLocation::first();
            if ($mainLocation) {
                ProductStock::create([
                    'product_id'  => $product->id,
                    'location_id' => $mainLocation->id,
                    'stock'       => (float) $product->getRawOriginal('stock'),
                ]);
            }
        }

        $totalStock = ProductStock::where('product_id', $product->id)->sum('stock');
        $product->update(['stock' => $totalStock]);
    }

    /**
     * Sinkronisasi stok global untuk semua produk.
     */
    public function syncAllGlobalStocks(): void
    {
        Product::each(function (Product $product) {
            $this->syncGlobalStock($product);
        });
    }

    /**
     * Ambil daftar produk dengan stok kritis per lokasi.
     *
     * @param float $threshold  Batas minimum stok
     * @return array
     */
    public function getLowStockAlerts(float $threshold = 10): array
    {
        return ProductStock::with(['product', 'location'])
            ->where('stock', '<=', $threshold)
            ->where('stock', '>', 0) // Tampilkan yang hampir habis juga
            ->orderBy('stock')
            ->get()
            ->groupBy('location.name')
            ->map(function ($stocks) {
                return $stocks->map(fn($ps) => [
                    'product'  => $ps->product->name,
                    'stock'    => $ps->stock,
                    'unit'     => $ps->product->price_unit,
                    'location' => $ps->location->name,
                ]);
            })
            ->toArray();
    }

    /**
     * Ambil matrix stok: semua produk × semua lokasi.
     * Format: ['product_id' => ['location_id' => qty, ...], ...]
     *
     * @return array
     */
    public function getStockMatrix(): array
    {
        $allStocks = ProductStock::with(['product', 'location'])
            ->get()
            ->groupBy('product_id');

        $matrix = [];
        foreach ($allStocks as $productId => $stocks) {
            foreach ($stocks as $ps) {
                $matrix[$productId][$ps->location_id] = $ps->stock;
            }
        }
        return $matrix;
    }

    /**
     * Memproses repack / pecah stok dari produk bulk ke produk eceran/kemasan kecil.
     *
     * @param int    $locationId
     * @param int    $sourceProductId
     * @param float  $sourceQty
     * @param array  $targets
     * @param int    $userId
     * @param string $notes
     * @return \App\Models\RepackLog
     * @throws \Exception
     */
    public function repackProduct(
        int $locationId,
        int $sourceProductId,
        float $sourceQty,
        array $targets,
        int $userId,
        string $notes = ''
    ): \App\Models\RepackLog {
        return DB::transaction(function () use ($locationId, $sourceProductId, $sourceQty, $targets, $userId, $notes) {
            $sourceProduct = Product::findOrFail($sourceProductId);

            // 1. Validasi stok sumber & potong stok di lokasi terpilih
            $sourcePs = ProductStock::getOrCreate($sourceProductId, $locationId);
            if ($sourcePs->stock < $sourceQty) {
                throw new \Exception(
                    "Stok {$sourceProduct->name} tidak mencukupi untuk di-repack. " .
                    "Tersedia: {$sourcePs->stock}, diminta: {$sourceQty}"
                );
            }

            $beforeSource = $sourcePs->stock;
            $sourcePs->decrementStock($sourceQty);

            // Log mutasi stok produk asal
            StockAdjustmentLog::create([
                'product_id'      => $sourceProductId,
                'location_id'     => $locationId,
                'type'            => 'adjustment',
                'quantity_before' => $beforeSource,
                'quantity_change' => -$sourceQty,
                'quantity_after'  => $sourcePs->stock,
                'created_by'      => $userId,
                'notes'           => "Pecah stok / repack (Bahan Baku) - Kode RPK",
                'created_at'      => now(),
            ]);

            $this->syncGlobalStock($sourceProduct);

            // 2. Buat header log repack
            $repackLog = \App\Models\RepackLog::create([
                'repack_code'       => \App\Models\RepackLog::generateCode(),
                'location_id'       => $locationId,
                'source_product_id' => $sourceProductId,
                'source_quantity'   => $sourceQty,
                'created_by'        => $userId,
                'notes'             => $notes ?: 'Pecah stok / kemas ulang',
                'created_at'        => now(),
            ]);

            // 3. Kalkulasi total HPP (Harga Modal) bahan baku
            $totalSourceCost = $sourceProduct->cost_price * $sourceQty;

            // Hitung total ekivalen yield dalam unit source
            $totalSourceEquivalents = 0.0;
            $targetDataList = [];

            foreach ($targets as $target) {
                $targetProductId = $target['target_product_id'];
                $targetQty = floatval($target['target_quantity']);
                $packagingCost = intval($target['additional_packaging_cost'] ?? 0);

                if ($targetQty <= 0) {
                    continue;
                }

                // Cari conversion_rate dari database
                $conversion = \App\Models\ProductConversion::where('source_product_id', $sourceProductId)
                    ->where('target_product_id', $targetProductId)
                    ->first();
                $rate = $conversion ? floatval($conversion->conversion_rate) : 1.0;
                if ($rate <= 0) {
                    $rate = 1.0;
                }

                $sourceEquivalent = $targetQty / $rate;
                $totalSourceEquivalents += $sourceEquivalent;

                $targetDataList[] = [
                    'product_id'         => $targetProductId,
                    'quantity'           => $targetQty,
                    'rate'               => $rate,
                    'source_equivalent'  => $sourceEquivalent,
                    'packaging_cost'     => $packagingCost,
                ];
            }

            // Jika tidak ada target valid, batalkan
            if (empty($targetDataList)) {
                throw new \Exception("Daftar produk hasil repack tidak valid.");
            }

            // Hindari division by zero jika yield = 0
            $totalSourceEquivalentsForCost = $totalSourceEquivalents > 0 ? $totalSourceEquivalents : 1.0;

            // 4. Tambah stok untuk setiap produk target & update cost_price
            foreach ($targetDataList as $item) {
                $targetProduct = Product::findOrFail($item['product_id']);
                $targetQty     = $item['quantity'];

                // Tambah stok di lokasi
                $targetPs = ProductStock::getOrCreate($item['product_id'], $locationId);
                $beforeTarget = $targetPs->stock;
                $targetPs->incrementStock($targetQty);

                // Hitung HPP per unit
                // Rumus: (bagian total source cost yang diserap / kuantitas target) + biaya kemasan per unit
                // Bagian total source cost diserap = (ekivalen unit / total ekivalen) * totalSourceCost
                $allocatedSourceCost = ($item['source_equivalent'] / $totalSourceEquivalentsForCost) * $totalSourceCost;
                $unitSourceCost = $targetQty > 0 ? ($allocatedSourceCost / $targetQty) : 0;
                $calculatedCostPrice = round($unitSourceCost + $item['packaging_cost']);

                // Log repack item
                \App\Models\RepackLogItem::create([
                    'repack_log_id'             => $repackLog->id,
                    'target_product_id'         => $item['product_id'],
                    'target_quantity'           => $targetQty,
                    'additional_packaging_cost' => $item['packaging_cost'],
                    'calculated_cost_price'      => $calculatedCostPrice,
                ]);

                // Log mutasi stok produk target
                StockAdjustmentLog::create([
                    'product_id'      => $item['product_id'],
                    'location_id'     => $locationId,
                    'type'            => 'adjustment',
                    'quantity_before' => $beforeTarget,
                    'quantity_change' => $targetQty,
                    'quantity_after'  => $targetPs->stock,
                    'created_by'      => $userId,
                    'notes'           => "Hasil repack - Kode {$repackLog->repack_code}",
                    'created_at'      => now(),
                ]);

                $this->syncGlobalStock($targetProduct);

                // Update cost price produk hasil repack di database
                $targetProduct->update([
                    'cost_price' => $calculatedCostPrice
                ]);
            }

            return $repackLog;
        });
    }
}
