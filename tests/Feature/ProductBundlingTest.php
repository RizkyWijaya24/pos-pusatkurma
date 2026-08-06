<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\BundleItem;
use App\Models\ProductStock;
use App\Models\StockLocation;
use App\Models\User;
use App\Models\Transaction;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBundlingTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private User $admin;
    private StockLocation $location;
    private Product $productA;
    private Product $productB;

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
            'type' => 'cabang',
            'is_active' => true,
        ]);

        // 3. Buat Produk A
        $this->productA = Product::create([
            'sku' => 'PK-COMP-A',
            'name' => 'Kurma Sukari 500g',
            'category' => 'Basah',
            'cost_price' => 20000,
            'selling_price' => 30000,
            'price_unit' => 'pcs',
            'stock' => 0,
        ]);

        // 4. Buat Produk B
        $this->productB = Product::create([
            'sku' => 'PK-COMP-B',
            'name' => 'Madu Murni 250ml',
            'category' => 'Premium',
            'cost_price' => 40000,
            'selling_price' => 60000,
            'price_unit' => 'pcs',
            'stock' => 0,
        ]);

        // Set stok awal komponen di lokasi
        $psA = ProductStock::getOrCreate($this->productA->id, $this->location->id);
        $psA->update(['stock' => 10.00]); // 10 pcs A

        $psB = ProductStock::getOrCreate($this->productB->id, $this->location->id);
        $psB->update(['stock' => 5.00]);  // 5 pcs B

        $this->stockService->syncGlobalStock($this->productA);
        $this->stockService->syncGlobalStock($this->productB);
    }

    /**
     * Test perhitungan stok virtual paket bundling.
     */
    public function test_virtual_stock_calculation(): void
    {
        // Buat Paket Bundling: 2x Produk A + 1x Produk B
        $bundle = Product::create([
            'sku' => 'PK-BUNDLE-RAMADAN',
            'name' => 'Paket Ramadan Hemat',
            'category' => 'Premium',
            'cost_price' => 0, // default auto-calculate
            'selling_price' => 100000,
            'price_unit' => 'pack',
            'stock' => 0,
            'is_bundle' => true,
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $this->productA->id,
            'quantity' => 2.00,
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $this->productB->id,
            'quantity' => 1.00,
        ]);

        // Stok komponen A = 10, B = 5.
        // Bundle butuh 2 A + 1 B.
        // Stok virtual = min(floor(10 / 2), floor(5 / 1)) = min(5, 5) = 5.
        $this->assertEquals(5.00, $bundle->getStockAtLocation($this->location->id));

        // Ubah stok komponen A menjadi 5.
        // Stok virtual = min(floor(5 / 2), floor(5 / 1)) = min(2, 5) = 2.
        $psA = ProductStock::getOrCreate($this->productA->id, $this->location->id);
        $psA->update(['stock' => 5.00]);

        $this->assertEquals(2.00, $bundle->getStockAtLocation($this->location->id));
    }

    /**
     * Test auto-kalkulasi harga modal paket bundling.
     */
    public function test_cost_price_calculation(): void
    {
        $bundle = Product::create([
            'sku' => 'PK-BUNDLE-TEST',
            'name' => 'Paket Test',
            'category' => 'Premium',
            'cost_price' => 0, // 0 means auto-calculate
            'selling_price' => 100000,
            'price_unit' => 'pack',
            'stock' => 0,
            'is_bundle' => true,
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $this->productA->id,
            'quantity' => 2.00, // cost: 2 * 20.000 = 40.000
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $this->productB->id,
            'quantity' => 1.50, // cost: 1.5 * 40.000 = 60.000
        ]);

        // Total cost must be 40.000 + 60.000 = 100.000
        $this->assertEquals(100000, $bundle->cost_price);

        // Jika diset manual (override)
        $bundle->update(['cost_price' => 85000]);
        $this->assertEquals(85000, $bundle->cost_price);
    }

    /**
     * Test pengurangan stok komponen saat produk bundling terjual.
     */
    public function test_stock_deduction_on_bundle_sale(): void
    {
        $bundle = Product::create([
            'sku' => 'PK-BUNDLE-RAMADAN',
            'name' => 'Paket Ramadan Hemat',
            'category' => 'Premium',
            'cost_price' => 0,
            'selling_price' => 100000,
            'price_unit' => 'pack',
            'stock' => 0,
            'is_bundle' => true,
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $this->productA->id,
            'quantity' => 2.00,
        ]);

        BundleItem::create([
            'bundle_id' => $bundle->id,
            'product_id' => $this->productB->id,
            'quantity' => 1.00,
        ]);

        // Buat dummy transaksi
        $transaction = Transaction::create([
            'cashier_id' => $this->admin->id,
            'transaction_code' => 'TRX-TEST-BUNDLE',
            'items_summary' => 'Paket Ramadan Hemat (2 pack x 100000)',
            'total_price' => 200000,
            'discount' => 0,
            'total_cost' => 160000, // 2 * 80.000
            'payment_method' => 'Cash',
            'branch' => 'Cabang Rumah',
        ]);

        // Jual 2 unit bundle
        $this->stockService->deductSaleStock(
            $bundle,
            $this->location,
            2.00,
            $transaction->id,
            $this->admin
        );

        // Stok komponen A harus berkurang: 10 - (2 * 2) = 6
        $this->assertEquals(6.00, $this->productA->getStockAtLocation($this->location->id));

        // Stok komponen B harus berkurang: 5 - (2 * 1) = 3
        $this->assertEquals(3.00, $this->productB->getStockAtLocation($this->location->id));
    }

    /**
     * Test pembuatan produk bundling via API/Controller.
     */
    public function test_create_bundle_via_controller(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/products', [
            'sku' => 'PK-NEW-BUNDLE',
            'name' => 'Bundling Spesial Toko',
            'category' => 'Premium',
            'cost_price' => 0,
            'selling_price' => 120000,
            'price_unit' => 'pack',
            'is_bundle' => true,
            'bundle_items' => [
                [
                    'product_id' => $this->productA->id,
                    'quantity' => 1.00,
                ],
                [
                    'product_id' => $this->productB->id,
                    'quantity' => 2.00,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Verifikasi database record produk bundle
        $this->assertDatabaseHas('products', [
            'sku' => 'PK-NEW-BUNDLE',
            'is_bundle' => true,
        ]);

        $bundleProduct = Product::where('sku', 'PK-NEW-BUNDLE')->first();

        // Verifikasi relasi bundle_items
        $this->assertDatabaseHas('bundle_items', [
            'bundle_id' => $bundleProduct->id,
            'product_id' => $this->productA->id,
            'quantity' => 1.00,
        ]);
        $this->assertDatabaseHas('bundle_items', [
            'bundle_id' => $bundleProduct->id,
            'product_id' => $this->productB->id,
            'quantity' => 2.00,
        ]);
    }
}
