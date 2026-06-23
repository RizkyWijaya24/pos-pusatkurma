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
     * Inisialisasi lokasi stok dan migrasi stok awal semua produk ke Gudang Pusat.
     */
    public function run(): void
    {
        // ── 1. Buat semua lokasi ──────────────────────────────────────────────────
        $locations = [
            ['name' => 'Pusat Cianjur',   'type' => 'gudang',  'description' => 'Gudang utama / pusat distribusi'],
            ['name' => 'Cabang Cianjur',  'type' => 'cabang',  'description' => 'Toko Cabang Cianjur'],
            ['name' => 'Cabang Ciranjang','type' => 'cabang',  'description' => 'Toko Cabang Ciranjang'],
            ['name' => 'Cabang Rumah',    'type' => 'cabang',  'description' => 'Toko Cabang Rumah'],
            ['name' => 'Cabang Online',   'type' => 'online',  'description' => 'Penjualan Online'],
        ];

        foreach ($locations as $loc) {
            StockLocation::firstOrCreate(
                ['name' => $loc['name']],
                ['type' => $loc['type'], 'description' => $loc['description'], 'is_active' => true]
            );
        }

        $this->command->info('✅ Lokasi stok berhasil dibuat: ' . count($locations) . ' lokasi');

        // ── 2. Temukan Gudang Pusat ───────────────────────────────────────────────
        $pusat = StockLocation::where('name', 'Pusat Cianjur')->first();
        if (!$pusat) {
            $this->command->error('❌ Gudang Pusat tidak ditemukan!');
            return;
        }

        // ── 3. Temukan user system / admin untuk audit log ────────────────────────
        $systemUser = User::where('role', 'admin')->first()
                   ?? User::where('role', 'owner')->first()
                   ?? User::first();

        if (!$systemUser) {
            $this->command->error('❌ Tidak ada user untuk audit log!');
            return;
        }

        // ── 4. Migrasi stok produk yang ada → Gudang Pusat ───────────────────────
        $products = Product::all();
        $migratedCount = 0;
        $skippedCount  = 0;

        foreach ($products as $product) {
            $existingStock = ProductStock::where('product_id', $product->id)
                                         ->where('location_id', $pusat->id)
                                         ->first();

            if ($existingStock) {
                // Sudah ada, skip (seeder idempoten)
                $skippedCount++;
                continue;
            }

            if ($product->stock <= 0) {
                // Produk stok 0 tetap dibuat record dengan stok 0
                ProductStock::create([
                    'product_id'  => $product->id,
                    'location_id' => $pusat->id,
                    'stock'       => 0.00,
                ]);

                // Buat record 0 juga untuk semua cabang
                foreach (StockLocation::where('id', '!=', $pusat->id)->get() as $loc) {
                    ProductStock::firstOrCreate([
                        'product_id'  => $product->id,
                        'location_id' => $loc->id,
                    ], ['stock' => 0.00]);
                }

                $migratedCount++;
                continue;
            }

            // Masukkan stok existing ke Gudang Pusat
            ProductStock::create([
                'product_id'  => $product->id,
                'location_id' => $pusat->id,
                'stock'       => $product->stock,
            ]);

            // Buat record stok 0 untuk semua cabang lain
            foreach (StockLocation::where('id', '!=', $pusat->id)->get() as $loc) {
                ProductStock::firstOrCreate([
                    'product_id'  => $product->id,
                    'location_id' => $loc->id,
                ], ['stock' => 0.00]);
            }

            // Catat log inisialisasi
            StockAdjustmentLog::create([
                'product_id'      => $product->id,
                'location_id'     => $pusat->id,
                'type'            => 'initial',
                'quantity_before' => 0,
                'quantity_change' => $product->stock,
                'quantity_after'  => $product->stock,
                'created_by'      => $systemUser->id,
                'notes'           => 'Migrasi stok awal sistem multi-cabang',
                'created_at'      => now(),
            ]);

            $migratedCount++;
        }

        $this->command->info("✅ Stok berhasil dimigrasi ke Gudang Pusat: {$migratedCount} produk");
        if ($skippedCount > 0) {
            $this->command->warn("⚠️  Produk yang dilewati (sudah ada): {$skippedCount}");
        }
        $this->command->info('🎉 Seeder StockLocation selesai!');
    }
}
