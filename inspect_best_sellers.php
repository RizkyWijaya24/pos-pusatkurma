<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Instantiate controller or run same queries
$selectedBranch = null; // Semua Cabang

$baseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
    ? \App\Models\Transaction::where('branch', $selectedBranch)
    : \App\Models\Transaction::query();

$dateObj = \Carbon\Carbon::today()->startOfDay();
$startOfWeek = $dateObj->copy()->startOfWeek();
$endOfWeek = $dateObj->copy()->endOfWeek();
$startOfMonth = $dateObj->copy()->startOfMonth();
$endOfMonth = $dateObj->copy()->endOfMonth();

$todayTransactionsForBestSeller = $baseQuery()
    ->whereBetween('created_at', [$dateObj->copy()->startOfDay(), $dateObj->copy()->endOfDay()])
    ->get();
$weeklyTransactionsForBestSeller = $baseQuery()
    ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
    ->get();
$monthlyTransactionsForBestSeller = $baseQuery()
    ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
    ->get();

// Define getBestSellers helper
function getBestSellersLocal($transactions, $limit = 5) {
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
    uasort($bestSellers, function ($a, $b) {
        if ($b['count'] === $a['count']) {
            return $b['qty'] <=> $a['qty'];
        }
        return $b['count'] <=> $a['count'];
    });
    return array_slice($bestSellers, 0, $limit);
}

$today = getBestSellersLocal($todayTransactionsForBestSeller);
$weekly = getBestSellersLocal($weeklyTransactionsForBestSeller);
$monthly = getBestSellersLocal($monthlyTransactionsForBestSeller);

echo "TODAY BEST SELLERS:\n";
print_r($today);
echo "\nWEEKLY BEST SELLERS:\n";
print_r($weekly);
echo "\nMONTHLY BEST SELLERS:\n";
print_r($monthly);
