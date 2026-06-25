<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Product::where('sku', 'PK-D31E86')->first();
if ($p) {
    echo "PRODUCT:\n";
    print_r($p->toArray());
    echo "\nSTOCKS:\n";
    print_r($p->productStocks()->with('location')->get()->toArray());
} else {
    echo "Product not found\n";
}
