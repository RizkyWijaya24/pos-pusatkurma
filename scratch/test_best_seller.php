<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$transactions = \App\Models\Transaction::latest()->take(50)->get();

// Let's write the combined logic here to test before putting it in the controller
function getBestSellersTest($transactions, $limit = 5) {
    $bestSellers = [];
    foreach ($transactions as $trx) {
        if (empty($trx->items_summary)) {
            continue;
        }
        $parts = explode(', ', $trx->items_summary);
        $seenInThisTransaction = [];
        foreach ($parts as $part) {
            if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)$/', trim($part), $matches)) {
                $name = trim($matches[1]);
                $qty = floatval($matches[2]);
                $unit = strtolower(trim($matches[3]));
            } else {
                $name = trim($part);
                $qty = 1.0;
                $unit = 'pcs';
            }

            // Normalisasi Gram ke Kg
            if ($unit === 'gram' || $unit === 'g') {
                $qty = $qty / 1000;
                $unit = 'kg';
            }

            if (!isset($bestSellers[$name])) {
                $bestSellers[$name] = [
                    'name' => $name,
                    'qty' => 0.0,
                    'unit' => $unit,
                    'count' => 0
                ];
            }
            $bestSellers[$name]['qty'] += $qty;
            if (!isset($seenInThisTransaction[$name])) {
                $bestSellers[$name]['count'] += 1;
                $seenInThisTransaction[$name] = true;
            }
        }
    }

    // Sort by count (frequency) desc, secondary by qty desc
    uasort($bestSellers, function ($a, $b) {
        if ($b['count'] === $a['count']) {
            return $b['qty'] <=> $a['qty'];
        }
        return $b['count'] <=> $a['count'];
    });

    return array_slice($bestSellers, 0, $limit);
}

$results = getBestSellersTest($transactions, 5);
echo "Combined Best Sellers (Freq + Normalized Qty):\n";
print_r($results);
