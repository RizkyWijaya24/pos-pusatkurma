<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveStockApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test live-stock API returns active products (stock > 0).
     */
    public function test_can_fetch_live_stock_of_active_products(): void
    {
        // Create active product (stock > 0)
        Product::create([
            'sku' => 'PK-TEST1',
            'name' => 'Kurma Ajwa',
            'category' => 'Kurma',
            'cost_price' => 100000,
            'selling_price' => 150000,
            'price_unit' => 'kg',
            'stock' => 10.50,
        ]);

        // Create inactive product (stock = 0)
        Product::create([
            'sku' => 'PK-TEST2',
            'name' => 'Kurma Sukari Out of Stock',
            'category' => 'Kurma',
            'cost_price' => 50000,
            'selling_price' => 75000,
            'price_unit' => 'kg',
            'stock' => 0.00,
        ]);

        $response = $this->getJson('/api/v1/live-stock');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'name',
                        'selling_price',
                        'stock',
                        'category',
                    ]
                ]
            ]);

        // Assert that both the active product and the out of stock product are included
        $response->assertJsonFragment(['name' => 'Kurma Ajwa']);
        $response->assertJsonFragment(['name' => 'Kurma Sukari Out of Stock']);
    }
}
