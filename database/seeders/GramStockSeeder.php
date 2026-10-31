<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockLocation;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class GramStockSeeder extends Seeder
{
    /**
     * Mengisi stok khusus untuk semua produk yang bersatuan gram
     * sebanyak 50.000 gram (50 kg) untuk setiap cabang.
     */
    public function run(): void
    {
        $locations = StockLocation::all();
        $gramProducts = Product::where('price_unit', 'gram')->where('is_bundle', false)->get();

        foreach ($locations as $loc) {
            foreach ($gramProducts as $prod) {
                ProductStock::updateOrCreate(
                    ['product_id' => $prod->id, 'location_id' => $loc->id],
                    ['stock' => 50000] // 50.000 gram = 50 kg per cabang
                );
            }
        }

        app(StockService::class)->syncAllGlobalStocks();

        if (isset($this->command)) {
            $this->command->info("SUKSES: " . $gramProducts->count() . " produk gram berhasil diisi 50.000 gram (50 kg) per cabang.");
        }
    }
}

