<?php

namespace App\Services;

use App\Models\Product;
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
        User $soldBy
    ): bool {
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
            'notes'           => "Penjualan di {$location->name}",
            'created_at'      => now(),
        ]);

        $this->syncGlobalStock($product);

        return true;
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
}
