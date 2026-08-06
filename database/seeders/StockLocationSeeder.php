<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockLocationSeeder extends Seeder
{
    /**
     * Inisialisasi lokasi stok dan isi SEMUA cabang dengan stok yang sama
     * dari kolom products.stock sebagai baseline operasional awal.
     *
     * Strategi: Semua cabang dapat stok yang sama agar tidak ada downtime.
     * Koreksi stok per cabang dilakukan belakangan via Stock Opname per lokasi.
     */
    public function run(): void
    {
        // ── 1. Buat semua lokasi ──────────────────────────────────────────────────
        $locationDefs = [
            ['name' => 'Cabang Rumah',     'type' => 'gudang', 'description' => 'Gudang utama sekaligus Toko Pusat & Stok Online'],
            ['name' => 'Cabang Cianjur',   'type' => 'cabang', 'description' => 'Toko Cabang Cianjur'],
            ['name' => 'Cabang Ciranjang', 'type' => 'cabang', 'description' => 'Toko Cabang Ciranjang'],
        ];

        foreach ($locationDefs as $loc) {
            StockLocation::firstOrCreate(
                ['name' => $loc['name']],
                ['type' => $loc['type'], 'description' => $loc['description'], 'is_active' => true]
            );
        }

        $allLocations = StockLocation::all();
        $this->command->info('✅ Lokasi stok berhasil dibuat/ditemukan: ' . $allLocations->count() . ' lokasi');

        // ── 2. Temukan user system / admin untuk audit log ────────────────────────
        $systemUser = User::where('role', 'admin')->first()
                   ?? User::where('role', 'owner')->first()
                   ?? User::first();

        if (!$systemUser) {
            $this->command->error('❌ Tidak ada user untuk audit log!');
            return;
        }

        // ── 3. Isi stok SEMUA lokasi dari products.stock (baseline operasional) ──
        //
        // CATATAN: Semua cabang diisi stok yang sama agar langsung bisa beroperasi
        // tanpa downtime. Ini adalah stok "sementara" yang harus dikoreksi melalui
        // Stock Opname fisik per cabang setelah sistem aktif.
        //
        $products      = Product::all();
        $migratedCount = 0;
        $skippedCount  = 0;
        $stockService  = app(\App\Services\StockService::class);

        $this->command->info('⏳ Mengisi stok ke semua ' . $allLocations->count() . ' cabang...');

        foreach ($products as $product) {
            if ($product->is_bundle) {
                continue;
            }

            $stockQty = 100.00;

            foreach ($allLocations as $location) {
                $ps = ProductStock::where('product_id', $product->id)
                                  ->where('location_id', $location->id)
                                  ->first();

                if ($ps) {
                    $qtyBefore = (float) $ps->stock;
                    if ($qtyBefore == $stockQty) {
                        $skippedCount++;
                        continue;
                    }
                    $ps->update(['stock' => $stockQty]);
                    
                    StockAdjustmentLog::create([
                        'product_id'      => $product->id,
                        'location_id'     => $location->id,
                        'type'            => 'adjustment',
                        'quantity_before' => $qtyBefore,
                        'quantity_change' => $stockQty - $qtyBefore,
                        'quantity_after'  => $stockQty,
                        'created_by'      => $systemUser->id,
                        'notes'           => 'Reset stok cabang ke 100 via seeder',
                        'created_at'      => now(),
                    ]);
                } else {
                    ProductStock::create([
                        'product_id'  => $product->id,
                        'location_id' => $location->id,
                        'stock'       => $stockQty,
                    ]);

                    StockAdjustmentLog::create([
                        'product_id'      => $product->id,
                        'location_id'     => $location->id,
                        'type'            => 'initial',
                        'quantity_before' => 0,
                        'quantity_change' => $stockQty,
                        'quantity_after'  => $stockQty,
                        'created_by'      => $systemUser->id,
                        'notes'           => 'Stok awal cabang 100 via seeder',
                        'created_at'      => now(),
                    ]);
                }
            }

            $stockService->syncGlobalStock($product);
            $migratedCount++;
        }

        // Sinkronisasi virtual stock untuk produk bundle setelah semua stock terisi
        foreach ($products as $product) {
            if ($product->is_bundle) {
                $stockService->syncGlobalStock($product);
            }
        }

        $this->command->info("✅ Stok baseline berhasil diisi ke {$migratedCount} produk × {$allLocations->count()} cabang");

        if ($skippedCount > 0) {
            $this->command->warn("⚠️  Record yang dilewati (sudah ada): {$skippedCount}");
        }

        // ── 4. Ringkasan ─────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('╔══════════════════════════════════════════════════════╗');
        $this->command->info('║          SEEDER SELESAI — RINGKASAN STOK            ║');
        $this->command->info('╠══════════════════════════════════════════════════════╣');

        foreach ($allLocations as $loc) {
            $count = ProductStock::where('location_id', $loc->id)->where('stock', '>', 0)->count();
            $type  = $loc->type === 'gudang' ? '(Gudang)' : '(Cabang)';
            $this->command->info("║  {$loc->name} {$type}: {$count} produk ada stok");
        }

        $this->command->info('╠══════════════════════════════════════════════════════╣');
        $this->command->info('║  ⚠️  PENTING: Stok cabang masih SEMENTARA (sama     ║');
        $this->command->info('║  semua). Lakukan Stock Opname fisik per cabang      ║');
        $this->command->info('║  untuk mengoreksi sesuai kondisi nyata lapangan.    ║');
        $this->command->info('╚══════════════════════════════════════════════════════╝');
        $this->command->newLine();
        $this->command->info('🎉 Semua kasir di semua cabang bisa langsung beroperasi!');
    }
}
