<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$transactions = \App\Models\Transaction::select('id', 'created_at', 'total_price', 'branch')->get();
echo "Total Transactions: " . $transactions->count() . "\n";
foreach ($transactions as $t) {
    echo "ID: {$t->id}, Created At: {$t->created_at}, Total Price: {$t->total_price}, Branch: {$t->branch}\n";
}
