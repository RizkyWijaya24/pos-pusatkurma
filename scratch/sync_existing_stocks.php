<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockLocation;
use App\Models\StockAdjustmentLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    $mainLocation = StockLocation::where('name', 'Pusat Cianjur')->first()
        ?? StockLocation::where('type', 'gudang')->first()
        ?? StockLocation::first();

    if (!$mainLocation) {
        echo "Error: No stock locations found!\n";
        return;
    }

    echo "Using main location: {$mainLocation->name} (ID: {$mainLocation->id})\n";

    $systemUser = User::where('role', 'admin')->first()
        ?? User::where('role', 'owner')->first()
        ?? User::first();

    $userId = $systemUser ? $systemUser->id : 1;

    $products = Product::all();
    $migratedCount = 0;

    foreach ($products as $product) {
        $stocksCount = ProductStock::where('product_id', $product->id)->count();

        if ($stocksCount === 0) {
            // Initialize main location with product's current stock
            $initialStock = (float) $product->stock;

            ProductStock::create([
                'product_id'  => $product->id,
                'location_id' => $mainLocation->id,
                'stock'       => $initialStock,
            ]);

            // Create 0 stock records for all other active locations
            $otherLocations = StockLocation::where('id', '!=', $mainLocation->id)->get();
            foreach ($otherLocations as $loc) {
                ProductStock::create([
                    'product_id'  => $product->id,
                    'location_id' => $loc->id,
                    'stock'       => 0.00,
                ]);
            }

            if ($initialStock > 0) {
                StockAdjustmentLog::create([
                    'product_id'      => $product->id,
                    'location_id'     => $mainLocation->id,
                    'type'            => 'initial',
                    'quantity_before' => 0,
                    'quantity_change' => $initialStock,
                    'quantity_after'  => $initialStock,
                    'created_by'      => $userId,
                    'notes'           => 'Sinkronisasi otomatis migrasi stok awal',
                    'created_at'      => now(),
                ]);
            }

            echo "Synced product: {$product->name} (SKU: {$product->sku}) with stock {$initialStock}\n";
            $migratedCount++;
        }
    }

    echo "Successfully synchronized {$migratedCount} products.\n";
});
