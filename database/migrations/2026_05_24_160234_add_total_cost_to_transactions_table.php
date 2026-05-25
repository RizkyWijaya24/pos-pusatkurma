<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('total_cost')->default(0)->after('items_summary');
        });

        // Populate existing transactions' total_cost
        $transactions = DB::table('transactions')->get();
        foreach ($transactions as $trx) {
            $totalCost = 0;
            // Parse items_summary which looks like: "Kurma Lulu Al-Khass (Per Gram) (500 gram), Kurma Sukari Basah (2 kg)"
            if (!empty($trx->items_summary)) {
                $items = explode(', ', $trx->items_summary);
                $parsedSuccessfully = true;
                foreach ($items as $itemStr) {
                    // Match pattern: "Product Name (qty unit)"
                    // e.g. "Kurma Lulu Al-Khass (Per Gram) (500 gram)"
                    // e.g. "Kurma Sukari Basah (2 kg)"
                    if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)$/', trim($itemStr), $matches)) {
                        $name = trim($matches[1]);
                        $qty = floatval($matches[2]);
                        
                        $product = DB::table('products')->where('name', $name)->first();
                        if ($product) {
                            $totalCost += round($product->cost_price * $qty);
                        } else {
                            $parsedSuccessfully = false;
                        }
                    } else {
                        $parsedSuccessfully = false;
                    }
                }
            } else {
                $parsedSuccessfully = false;
            }
            
            if ($totalCost == 0 || !$parsedSuccessfully) {
                // Fallback to 70% of total price as cost price
                $totalCost = round($trx->total_price * 0.7);
            }
            
            DB::table('transactions')->where('id', $trx->id)->update(['total_cost' => $totalCost]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('total_cost');
        });
    }
};
