<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductConversionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Product $sourceProduct;
    private Product $targetProduct;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'branch' => 'Pusat Cianjur'
        ]);

        // Cashier
        $this->cashier = User::factory()->create([
            'role' => 'kasir',
            'branch' => 'Cabang Cianjur'
        ]);

        // Products
        $this->sourceProduct = Product::create([
            'sku' => 'PK-SRC-001',
            'name' => 'Kurma Dus 10kg',
            'category' => 'kurma',
            'cost_price' => 500000,
            'selling_price' => 600000,
            'price_unit' => 'dus',
            'stock' => 5,
        ]);

        $this->targetProduct = Product::create([
            'sku' => 'PK-TGT-001',
            'name' => 'Kurma Eceran 1kg',
            'category' => 'kurma',
            'cost_price' => 50000,
            'selling_price' => 70000,
            'price_unit' => 'kg',
            'stock' => 0,
        ]);
    }

    /**
     * Test admin can access conversions list.
     */
    public function test_admin_can_access_conversions_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/conversions');

        $response->assertStatus(200);
        $response->assertSee('Aturan Konversi Produk');
        $response->assertSee('Kurma Dus 10kg');
    }

    /**
     * Test cashier cannot access conversions list.
     */
    public function test_cashier_cannot_access_conversions_index(): void
    {
        $response = $this->actingAs($this->cashier)->get('/admin/conversions');

        // Cashier should be forbidden/redirected (in this app role middleware might redirect or throw 403)
        $response->assertStatus(403);
    }

    /**
     * Test admin can create a product conversion.
     */
    public function test_admin_can_create_product_conversion(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/conversions', [
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct->id,
            'conversion_rate' => 10.00,
        ]);

        $response->assertRedirect('/admin/conversions');
        $this->assertDatabaseHas('product_conversions', [
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct->id,
            'conversion_rate' => 10.00,
        ]);
    }

    /**
     * Test validation prevents same source and target product.
     */
    public function test_validation_prevents_same_source_and_target(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/conversions', [
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->sourceProduct->id, // SAME PRODUCT
            'conversion_rate' => 1.00,
        ]);

        $response->assertSessionHasErrors(['target_product_id']);
        $this->assertDatabaseEmpty('product_conversions');
    }

    /**
     * Test validation prevents duplicate conversions.
     */
    public function test_validation_prevents_duplicate_conversions(): void
    {
        // Create initial conversion
        ProductConversion::create([
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct->id,
            'conversion_rate' => 10.00,
        ]);

        // Attempt duplicate POST
        $response = $this->actingAs($this->admin)->post('/admin/conversions', [
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct->id,
            'conversion_rate' => 5.00,
        ]);

        $response->assertSessionHasErrors(['target_product_id']);
        $this->assertEquals(1, ProductConversion::count());
    }

    /**
     * Test admin can delete a product conversion.
     */
    public function test_admin_can_delete_product_conversion(): void
    {
        $conv = ProductConversion::create([
            'source_product_id' => $this->sourceProduct->id,
            'target_product_id' => $this->targetProduct->id,
            'conversion_rate' => 10.00,
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/conversions/{$conv->id}");

        $response->assertRedirect('/admin/conversions');
        $this->assertDatabaseMissing('product_conversions', [
            'id' => $conv->id,
        ]);
    }
}
