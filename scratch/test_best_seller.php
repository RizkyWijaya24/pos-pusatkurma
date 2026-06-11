<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$transactions = \App\Models\Transaction::latest()->take(20)->get();
$controller = new \App\Http\Controllers\DashboardController();
$reflection = new \ReflectionMethod($controller, 'getBestSellers');
$reflection->setAccessible(true);

$results = $reflection->invoke($controller, $transactions, 5);
echo "Best Sellers Results:\n";
print_r($results);
