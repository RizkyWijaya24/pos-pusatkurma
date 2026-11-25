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
        Schema::create('wholesale_price_memories', function (Blueprint $table) {
            $table->id();
            $table->string('customer_key', 191)->index();
            $table->string('product_key', 191)->index();
            $table->string('price_unit', 32)->default('pcs');
            $table->unsignedBigInteger('last_selling_price');
            $table->unsignedBigInteger('last_cost_price')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_key', 'product_key'], 'wpm_customer_product_unique');
        });

        // Backfill dari histori transaksi wholesale yang sudah ada
        try {
            $now = now();
            $memories = [];

            DB::table('transactions')
                ->where('transaction_type', 'wholesale')
                ->where('payment_status', '!=', 'cancelled')
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->whereNotNull('items_summary')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->select('id', 'customer_name', 'items_summary', 'created_at')
                ->chunk(200, function ($rows) use (&$memories, $now) {
                    foreach ($rows as $trx) {
                        $custKey = mb_strtolower(trim($trx->customer_name));
                        if ($custKey === '') {
                            continue;
                        }

                        if (preg_match_all('/([^,]+?)\s*\(\s*([\d\.]+)\s*([a-zA-Z0-9\/]+)\s*x\s*(\d+)\s*\)/i', $trx->items_summary, $matches, PREG_SET_ORDER)) {
                            foreach ($matches as $m) {
                                $prodKey = mb_strtolower(trim($m[1]));
                                if ($prodKey === '' || str_contains($prodKey, 'stok sudah didata')) {
                                    continue;
                                }
                                $unit  = trim($m[3]);
                                $price = (int) $m[4];

                                if ($price > 0) {
                                    $compositeKey = $custKey . '||' . $prodKey;
                                    $memories[$compositeKey] = [
                                        'customer_key'        => $custKey,
                                        'product_key'         => $prodKey,
                                        'price_unit'          => $unit,
                                        'last_selling_price'  => $price,
                                        'last_cost_price'     => null,
                                        'last_transaction_at' => $trx->created_at,
                                        'created_at'          => $now,
                                        'updated_at'          => $now,
                                    ];
                                }
                            }
                        }
                    }
                });

            if (!empty($memories)) {
                foreach (array_chunk(array_values($memories), 200) as $batch) {
                    DB::table('wholesale_price_memories')->upsert(
                        $batch,
                        ['customer_key', 'product_key'],
                        ['price_unit', 'last_selling_price', 'last_transaction_at', 'updated_at']
                    );
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel transactions belum terisi saat fresh migrate
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wholesale_price_memories');
    }
};
