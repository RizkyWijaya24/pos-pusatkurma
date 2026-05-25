<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'PK-AJW-001',
                'name' => 'Kurma Ajwa Premium',
                'category' => 'Premium',
                'cost_price' => 130000,
                'selling_price' => 180000,
                'price_unit' => 'kg',
                'stock' => 45.00,
            ],
            [
                'sku' => 'PK-SUK-002',
                'name' => 'Kurma Sukari Basah',
                'category' => 'Basah',
                'cost_price' => 70000,
                'selling_price' => 95000,
                'price_unit' => 'kg',
                'stock' => 120.00,
            ],
            [
                'sku' => 'PK-KHL-003',
                'name' => 'Kurma Khalas Premium',
                'category' => 'Premium',
                'cost_price' => 45000,
                'selling_price' => 65000,
                'price_unit' => 'pack',
                'stock' => 8.00,
            ],
            [
                'sku' => 'PK-MED-004',
                'name' => 'Kurma Medjool Jumbo',
                'category' => 'Premium',
                'cost_price' => 160000,
                'selling_price' => 220000,
                'price_unit' => 'kg',
                'stock' => 25.00,
            ],
            [
                'sku' => 'PK-TUN-005',
                'name' => 'Kurma Tunisia Tangkai',
                'category' => 'Kering',
                'cost_price' => 60000,
                'selling_price' => 85000,
                'price_unit' => 'pack',
                'stock' => 6.00,
            ],
            [
                'sku' => 'PK-LUL-006',
                'name' => 'Kurma Lulu Al-Khass (Per Gram)',
                'category' => 'Kering',
                'cost_price' => 100,
                'selling_price' => 150,
                'price_unit' => 'gram',
                'stock' => 5000.00, // 5000 grams
            ]
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['sku' => $p['sku']],
                $p
            );
        }
    }
}
