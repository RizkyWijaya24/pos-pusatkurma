<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductConversion;
use App\Models\ProductStock;
use App\Models\RepackLog;
use App\Models\StockLocation;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRepackTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private User $admin;
    private StockLocation $location;
    private Product $sourceProduct;
    private Product $targetProduct1;
    private Product $targetProduct2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockService = app(StockService::class);

        // 1. Buat User Admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'branch' => 'Cabang Rumah'
        ]);

        // 2. Buat Lokasi
        $this->location = StockLocation::create([
            'name' => 'Cabang Rumah',
            'type' => 'gudang',
            'is_active' => true,
        ]);

        // 3. Buat Produk Asal (Dus)
        $this->sourceProduct = Product::create([
            'sku' => 'PK-SRC-001',
            'name' => 'Pistachio Bulk Dus 10kg',
            'category' => 'Premium',
            'cost_price' => 1500000,
            'selling_price' => 1800000,
            'price_unit' => 'dus',
            'stock' => 0,
        ]);

        // 4. Buat Produk Target 1 (Pack 1kg)
        $this->targetProduct1 = Product::create([
            'sku' => 'PK-TGT-001',
            'name' => 'Pistachio Repack 1kg',
            'category' => 'Premium',
            'cost_price' => 0,
            'selling_price' => 200000,
            'price_unit' => 'kg',
            'stock' => 0,
        ]);

        // 5. Buat Produk Target 2 (Pouch 400g)
        $this->targetProduct2 = Product::create([
            'sku' => 'PK-TGT-002',
            'name' => 'Pistachio Pouch 400g',
            'category' => 'Premium',
            'cost_price' => 0,
            'selling_price' => 90000,
            'price_unit' => 'pack',
            'stock' => 0,
        ]);

        // 6. Inisialisasi Konversi
        // 1 Dus = 10 Kg
        ProductConversion::create([
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct1->id,
            'conversion_rate' => 10.00,
        ]);

        // 1 Dus = 25 Pouch (ukuran 400g)
        ProductConversion::create([
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct2->id,
            'conversion_rate' => 25.00,
        ]);
    }

    /**
     * Test proses repack berhasil memotong stok asal, menambah stok target,
     * memperbarui HPP (cost_price), dan membuat logs.
     */
    public function test_repack_product_successfully_calculates_hpp_and_mutates_stock()
    {
        // Berikan stok asal = 5 Dus di lokasi terpilih
        $ps = ProductStock::getOrCreate($this->sourceProduct->id, $this->location->id);
        $ps->stock = 5.00;
        $ps->save();
        $this->stockService->syncGlobalStock($this->sourceProduct);

        // Eksekusi Repack 1 Dus menjadi:
        // - 5 Kg (Pistachio Repack 1kg) - biaya kemasan Rp 0
        // - 10 Pouch (Pistachio Pouch 400g) - biaya kemasan Rp 3.000 / pouch
        $targets = [
            [
                'target_product_id' => $this->targetProduct1->id,
                'target_quantity' => 5.00, // setara 0.5 Dus
                'additional_packaging_cost' => 0,
            ],
            [
                'target_product_id' => $this->targetProduct2->id,
                'target_quantity' => 10.00, // setara 0.4 Dus (Total yield = 0.9 Dus. Waste = 0.1 Dus)
                'additional_packaging_cost' => 3000,
            ]
        ];

        $repackLog = $this->stockService->repackProduct(
            $this->location->id,
            $this->sourceProduct->id,
            1.00, // 1 Dus di-repack
            $targets,
            $this->admin->id,
            'Test repack logic'
        );

        // ASSERTIONS:
        
        // 1. Log repack terbuat
        $this->assertDatabaseHas('repack_logs', [
            'id' => $repackLog->id,
            'source_product_id' => $this->sourceProduct->id,
            'source_quantity' => 1.00,
            'location_id' => $this->location->id,
            'created_by' => $this->admin->id,
        ]);

        // 2. Stok Bahan Baku berkurang dari 5.00 menjadi 4.00
        $this->assertEquals(4.00, $this->sourceProduct->fresh()->getStockAtLocation($this->location->id));
        $this->assertEquals(4.00, $this->sourceProduct->fresh()->stock); // Global stock

        // 3. Stok Hasil Repack 1 bertambah menjadi 5.00
        $this->assertEquals(5.00, $this->targetProduct1->fresh()->getStockAtLocation($this->location->id));

        // 4. Stok Hasil Repack 2 bertambah menjadi 10.00
        $this->assertEquals(10.00, $this->targetProduct2->fresh()->getStockAtLocation($this->location->id));

        // 5. Kalkulasi HPP & Update cost_price
        // Total source cost = 1.500.000 * 1 = 1.500.000
        // Total equivalents = (5 / 10) + (10 / 25) = 0.5 + 0.4 = 0.9 Dus
        // Cost per equivalent = 1.500.000 / 0.9 = 1.666.666,67 per Dus
        // Target 1 HPP = (1.666.666,67 / 10) + 0 = Rp 166.667
        // Target 2 HPP = (1.666.666,67 / 25) + 3000 = 66.667 + 3000 = Rp 69.667
        
        $this->assertEquals(166667, $this->targetProduct1->fresh()->cost_price);
        $this->assertEquals(69667, $this->targetProduct2->fresh()->cost_price);
    }
}
