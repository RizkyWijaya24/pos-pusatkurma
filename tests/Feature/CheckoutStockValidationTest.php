<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutStockValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private User $admin;
    private StockLocation $cabangLocation;
    private StockLocation $pusatLocation;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Locations
        $this->pusatLocation = StockLocation::create([
            'name' => 'Cabang Rumah',
            'type' => 'gudang',
            'is_active' => true,
        ]);

        $this->cabangLocation = StockLocation::create([
            'name' => 'Cabang Cianjur',
            'type' => 'cabang',
            'is_active' => true,
        ]);

        // 2. Create Users
        $this->cashier = User::factory()->create([
            'role' => 'kasir',
            'branch' => 'Cabang Cianjur',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'branch' => 'Cabang Rumah',
        ]);

        // 3. Create Product
        $this->product = Product::create([
            'sku' => 'PK-SUKARI',
            'name' => 'Kurma Sukari',
            'category' => 'kurma',
            'cost_price' => 50000,
            'selling_price' => 70000,
            'price_unit' => 'pcs',
            'stock' => 0.0, // aggregate starts at 0
        ]);

        // Setup stock at locations
        ProductStock::create([
            'product_id' => $this->product->id,
            'location_id' => $this->cabangLocation->id,
            'stock' => 5.0, // Stock at Cianjur is 5
        ]);

        ProductStock::create([
            'product_id' => $this->product->id,
            'location_id' => $this->pusatLocation->id,
            'stock' => 10.0, // Stock at Pusat is 10
        ]);
    }

    /**
     * Test POS Cashier checkout fails when branch stock is insufficient.
     */
    public function test_pos_cashier_checkout_fails_when_branch_stock_is_insufficient(): void
    {
        $response = $this->actingAs($this->cashier)->postJson('/kasir/transactions', [
            'total_price' => 700000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'items' => [
                [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'qty' => 10.0, // Requested 10, but only 5 available at Cianjur
                    'price_unit' => 'pcs',
                    'price' => 70000,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonPath('message', 'Stok produk "Kurma Sukari" tidak mencukupi! Tersedia: 5 pcs, diminta: 10 pcs');
    }

    /**
     * Test POS Cashier checkout succeeds when branch stock is sufficient.
     */
    public function test_pos_cashier_checkout_succeeds_when_branch_stock_is_sufficient(): void
    {
        $response = $this->actingAs($this->cashier)->postJson('/kasir/transactions', [
            'total_price' => 210000,
            'discount' => 0,
            'payment_method' => 'Cash',
            'items' => [
                [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'qty' => 3.0, // Requested 3, available 5
                    'price_unit' => 'pcs',
                    'price' => 70000,
                ]
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify stock is decremented in Cianjur location
        $stockCianjur = $this->product->getStockAtLocation($this->cabangLocation->id);
        $this->assertEquals(2.0, $stockCianjur);
    }

    /**
     * Test wholesale checkout fails when pusat stock is insufficient.
     */
    public function test_wholesale_checkout_fails_when_pusat_stock_is_insufficient(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/admin/wholesale-transactions', [
            'customer_name' => 'Wholesale Customer',
            'customer_phone' => '08123456789',
            'payment_method' => 'Cash',
            'discount' => 0,
            'shipping_cost' => 0,
            'items' => [
                [
                    'name' => $this->product->name,
                    'qty' => 15.0, // Requested 15, but only 10 available at Pusat (Cabang Rumah)
                    'price_unit' => 'pcs',
                    'selling_price' => 60000,
                ]
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonPath('message', 'Stok produk "Kurma Sukari" tidak mencukupi! Tersedia: 10 pcs, diminta: 15 pcs');
    }

    /**
     * Test online shop checkout fails when pusat stock is insufficient.
     */
    public function test_online_shop_checkout_fails_when_pusat_stock_is_insufficient(): void
    {
        // Disable DOKU payment integration to prevent actual external requests
        config(['doku.enabled' => false]);

        $response = $this->postJson('/shop/checkout', [
            'name' => 'Online Customer',
            'phone' => '08122334455',
            'email' => 'customer@online.com',
            'address' => 'Jl. Kebon Jeruk No. 5',
            'notes' => 'Tinggalkan di pos satpam',
            'items' => [
                [
                    'id' => $this->product->id,
                    'qty' => 12.0 // Requested 12, but only 10 available at Pusat (Cabang Rumah)
                ]
            ],
            'destination_city_id' => 109, // Cianjur
            'destination_city_name' => 'Cianjur',
            'shipping_courier' => 'jne',
            'shipping_service' => 'REG',
            'shipping_service_name' => 'Layanan Reguler JNE',
            'shipping_cost' => 10000,
            'shipping_etd' => '1-2 Hari',
        ]);

        $response->assertStatus(500);
        $response->assertJson([
            'status' => 'error',
        ]);
        $response->assertJsonPath('message', 'Stok produk "Kurma Sukari" tidak mencukupi! Tersedia: 10 pcs, diminta: 12 pcs');
    }
}
