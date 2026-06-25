<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerStockTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StockLocation $mainLocation;
    private StockLocation $cabangLocation;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin User
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'branch' => 'Pusat Cianjur'
        ]);

        // Create main warehouse location
        $this->mainLocation = StockLocation::create([
            'name' => 'Pusat Cianjur',
            'type' => 'gudang',
            'is_active' => true,
        ]);

        // Create an additional branch location
        $this->cabangLocation = StockLocation::create([
            'name' => 'Cabang Cianjur',
            'type' => 'cabang',
            'is_active' => true,
        ]);
    }

    /**
     * Test creating a product initializes stock at the main location.
     */
    public function test_creating_product_initializes_stock_at_main_location(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/products', [
            'sku' => 'PK-TEST-NEW',
            'name' => 'New Test Product',
            'category' => 'kurma',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'dus',
            'stock' => 12.50,
        ]);

        $response->assertStatus(200);
        $product = Product::where('sku', 'PK-TEST-NEW')->first();
        $this->assertNotNull($product);

        // Verify global stock is 12.50
        $this->assertEquals(12.50, $product->stock);

        // Verify stock at main location is 12.50
        $this->assertEquals(12.50, $product->getStockAtLocation($this->mainLocation->id));

        // Verify stock at other location is 0.00
        $this->assertEquals(0.00, $product->getStockAtLocation($this->cabangLocation->id));

        // Verify StockAdjustmentLog is created
        $this->assertDatabaseHas('stock_adjustment_logs', [
            'product_id' => $product->id,
            'location_id' => $this->mainLocation->id,
            'type' => 'initial',
            'quantity_before' => 0.00,
            'quantity_change' => 12.50,
            'quantity_after' => 12.50,
        ]);
    }

    /**
     * Test updating a product's stock adjusts the main location stock.
     */
    public function test_updating_product_stock_adjusts_main_location_stock(): void
    {
        // 1. Create a product with initial stock 5
        $product = Product::create([
            'sku' => 'PK-TEST-EDIT',
            'name' => 'Edit Test Product',
            'category' => 'kurma',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'dus',
            'stock' => 5.00,
        ]);

        // Manually setup stocks (main: 5, branch: 2)
        $psMain = ProductStock::create([
            'product_id' => $product->id,
            'location_id' => $this->mainLocation->id,
            'stock' => 5.00,
        ]);

        $psBranch = ProductStock::create([
            'product_id' => $product->id,
            'location_id' => $this->cabangLocation->id,
            'stock' => 2.00,
        ]);

        // Sync global stock to match actual sum
        $product->update(['stock' => 7.00]);

        // 2. Perform update via controller setting stock to 10
        $response = $this->actingAs($this->admin)->postJson("/admin/products/{$product->id}", [
            'sku' => 'PK-TEST-EDIT',
            'name' => 'Edit Test Product Updated',
            'category' => 'kurma',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'dus',
            'stock' => 10.00, // Total stock updated to 10
        ]);

        $response->assertStatus(200);

        // Branch stock should still be 2.00
        $this->assertEquals(2.00, $product->fresh()->getStockAtLocation($this->cabangLocation->id));

        // Main location stock should be updated to: 10 - 2 = 8.00
        $this->assertEquals(8.00, $product->fresh()->getStockAtLocation($this->mainLocation->id));

        // Global stock should be exactly 10.00
        $this->assertEquals(10.00, $product->fresh()->stock);

        // Verify StockAdjustmentLog is created for change (+3)
        $this->assertDatabaseHas('stock_adjustment_logs', [
            'product_id' => $product->id,
            'location_id' => $this->mainLocation->id,
            'type' => 'adjustment',
            'quantity_before' => 5.00,
            'quantity_change' => 3.00,
            'quantity_after' => 8.00,
        ]);
    }
}
