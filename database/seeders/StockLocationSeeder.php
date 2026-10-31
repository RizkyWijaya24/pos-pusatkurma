<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StockLocationSeeder extends Seeder
{
    /**
     * Inisialisasi 3 Lokasi Cabang dan seeding stok per cabang untuk semua produk:
     * 1. Cabang Rumah     (Gudang Utama, Toko Pusat & Stok Online) - Type: gudang
     * 2. Cabang Cianjur   (Toko Cabang Cianjur)                    - Type: cabang
     * 3. Cabang Ciranjang (Toko Cabang Ciranjang)                  - Type: cabang
     */
    public function run(): void
    {
        // ── 1. Buat / Pastikan 3 Lokasi Cabang ──────────────────────────────────
        $locationDefs = [
            [
                'name'        => 'Cabang Rumah',
                'type'        => 'gudang',
                'description' => 'Gudang utama sekaligus Toko Pusat & Stok Online',
                'is_active'   => true,
            ],
            [
                'name'        => 'Cabang Cianjur',
                'type'        => 'cabang',
                'description' => 'Toko Cabang Cianjur',
                'is_active'   => true,
            ],
            [
                'name'        => 'Cabang Ciranjang',
                'type'        => 'cabang',
                'description' => 'Toko Cabang Ciranjang',
                'is_active'   => true,
            ],
        ];

        foreach ($locationDefs as $def) {
            StockLocation::updateOrCreate(
                ['name' => $def['name']],
                $def
            );
        }

        $allLocations = StockLocation::all()->keyBy('name');
        $this->command->info('✅ 3 Lokasi Cabang berhasil disiapkan:');
        foreach ($allLocations as $loc) {
            $this->command->line("   - [ID {$loc->id}] {$loc->name} ({$loc->type})");
        }

        // ── 2. User audit log ───────────────────────────────────────────────────
        $systemUser = User::where('role', 'admin')->first()
                   ?? User::where('role', 'owner')->first()
                   ?? User::first();

        if (!$systemUser) {
            $this->command->error('❌ Tidak ada user untuk audit log!');
            return;
        }

        // ── 3. Distribusi Stok per Cabang ───────────────────────────────────────
        $products     = Product::all();
        $stockService = app(\App\Services\StockService::class);
        $totalSeeded  = 0;

        $this->command->info("⏳ Mengisi stok untuk {$products->count()} produk di 3 cabang...");

        DB::beginTransaction();
        try {
            foreach ($products as $product) {
                if ($product->is_bundle) {
                    continue;
                }

                $rawStock   = (float) $product->getRawOriginal('stock');
                $unit       = strtolower($product->price_unit ?? 'pcs');

                // Alokasi stok proporsional & realistis:
                // Gudang Rumah = 50%, Cabang Cianjur = 25%, Cabang Ciranjang = 25%
                if ($unit === 'gram') {
                    if ($rawStock >= 30000) {
                        $stockRumah     = round($rawStock * 0.50, 2);
                        $stockCianjur   = round($rawStock * 0.25, 2);
                        $stockCiranjang = round($rawStock - $stockRumah - $stockCianjur, 2);
                    } else {
                        // Baseline aman kurma ecer: 50kg gudang, 25kg per cabang
                        $stockRumah     = 50000.00;
                        $stockCianjur   = 25000.00;
                        $stockCiranjang = 25000.00;
                    }
                } elseif ($unit === 'dus') {
                    if ($rawStock >= 30) {
                        $stockRumah     = (float) round($rawStock * 0.50);
                        $stockCianjur   = (float) round($rawStock * 0.25);
                        $stockCiranjang = (float) max(0, $rawStock - $stockRumah - $stockCianjur);
                    } else {
                        $stockRumah     = 30.00;
                        $stockCianjur   = 15.00;
                        $stockCiranjang = 15.00;
                    }
                } elseif ($unit === 'kg') {
                    if ($rawStock >= 30) {
                        $stockRumah     = round($rawStock * 0.50, 2);
                        $stockCianjur   = round($rawStock * 0.25, 2);
                        $stockCiranjang = round($rawStock - $stockRumah - $stockCianjur, 2);
                    } else {
                        $stockRumah     = 100.00;
                        $stockCianjur   = 50.00;
                        $stockCiranjang = 50.00;
                    }
                } else {
                    // pcs, pack, dsb.
                    if ($rawStock >= 30) {
                        $stockRumah     = (float) round($rawStock * 0.50);
                        $stockCianjur   = (float) round($rawStock * 0.25);
                        $stockCiranjang = (float) max(0, $rawStock - $stockRumah - $stockCianjur);
                    } else {
                        $stockRumah     = 100.00;
                        $stockCianjur   = 50.00;
                        $stockCiranjang = 50.00;
                    }
                }

                $branchStockMap = [
                    'Cabang Rumah'     => $stockRumah,
                    'Cabang Cianjur'   => $stockCianjur,
                    'Cabang Ciranjang' => $stockCiranjang,
                ];

                foreach ($branchStockMap as $branchName => $targetQty) {
                    if (!isset($allLocations[$branchName])) {
                        continue;
                    }
                    $loc = $allLocations[$branchName];

                    $ps = ProductStock::where('product_id', $product->id)
                                      ->where('location_id', $loc->id)
                                      ->first();

                    if ($ps) {
                        $before = (float) $ps->stock;
                        $ps->update(['stock' => $targetQty]);

                        if ($before != $targetQty) {
                            StockAdjustmentLog::create([
                                'product_id'      => $product->id,
                                'location_id'     => $loc->id,
                                'type'            => 'adjustment',
                                'quantity_before' => $before,
                                'quantity_change' => $targetQty - $before,
                                'quantity_after'  => $targetQty,
                                'created_by'      => $systemUser->id,
                                'notes'           => "Penyesuaian stok seeder {$branchName}",
                                'created_at'      => now(),
                            ]);
                        }
                    } else {
                        ProductStock::create([
                            'product_id'  => $product->id,
                            'location_id' => $loc->id,
                            'stock'       => $targetQty,
                        ]);

                        StockAdjustmentLog::create([
                            'product_id'      => $product->id,
                            'location_id'     => $loc->id,
                            'type'            => 'initial',
                            'quantity_before' => 0,
                            'quantity_change' => $targetQty,
                            'quantity_after'  => $targetQty,
                            'created_by'      => $systemUser->id,
                            'notes'           => "Stok awal seeder {$branchName}",
                            'created_at'      => now(),
                        ]);
                    }
                }

                // Sinkronisasi global stock pada tabel products
                $totalStockNow = ProductStock::where('product_id', $product->id)->sum('stock');
                $product->update(['stock' => $totalStockNow]);

                $totalSeeded++;
            }

            // Sync virtual stock bundle jika ada
            foreach ($products as $product) {
                if ($product->is_bundle) {
                    $stockService->syncGlobalStock($product);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->command->error("❌ Terjadi kesalahan saat seeding stok: " . $e->getMessage());
            throw $e;
        }

        // ── 4. Ringkasan Hasil Seeder ───────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('╔═══════════════════════════════════════════════════════════════════╗');
        $this->command->info('║             SEEDER STOK 3 CABANG BERHASIL DIJALANKAN              ║');
        $this->command->info('╠═══════════════════════════════════════════════════════════════════╣');
        foreach ($allLocations as $loc) {
            $count = ProductStock::where('location_id', $loc->id)->where('stock', '>', 0)->count();
            $sum   = ProductStock::where('location_id', $loc->id)->sum('stock');
            $typeLabel = $loc->type === 'gudang' ? '[Gudang & Pusat]' : '[Toko Cabang]   ';
            $this->command->info(sprintf(
                "║  %-18s %s: %4d produk (Total Qty: %12s) ║",
                $loc->name,
                $typeLabel,
                $count,
                number_format($sum, 0, ',', '.')
            ));
        }
        $this->command->info('╚═══════════════════════════════════════════════════════════════════╝');
        $this->command->info("🎉 Total {$totalSeeded} produk telah memiliki stok aktif di ketiga cabang.");
    }
}
