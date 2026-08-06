<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private StockLocation $mainLocation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'branch' => 'Cabang Rumah'
        ]);

        $this->mainLocation = StockLocation::create([
            'name' => 'Cabang Rumah',
            'type' => 'gudang',
            'is_active' => true,
        ]);
    }

    /**
     * Test that the shop catalog only shows active products.
     */
    public function test_catalog_only_shows_active_products(): void
    {
        // Create active product
        $activeProduct = Product::create([
            'sku' => 'PK-ACTIVE-1',
            'name' => 'Active Date Fruit',
            'category' => 'Premium',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'pcs',
            'stock' => 10,
            'is_active_in_shop' => true,
        ]);

        // Create inactive product
        $inactiveProduct = Product::create([
            'sku' => 'PK-INACTIVE-1',
            'name' => 'Hidden Date Fruit',
            'category' => 'Premium',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'pcs',
            'stock' => 10,
            'is_active_in_shop' => false,
        ]);

        $response = $this->get('/shop');

        $response->assertStatus(200);
        $response->assertSee($activeProduct->name);
        $response->assertDontSee($inactiveProduct->name);
    }

    /**
     * Test that direct detail page accesses fail with 404 for inactive products.
     */
    public function test_catalog_detail_returns_404_for_inactive_product(): void
    {
        $activeProduct = Product::create([
            'sku' => 'PK-ACTIVE-2',
            'name' => 'Active Detail Date',
            'category' => 'Premium',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'pcs',
            'stock' => 10,
            'is_active_in_shop' => true,
        ]);

        $inactiveProduct = Product::create([
            'sku' => 'PK-INACTIVE-2',
            'name' => 'Hidden Detail Date',
            'category' => 'Premium',
            'cost_price' => 50000,
            'selling_price' => 100000,
            'price_unit' => 'pcs',
            'stock' => 10,
            'is_active_in_shop' => false,
        ]);

        // Access active product detail: should succeed
        $responseActive = $this->get("/shop/product/{$activeProduct->id}");
        $responseActive->assertStatus(200);
        $responseActive->assertSee($activeProduct->name);

        // Access inactive product detail: should return 404
        $responseInactive = $this->get("/shop/product/{$inactiveProduct->id}");
        $responseInactive->assertStatus(404);
    }

    /**
     * Test that admin can toggle product shop visibilities via settings page.
     */
    public function test_admin_can_toggle_product_visibility_via_settings(): void
    {
        // 1. Create two products (active by default)
        $p1 = Product::create([
            'sku' => 'PK-TEST-VIS-1',
            'name' => 'Visible Product',
            'category' => 'Premium',
            'cost_price' => 40000,
            'selling_price' => 80000,
            'price_unit' => 'pcs',
            'stock' => 5,
        ]);

        $p2 = Product::create([
            'sku' => 'PK-TEST-VIS-2',
            'name' => 'Hidden Product Later',
            'category' => 'Premium',
            'cost_price' => 45000,
            'selling_price' => 90000,
            'price_unit' => 'pcs',
            'stock' => 5,
        ]);

        $p1->refresh();
        $p2->refresh();

        $this->assertTrue($p1->is_active_in_shop);
        $this->assertTrue($p2->is_active_in_shop);

        // 2. Submit settings form, enabling p1 and disabling p2
        $response = $this->actingAs($this->admin)->post('/admin/settings', [
            'settings' => [
                'active_product_ids_submitted' => '1',
                'active_product_ids' => [$p1->id], // Only p1 checked
                'shop_name' => 'Pusat Kurma Baru'
            ]
        ]);

        $response->assertRedirect();
        $p1->refresh();
        $p2->refresh();

        // Verify status has updated accordingly
        $this->assertTrue($p1->is_active_in_shop);
        $this->assertFalse($p2->is_active_in_shop);
    }
}
