<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get cashiers (Kasir & Admin users can act as cashiers)
        $cashiers = User::whereIn('role', ['kasir', 'admin'])->get();
        if ($cashiers->isEmpty()) {
            return;
        }

        // Get products
        $products = Product::all();
        if ($products->isEmpty()) {
            return;
        }

        $paymentMethods = ['Cash', 'QRIS', 'Debit'];
        $branches = ['Cabang Rumah', 'Cabang Cianjur', 'Cabang Ciranjang'];

        // Seed transactions for the last 60 days (from 59 days ago until today)
        for ($i = 59; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);

            // Generate 3 to 8 transactions per day
            $numTransactions = rand(3, 8);

            for ($j = 0; $j < $numTransactions; $j++) {
                // Set a random time during the day (e.g. 08:00 to 21:00)
                $transactionDate = $date->copy()
                    ->setHour(rand(8, 21))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                $cashier = $cashiers->random();
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                $branch = $branches[array_rand($branches)];

                // Pick 1 to 4 random products
                $transactionProducts = $products->random(rand(1, min(4, $products->count())));
                $items = [];
                $totalPrice = 0;
                $totalCost = 0;

                foreach ($transactionProducts as $product) {
                    // Determine quantity based on unit
                    $qty = 0;
                    if ($product->price_unit === 'kg') {
                        // random decimal or int
                        $qty = rand(1, 5) + (rand(0, 1) ? 0.5 : 0);
                    } elseif ($product->price_unit === 'gram') {
                        $qty = rand(2, 10) * 100; // 200g - 1000g
                    } else {
                        $qty = rand(1, 5); // packs, units
                    }

                    $items[] = $product->name . ' (' . $qty . ' ' . $product->price_unit . ')';
                    $totalPrice += round($product->selling_price * $qty);
                    $totalCost += round($product->cost_price * $qty);

                    // Slightly adjust stock of the product (optional, but good for realism)
                    if ($product->stock >= $qty) {
                        $product->stock -= $qty;
                        $product->save();
                    }
                }

                $itemsSummary = implode(', ', $items);
                $trxCode = 'TRX-' . $transactionDate->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

                // Create transaction and explicitly set created_at/updated_at
                $transaction = new Transaction();
                $transaction->cashier_id = $cashier->id;
                $transaction->transaction_code = $trxCode;
                $transaction->items_summary = $itemsSummary;
                $transaction->total_price = $totalPrice;
                $transaction->total_cost = $totalCost;
                $transaction->payment_method = $paymentMethod;
                $transaction->branch = $branch;
                $transaction->created_at = $transactionDate;
                $transaction->updated_at = $transactionDate;
                $transaction->save();
            }
        }
    }
}
