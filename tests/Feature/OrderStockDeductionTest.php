<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStockDeductionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StockLocation $onlineLocation;
    private Product $product1;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Admin User
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Create Online Location
        $this->onlineLocation = StockLocation::create([
            'name' => 'Cabang Rumah',
            'type' => 'gudang',
            'is_active' => true,
        ]);

        // Create Products
        $this->product1 = Product::create([
            'sku' => 'PK-PROD-1',
            'name' => 'Product 1',
            'category' => 'kurma',
            'cost_price' => 10000,
            'selling_price' => 15000,
            'price_unit' => 'pcs',
            'stock' => 100.00,
        ]);

        $this->product2 = Product::create([
            'sku' => 'PK-PROD-2',
            'name' => 'Product 2',
            'category' => 'kurma',
            'cost_price' => 20000,
            'selling_price' => 30000,
            'price_unit' => 'pcs',
            'stock' => 50.00,
        ]);

        // Setup initial stocks at Online Location
        ProductStock::create([
            'product_id' => $this->product1->id,
            'location_id' => $this->onlineLocation->id,
            'stock' => 100.00,
        ]);

        ProductStock::create([
            'product_id' => $this->product2->id,
            'location_id' => $this->onlineLocation->id,
            'stock' => 50.00,
        ]);
    }

    /**
     * Test that changing an order's payment_status to 'paid' deducts stock.
     */
    public function test_order_status_paid_deducts_stock(): void
    {
        // 1. Create order as pending
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'customer_name' => 'John Doe',
            'customer_phone' => '08123456789',
            'customer_email' => 'john@example.com',
            'shipping_address' => 'Test Address 1',
            'payment_status' => 'pending',
            'subtotal_amount' => 60000,
            'total_amount' => 65000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product1->id,
            'qty' => 2.00,
            'price' => 15000,
            'subtotal' => 30000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product2->id,
            'qty' => 1.00,
            'price' => 30000,
            'subtotal' => 30000,
        ]);

        // Verify initial stock values
        $this->assertEquals(100.00, $this->product1->fresh()->stock);
        $this->assertEquals(50.00, $this->product2->fresh()->stock);

        // 2. Act: Change status to paid
        $order->update(['payment_status' => 'paid']);

        // 3. Assert: Stock is deducted at the online location
        $this->assertEquals(98.00, $this->product1->fresh()->getStockAtLocation($this->onlineLocation->id));
        $this->assertEquals(49.00, $this->product2->fresh()->getStockAtLocation($this->onlineLocation->id));

        // Assert: Global stocks are synchronized
        $this->assertEquals(98.00, $this->product1->fresh()->stock);
        $this->assertEquals(49.00, $this->product2->fresh()->stock);

        // Assert: StockAdjustmentLog records are created
        $this->assertDatabaseHas('stock_adjustment_logs', [
            'product_id' => $this->product1->id,
            'location_id' => $this->onlineLocation->id,
            'type' => 'sale',
            'quantity_before' => 100.00,
            'quantity_change' => -2.00,
            'quantity_after' => 98.00,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        $this->assertDatabaseHas('stock_adjustment_logs', [
            'product_id' => $this->product2->id,
            'location_id' => $this->onlineLocation->id,
            'type' => 'sale',
            'quantity_before' => 50.00,
            'quantity_change' => -1.00,
            'quantity_after' => 49.00,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);

        // Assert: Transaction record is created under 'Cabang Online'
        $this->assertDatabaseHas('transactions', [
            'transaction_code' => 'TRX-ONL-' . $order->order_code,
            'items_summary' => 'Product 1 (2 pcs x 15000), Product 2 (1 pcs x 30000)',
            'total_price' => 60000,
            'total_cost' => 40000,
            'payment_method' => 'Midtrans',
            'branch' => 'Cabang Rumah',
        ]);
    }

    /**
     * Test that updating an order's status to paid via Admin endpoint deducts stock.
     */
    public function test_admin_marking_order_paid_deducts_stock(): void
    {
        $order = Order::create([
            'order_code' => Order::generateOrderCode(),
            'customer_name' => 'Jane Doe',
            'customer_phone' => '08123456780',
            'customer_email' => 'jane@example.com',
            'shipping_address' => 'Test Address 2',
            'payment_status' => 'pending',
            'subtotal_amount' => 30000,
            'total_amount' => 35000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product1->id,
            'qty' => 2.00,
            'price' => 15000,
            'subtotal' => 30000,
        ]);

        // Verify initial stock values
        $this->assertEquals(100.00, $this->product1->fresh()->stock);

        // Act: Make admin POST request to status update endpoint
        $response = $this->actingAs($this->admin)->postJson("/admin/orders/{$order->id}/status", [
            'status' => 'paid',
        ]);

        $response->assertStatus(200);

        // Assert: Stock is deducted
        $this->assertEquals(98.00, $this->product1->fresh()->stock);
        $this->assertEquals(98.00, $this->product1->fresh()->getStockAtLocation($this->onlineLocation->id));

        // Assert: Transaction is recorded
        $this->assertDatabaseHas('transactions', [
            'transaction_code' => 'TRX-ONL-' . $order->order_code,
            'items_summary' => 'Product 1 (2 pcs x 15000)',
            'total_price' => 30000,
            'total_cost' => 20000,
            'payment_method' => 'Midtrans',
            'branch' => 'Cabang Rumah',
        ]);
    }
}
