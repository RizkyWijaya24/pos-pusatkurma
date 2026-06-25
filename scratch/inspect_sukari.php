<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$p = Product::where('sku', 'PK-22A8F1')->first();
if ($p) {
    echo "PRODUCT:\n";
    print_r($p->toArray());
    echo "\nSTOCKS:\n";
    print_r($p->productStocks()->with('location')->get()->toArray());
} else {
    echo "Product PK-22A8F1 not found!\n";
}
